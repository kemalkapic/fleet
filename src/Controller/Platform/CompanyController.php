<?php

namespace App\Controller\Platform;

use App\Entity\Company;
use App\Form\CompanyType;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/platform/companies', name: 'platform_company_')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class CompanyController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(CompanyRepository $companies): Response
    {
        return $this->render('platform/company/index.html.twig', [
            'companies' => $companies->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $company = new Company();
        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($company);
            $entityManager->flush();

            $this->addFlash('success', 'Firma je kreirana.');

            return $this->redirectToRoute('platform_company_index');
        }

        return $this->render('platform/company/new.html.twig', [
            'company' => $company,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Company $company, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Firma je ažurirana.');

            return $this->redirectToRoute('platform_company_index');
        }

        return $this->render('platform/company/edit.html.twig', [
            'company' => $company,
            'form' => $form,
        ]);
    }
}
