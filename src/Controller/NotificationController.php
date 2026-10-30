<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserNotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications', name: 'notification_')]
#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        UserNotificationRepository $notifications,
    ): Response {
        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications->findLatestForUser($this->requireUser(), 200),
        ]);
    }

    #[Route('/fragment', name: 'fragment', methods: ['GET'])]
    public function fragment(UserNotificationRepository $notifications): JsonResponse
    {
        $user = $this->requireUser();

        return $this->json([
            'unreadCount' => $notifications->countUnreadForUser($user),
            'html' => $this->renderView('notification/_dropdown_items.html.twig', [
                'items' => $notifications->findLatestForUser($user),
            ]),
        ]);
    }

    #[Route('/read-all', name: 'read_all', methods: ['POST'])]
    public function readAll(Request $request, UserNotificationRepository $notifications): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('notifications_read_all', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $notifications->markAllRead($this->requireUser());

        return $this->redirectToRoute('notification_index');
    }

    #[Route('/{id}/open', name: 'open', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function open(
        int $id,
        Request $request,
        UserNotificationRepository $notifications,
        EntityManagerInterface $entityManager,
    ): RedirectResponse
    {
        $item = $notifications->findOneForUser($id, $this->requireUser());
        if ($item === null) {
            throw $this->createNotFoundException();
        }

        $item->markRead();
        $entityManager->flush();
        $targetUrl = $item->getTargetUrl();
        $targetUrl = $targetUrl !== null && str_starts_with($targetUrl, '/') && !str_starts_with($targetUrl, '//')
            ? $targetUrl
            : $this->generateUrl('notification_index');

        return new RedirectResponse(
            $request->query->get('source') === 'inbox'
                ? $targetUrl
                : $this->withNotificationPopup($targetUrl),
        );
    }

    #[Route('/{id}/renew', name: 'renew', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function renew(
        int $id,
        Request $request,
        UserNotificationRepository $notifications,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('ROLE_COMPANY_ADMIN');

        $item = $notifications->findOneForUser($id, $this->requireUser());
        if ($item === null || !$item->isDocumentRenewalAvailable()) {
            $this->addFlash('error', 'Ova evidencija je već produžena ili više nije dostupna.');

            return $this->redirectToRoute('notification_index');
        }

        if (!$this->isCsrfTokenValid('renew_notification_'.$item->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $document = $item->getDocument();
        $dateValue = (string) $request->request->get('validUntil');
        $validUntil = \DateTimeImmutable::createFromFormat('!Y-m-d', $dateValue);
        $dateErrors = \DateTimeImmutable::getLastErrors();

        if (!$validUntil instanceof \DateTimeImmutable
            || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
            || $validUntil->format('Y-m-d') !== $dateValue
            || $validUntil <= $document->getValidUntil()
        ) {
            $this->addFlash('error', 'Novi datum isteka mora biti poslije trenutnog roka.');

            return $this->redirectToRoute('notification_index');
        }

        $document->setValidUntil($validUntil);
        $item->markRead();
        $entityManager->flush();

        $this->addFlash('success', sprintf(
            'Rok za evidenciju "%s" produžen je do %s.',
            $document->getTypeLabel(),
            $validUntil->format('d.m.Y'),
        ));

        return $this->redirectToRoute('notification_index');
    }

    private function withNotificationPopup(string $url): string
    {
        [$path, $fragment] = array_pad(explode('#', $url, 2), 2, null);
        $path .= str_contains($path, '?') ? '&notifications=open' : '?notifications=open';

        return $fragment !== null ? $path.'#'.$fragment : $path;
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
