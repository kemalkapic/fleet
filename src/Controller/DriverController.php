<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\Driver;
use App\Entity\FleetDocument;
use App\Enum\Module;
use App\Form\DriverType;
use App\Repository\DriverRepository;
use App\Repository\FleetDocumentRepository;
use App\Service\Module\ModuleChecker;
use App\Service\Tenant\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/drivers', name: 'driver_')]
#[IsGranted('ROLE_COMPANY_ADMIN')]
class DriverController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        TenantContext $tenantContext,
        ModuleChecker $moduleChecker,
        DriverRepository $drivers,
    ): Response {
        $company = $tenantContext->requireCompany();
        $this->denyAccessUnlessGrantedForModule($moduleChecker, $company);
        $driverCount = $drivers->countForCompany($company);

        return $this->render('driver/index.html.twig', [
            'drivers' => $drivers->findForCompany($company),
            'driver_count' => $driverCount,
            'driver_limit' => $company->getDriverLimit(),
            'driver_limit_reached' => $this->isLimitReached($company->getDriverLimit(), $driverCount),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        TenantContext $tenantContext,
        ModuleChecker $moduleChecker,
        DriverRepository $drivers,
        FleetDocumentRepository $documents,
        EntityManagerInterface $entityManager,
    ): Response {
        $company = $tenantContext->requireCompany();
        $this->denyAccessUnlessGrantedForModule($moduleChecker, $company);

        if ($this->isLimitReached($company->getDriverLimit(), $drivers->countForCompany($company))) {
            $this->addFlash('error', 'Dosegnut je limit broja vozača. Kontaktirajte administratora da dodate još vozača.');

            return $this->redirectToRoute('driver_index');
        }

        $driver = (new Driver())->setCompany($company);
        $form = $this->createForm(DriverType::class, $driver);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->isLimitReached($company->getDriverLimit(), $drivers->countForCompany($company))) {
                $this->addFlash('error', 'Dosegnut je limit broja vozača. Kontaktirajte administratora da dodate još vozača.');

                return $this->redirectToRoute('driver_index');
            }

            $entityManager->persist($driver);
            $documentCreated = $this->createDrivingLicenceDocumentIfNeeded($driver, $company, $moduleChecker, $documents, $entityManager);
            $entityManager->flush();

            $this->addFlash(
                'success',
                $documentCreated
                    ? 'Vozač je kreiran i dokument vozačke dozvole je dodan.'
                    : 'Vozač je kreiran.'
            );

            return $this->redirectToRoute('driver_index');
        }

        return $this->render('driver/new.html.twig', [
            'driver' => $driver,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        TenantContext $tenantContext,
        ModuleChecker $moduleChecker,
        DriverRepository $drivers,
        FleetDocumentRepository $documents,
        EntityManagerInterface $entityManager,
    ): Response {
        $company = $tenantContext->requireCompany();
        $this->denyAccessUnlessGrantedForModule($moduleChecker, $company);

        $driver = $drivers->findOneForCompany($id, $company);

        if (!$driver instanceof Driver) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(DriverType::class, $driver);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Vozač je ažuriran.');

            return $this->redirectToRoute('driver_index');
        }

        return $this->render('driver/edit.html.twig', [
            'driver' => $driver,
            'form' => $form,
            'documents' => $documents->findForDriver($driver, $company),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        TenantContext $tenantContext,
        ModuleChecker $moduleChecker,
        DriverRepository $drivers,
        FleetDocumentRepository $documents,
        EntityManagerInterface $entityManager,
    ): Response {
        $company = $tenantContext->requireCompany();
        $this->denyAccessUnlessGrantedForModule($moduleChecker, $company);

        $driver = $drivers->findOneForCompany($id, $company);

        if (!$driver instanceof Driver) {
            throw new NotFoundHttpException();
        }

        if (!$this->isCsrfTokenValid('delete_driver_'.$driver->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid delete token.');
        }

        foreach ($documents->findForDriver($driver, $company) as $document) {
            $document->softDelete();
        }

        $driver->softDelete();
        $entityManager->flush();

        $this->addFlash('success', 'Vozač je obrisan.');

        return $this->redirectToRoute('driver_index');
    }

    private function denyAccessUnlessGrantedForModule(ModuleChecker $moduleChecker, Company $company): void
    {
        if (!$moduleChecker->isEnabled($company, Module::DRIVERS)) {
            throw $this->createAccessDeniedException('Drivers module is disabled.');
        }
    }

    private function createDrivingLicenceDocumentIfNeeded(
        Driver $driver,
        Company $company,
        ModuleChecker $moduleChecker,
        FleetDocumentRepository $documents,
        EntityManagerInterface $entityManager,
    ): bool {
        if (!$moduleChecker->isEnabled($company, Module::DOCUMENTS) || $driver->getLicenceExpiresAt() === null) {
            return false;
        }

        if ($this->isLimitReached($company->getDocumentLimit(), $documents->countForCompany($company))) {
            return false;
        }

        $document = (new FleetDocument())
            ->setCompany($company)
            ->setDriver($driver)
            ->setType('driving_licence')
            ->setDocumentNumber($driver->getLicenceNumber())
            ->setValidFrom($driver->getLicenceIssuedAt())
            ->setValidUntil($driver->getLicenceExpiresAt())
            ->setNotes('Automatski kreirano iz podataka vozača.');

        $entityManager->persist($document);

        return true;
    }

    private function isLimitReached(?int $limit, int $currentCount): bool
    {
        return $limit !== null && $currentCount >= $limit;
    }
}
