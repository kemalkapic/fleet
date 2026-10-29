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
    public function open(int $id, UserNotificationRepository $notifications, EntityManagerInterface $entityManager): RedirectResponse
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

        return new RedirectResponse($this->withNotificationPopup($targetUrl));
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
