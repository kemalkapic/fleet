<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\Driver;
use App\Entity\FleetDocument;
use App\Entity\Vehicle;
use App\Enum\Module;
use App\Form\FleetDocumentType;
use App\Repository\DriverRepository;
use App\Repository\FleetDocumentRepository;
use App\Repository\VehicleRepository;
use App\Service\Module\ModuleChecker;
use App\Service\Storage\DocumentStorage;
use App\Service\Tenant\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/documents', name: 'document_')]
#[IsGranted('ROLE_COMPANY_ADMIN')]
class FleetDocumentController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        TenantContext $tenantContext,
        ModuleChecker $moduleChecker,
        FleetDocumentRepository $documents,
    ): Response {
        $company = $tenantContext->requireCompany();
        $this->denyAccessUnlessGrantedForModule($moduleChecker, $company);
        $documentCount = $documents->countForCompany($company);

        return $this->render('document/index.html.twig', [
            'documents' => $documents->findForCompany($company),
            'document_count' => $documentCount,
            'document_limit' => $company->getDocumentLimit(),
            'document_limit_reached' => $this->isLimitReached($company->getDocumentLimit(), $documentCount),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        TenantContext $tenantContext,
        ModuleChecker $moduleChecker,
        FleetDocumentRepository $documents,
        VehicleRepository $vehicles,
        DriverRepository $drivers,
        DocumentStorage $storage,
        EntityManagerInterface $entityManager,
    ): Response {
        $company = $tenantContext->requireCompany();
        $this->denyAccessUnlessGrantedForModule($moduleChecker, $company);

        if ($this->isLimitReached($company->getDocumentLimit(), $documents->countForCompany($company))) {
            $this->addFlash('error', 'Dosegnut je limit broja dokumenata. Kontaktirajte administratora da dodate još dokumenata.');

            return $this->redirectToRoute('document_index');
        }

        $document = (new FleetDocument())->setCompany($company);
        $vehicleId = $request->query->getInt('vehicle');

        if ($vehicleId > 0) {
            $vehicle = $vehicles->findOneForCompany($vehicleId, $company);

            if ($vehicle instanceof Vehicle) {
                $document->setVehicle($vehicle);
            }
        }

        $driverId = $request->query->getInt('driver');

        if ($driverId > 0) {
            $driver = $drivers->findOneForCompany($driverId, $company);

            if ($driver instanceof Driver) {
                $document->setDriver($driver);
            }
        }

        $form = $this->createDocumentForm($document, $company, $vehicles, $drivers);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->applyOwner($document, (string) $form->get('ownerType')->getData())) {
            if ($this->isLimitReached($company->getDocumentLimit(), $documents->countForCompany($company))) {
                $this->addFlash('error', 'Dosegnut je limit broja dokumenata. Kontaktirajte administratora da dodate još dokumenata.');

                return $this->redirectToRoute('document_index');
            }

            $uploadedFile = $form->get('file')->getData();

            if ($uploadedFile !== null) {
                $originalFilename = $uploadedFile->getClientOriginalName();
                $mimeType = $uploadedFile->getMimeType();
                $size = $uploadedFile->getSize();
                $document->setStoredFile(
                    $storage->store($uploadedFile, $company),
                    $originalFilename,
                    $mimeType,
                    is_int($size) ? $size : null,
                );
            }

            $entityManager->persist($document);
            $entityManager->flush();

            $this->addFlash('success', 'Dokument je sačuvan.');

            return $this->redirectAfterSave($document);
        }

        return $this->render('document/new.html.twig', [
            'document' => $document,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        TenantContext $tenantContext,
        ModuleChecker $moduleChecker,
        FleetDocumentRepository $documents,
        VehicleRepository $vehicles,
        DriverRepository $drivers,
        DocumentStorage $storage,
        EntityManagerInterface $entityManager,
    ): Response {
        $company = $tenantContext->requireCompany();
        $this->denyAccessUnlessGrantedForModule($moduleChecker, $company);

        $document = $documents->findOneForCompany($id, $company);

        if (!$document instanceof FleetDocument) {
            throw new NotFoundHttpException();
        }

        $form = $this->createDocumentForm($document, $company, $vehicles, $drivers);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->applyOwner($document, (string) $form->get('ownerType')->getData())) {
            $uploadedFile = $form->get('file')->getData();

            if ($uploadedFile !== null) {
                $oldStorageKey = $document->getStorageKey();
                $originalFilename = $uploadedFile->getClientOriginalName();
                $mimeType = $uploadedFile->getMimeType();
                $size = $uploadedFile->getSize();
                $document->setStoredFile(
                    $storage->store($uploadedFile, $company),
                    $originalFilename,
                    $mimeType,
                    is_int($size) ? $size : null,
                );
                $storage->remove($oldStorageKey);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Dokument je ažuriran.');

            return $this->redirectAfterSave($document);
        }

        return $this->render('document/edit.html.twig', [
            'document' => $document,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/download', name: 'download', methods: ['GET'])]
    public function download(
        int $id,
        TenantContext $tenantContext,
        ModuleChecker $moduleChecker,
        FleetDocumentRepository $documents,
        DocumentStorage $storage,
    ): Response {
        $company = $tenantContext->requireCompany();
        $this->denyAccessUnlessGrantedForModule($moduleChecker, $company);

        $document = $documents->findOneForCompany($id, $company);

        if (!$document instanceof FleetDocument || $document->getStorageKey() === null) {
            throw new NotFoundHttpException();
        }

        $path = $storage->path($document->getStorageKey());

        if (!is_file($path)) {
            throw new NotFoundHttpException();
        }

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $document->getOriginalFilename() ?: 'dokument',
        );

        return $response;
    }

    #[Route('/{id}/expiry', name: 'update_expiry', methods: ['POST'])]
    public function updateExpiry(
        int $id,
        Request $request,
        TenantContext $tenantContext,
        ModuleChecker $moduleChecker,
        FleetDocumentRepository $documents,
        EntityManagerInterface $entityManager,
    ): Response {
        $company = $tenantContext->requireCompany();
        $this->denyAccessUnlessGrantedForModule($moduleChecker, $company);

        $document = $documents->findOneForCompany($id, $company);

        if (!$document instanceof FleetDocument) {
            throw new NotFoundHttpException();
        }

        if (!$this->isCsrfTokenValid('update_document_expiry_'.$document->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid expiry update token.');
        }

        $validUntil = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $request->request->get('validUntil'));

        if (!$validUntil instanceof \DateTimeImmutable) {
            $this->addFlash('error', 'Unesite ispravan datum isteka.');

            return $this->redirectToRoute('app_dashboard');
        }

        $document->setValidUntil($validUntil);
        $entityManager->flush();

        $this->addFlash('success', 'Datum isteka dokumenta je ažuriran.');

        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        TenantContext $tenantContext,
        ModuleChecker $moduleChecker,
        FleetDocumentRepository $documents,
        EntityManagerInterface $entityManager,
    ): Response {
        $company = $tenantContext->requireCompany();
        $this->denyAccessUnlessGrantedForModule($moduleChecker, $company);

        $document = $documents->findOneForCompany($id, $company);

        if (!$document instanceof FleetDocument) {
            throw new NotFoundHttpException();
        }

        if (!$this->isCsrfTokenValid('delete_document_'.$document->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid delete token.');
        }

        $document->softDelete();
        $entityManager->flush();

        $this->addFlash('success', 'Dokument je obrisan.');

        return $this->redirectToRoute('document_index');
    }

    private function createDocumentForm(
        FleetDocument $document,
        Company $company,
        VehicleRepository $vehicles,
        DriverRepository $drivers,
    ): \Symfony\Component\Form\FormInterface {
        return $this->createForm(FleetDocumentType::class, $document, [
            'vehicles' => $vehicles->findForCompany($company),
            'drivers' => $drivers->findForCompany($company),
        ]);
    }

    private function applyOwner(FleetDocument $document, string $ownerType): bool
    {
        if ($ownerType === FleetDocument::OWNER_DRIVER) {
            $driver = $document->getDriver();

            if (!$driver instanceof Driver) {
                $this->addFlash('error', 'Odaberite vozača za ovaj dokument.');

                return false;
            }

            $document->setVehicle(null);

            return true;
        }

        $vehicle = $document->getVehicle();

        if (!$vehicle instanceof Vehicle) {
            $this->addFlash('error', 'Odaberite vozilo za ovaj dokument.');

            return false;
        }

        $document->setDriver(null);

        return true;
    }

    private function redirectAfterSave(FleetDocument $document): Response
    {
        $vehicle = $document->getVehicle();

        if ($vehicle instanceof Vehicle && $vehicle->getId() !== null) {
            return $this->redirectToRoute('vehicle_edit', [
                'id' => $vehicle->getId(),
                '_fragment' => 'vehicle-documents',
            ]);
        }

        $driver = $document->getDriver();

        if ($driver instanceof Driver && $driver->getId() !== null) {
            return $this->redirectToRoute('driver_edit', [
                'id' => $driver->getId(),
                '_fragment' => 'driver-documents',
            ]);
        }

        return $this->redirectToRoute('document_index');
    }

    private function denyAccessUnlessGrantedForModule(ModuleChecker $moduleChecker, Company $company): void
    {
        if (!$moduleChecker->isEnabled($company, Module::DOCUMENTS)) {
            throw $this->createAccessDeniedException('Documents module is disabled.');
        }
    }

    private function isLimitReached(?int $limit, int $currentCount): bool
    {
        return $limit !== null && $currentCount >= $limit;
    }
}
