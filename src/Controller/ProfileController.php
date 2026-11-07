<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Profil je uspješno ažuriran.');

            return $this->redirectToRoute('profile_edit');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form,
            'user' => $user,
            'role_label' => $this->roleLabel($user),
        ]);
    }

    private function roleLabel(User $user): string
    {
        return match (true) {
            in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true) => 'Super administrator',
            in_array('ROLE_COMPANY_ADMIN', $user->getRoles(), true) => 'Administrator firme',
            in_array('ROLE_DRIVER', $user->getRoles(), true) => 'Vozač',
            default => 'Korisnik',
        };
    }
}
