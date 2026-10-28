<?php

namespace App\Controller\Platform;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\Notification\TestNotificationSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/platform/users', name: 'platform_user_')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class UserController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(UserRepository $users): Response
    {
        return $this->render('platform/user/index.html.twig', [
            'users' => $users->findBy([], ['email' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = (string) $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Korisnik je kreiran.');

            return $this->redirectToRoute('platform_user_index');
        }

        return $this->render('platform/user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = (string) $form->get('plainPassword')->getData();

            if ($plainPassword !== '') {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            $entityManager->flush();

            $this->addFlash('success', 'Korisnik je ažuriran.');

            return $this->redirectToRoute('platform_user_index');
        }

        return $this->render('platform/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/send-test-notifications', name: 'send_test_notifications', methods: ['POST'])]
    public function sendTestNotifications(
        User $user,
        Request $request,
        TestNotificationSender $sender,
    ): Response {
        if (!$this->isCsrfTokenValid('send_test_notifications_'.$user->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        try {
            $result = $sender->send($user);
            $this->addFlash('success', sprintf(
                'Za %s kreirano je %d obavijesti, isporučeno %d push poruka i %s jedan testni email.',
                $user->getEmail(),
                $result['notifications'],
                $result['pushSent'],
                $result['emailSent'] ? 'poslan je' : 'nije poslan',
            ));
        } catch (\Throwable $exception) {
            $this->addFlash('error', 'Testno slanje nije uspjelo: '.$exception->getMessage());
        }

        return $this->redirectToRoute('platform_user_index');
    }
}
