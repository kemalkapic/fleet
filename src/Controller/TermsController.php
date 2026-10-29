<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class TermsController extends AbstractController
{
    #[Route('/terms/accept', name: 'terms_accept', methods: ['POST'])]
    public function accept(
        Request $request,
        EntityManagerInterface $entityManager,
        #[Autowire('%app.terms_version%')] string $termsVersion,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('accept_terms_'.$termsVersion, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if ($request->request->getBoolean('accept_terms') !== true) {
            $this->addFlash('error', 'Morate prihvatiti Uvjete korištenja i Obavijest o privatnosti.');

            return $this->redirectToRoute('app_dashboard');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $user->acceptTerms($termsVersion);
        $entityManager->flush();
        $this->addFlash('success', 'Uvjeti korištenja i Obavijest o privatnosti su prihvaćeni.');

        return $this->redirectToRoute('app_dashboard');
    }
}
