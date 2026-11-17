<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\Vehicle;
use App\Entity\VehicleServiceAttachment;
use App\Entity\VehicleServiceRecord;
use App\Enum\Module;
use App\Repository\VehicleRepository;
use App\Repository\VehicleServiceAttachmentRepository;
use App\Repository\VehicleServiceRecordRepository;
use App\Service\Module\ModuleChecker;
use App\Service\Storage\VehicleServiceReceiptStorage;
use App\Service\Tenant\TenantContext;
use App\Service\Vehicle\VehicleServiceCatalog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/vehicles', name: 'vehicle_service_')]
#[IsGranted('ROLE_END_USER')]
class VehicleServiceController extends AbstractController
{
    private const MAX_RECEIPTS = 5;
    private const MAX_RECEIPT_SIZE = 5_242_880;
    private const ALLOWED_RECEIPT_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

    #[Route('/{vehicleId}/services', name: 'add', methods: ['POST'])]
    public function add(
        int $vehicleId,
        Request $request,
        TenantContext $tenantContext,
        ModuleChecker $moduleChecker,
        VehicleRepository $vehicles,
        VehicleServiceCatalog $catalog,
        VehicleServiceReceiptStorage $storage,
        EntityManagerInterface $entityManager,
    ): Response {
        $company = $tenantContext->requireCompany();
        $this->denyAccessUnlessGrantedForModule($moduleChecker, $company);
        $vehicle = $this->requireVehicle($vehicleId, $company, $vehicles);

        if (!$this->isCsrfTokenValid('add_vehicle_service_'.$vehicle->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid vehicle service token.');
        }

        $type = (string) $request->request->get('type');
        $serviceDate = $this->parseDate((string) $request->request->get('serviceDate'));
        $odometerKm = $request->request->getInt('odometerKm');
        $types = $catalog->types();

        if (!array_key_exists($type, $types) || !$serviceDate instanceof \DateTimeImmutable || $odometerKm <= 0) {
            $this->addFlash('error', 'Odaberite vrstu servisa te unesite ispravan datum i kilometražu.');

            return $this->redirectToServiceTab($vehicle);
        }

        $receipts = $this->receiptFiles($request);
        $fileError = $this->validateReceipts($receipts);

        if ($fileError !== null) {
            $this->addFlash('error', $fileError);

            return $this->redirectToServiceTab($vehicle);
        }

        $submittedItems = $request->request->all('items');
        $record = (new VehicleServiceRecord())
            ->setCompany($company)
            ->setVehicle($vehicle)
            ->setType($type)
            ->setServiceDate($serviceDate)
            ->setOdometerKm($odometerKm)
            ->setServiceProvider((string) $request->request->get('serviceProvider'))
            ->setCostBam((string) $request->request->get('costBam'))
            ->setItems($catalog->validSelectedItems($type, is_array($submittedItems) ? $submittedItems : []))
            ->setAdditionalWork((string) $request->request->get('additionalWork'))
            ->setNotes((string) $request->request->get('notes'));

        foreach ($receipts as $receipt) {
            $size = $receipt->getSize();
            $attachment = (new VehicleServiceAttachment())->setStoredFile(
                $storage->store($receipt, $company),
                $receipt->getClientOriginalName(),
                $receipt->getMimeType() ?: 'application/octet-stream',
                is_int($size) ? $size : null,
            );
            $record->addAttachment($attachment);
        }

        if ($vehicle->getCurrentMileage() === null || $odometerKm > $vehicle->getCurrentMileage()) {
            $vehicle->setCurrentMileage($odometerKm);
        }

        $entityManager->persist($record);
        $entityManager->flush();

        $this->addFlash('success', 'Servis je dodan u servisnu knjigu.');

        return $this->redirectToServiceTab($vehicle);
    }

    #[Route('/{vehicleId}/services/{serviceId}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        int $vehicleId,
        int $serviceId,
        Request $request,
        TenantContext $tenantContext,
        ModuleChecker $moduleChecker,
        VehicleRepository $vehicles,
        VehicleServiceRecordRepository $serviceRecords,
        VehicleServiceReceiptStorage $storage,
        EntityManagerInterface $entityManager,
    ): Response {
        $company = $tenantContext->requireCompany();
        $this->denyAccessUnlessGrantedForModule($moduleChecker, $company);
        $vehicle = $this->requireVehicle($vehicleId, $company, $vehicles);
        $record = $serviceRecords->findOneForVehicle($serviceId, $vehicle, $company);

        if (!$record instanceof VehicleServiceRecord) {
            throw new NotFoundHttpException();
        }

        if (!$this->isCsrfTokenValid('delete_vehicle_service_'.$record->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid vehicle service delete token.');
        }

        foreach ($record->getAttachments() as $attachment) {
            $storage->remove($attachment->getStorageKey());
        }

        $entityManager->remove($record);
        $entityManager->flush();
        $this->addFlash('success', 'Servis je obrisan iz servisne knjige.');

        return $this->redirectToServiceTab($vehicle);
    }

    #[Route('/service-receipts/{id}/download', name: 'receipt_download', methods: ['GET'])]
    public function downloadReceipt(
        int $id,
        TenantContext $tenantContext,
        ModuleChecker $moduleChecker,
        VehicleServiceAttachmentRepository $attachments,
        VehicleServiceReceiptStorage $storage,
    ): Response {
        $company = $tenantContext->requireCompany();
        $this->denyAccessUnlessGrantedForModule($moduleChecker, $company);
        $attachment = $attachments->findOneForCompany($id, $company);

        if (!$attachment instanceof VehicleServiceAttachment) {
            throw new NotFoundHttpException();
        }

        $path = $storage->path($attachment->getStorageKey());

        if (!is_file($path)) {
            throw new NotFoundHttpException();
        }

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $attachment->getOriginalFilename());

        return $response;
    }

    private function requireVehicle(int $id, Company $company, VehicleRepository $vehicles): Vehicle
    {
        $vehicle = $vehicles->findOneForCompany($id, $company);

        if (!$vehicle instanceof Vehicle) {
            throw new NotFoundHttpException();
        }

        return $vehicle;
    }

    private function denyAccessUnlessGrantedForModule(ModuleChecker $moduleChecker, Company $company): void
    {
        if (!$moduleChecker->isEnabled($company, Module::VEHICLES)) {
            throw $this->createAccessDeniedException('Vehicles module is disabled.');
        }
    }

    private function redirectToServiceTab(Vehicle $vehicle): Response
    {
        return $this->redirectToRoute('vehicle_edit', [
            'id' => $vehicle->getId(),
            '_fragment' => 'vehicle-services',
        ]);
    }

    private function parseDate(string $value): ?\DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = \DateTimeImmutable::getLastErrors();

        if (!$date instanceof \DateTimeImmutable || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }

        return $date;
    }

    /** @return list<UploadedFile> */
    private function receiptFiles(Request $request): array
    {
        return array_values(array_filter(
            $request->files->all('receipts'),
            static fn (mixed $file): bool => $file instanceof UploadedFile,
        ));
    }

    /** @param list<UploadedFile> $receipts */
    private function validateReceipts(array $receipts): ?string
    {
        if (count($receipts) > self::MAX_RECEIPTS) {
            return 'Možete dodati najviše 5 računa po servisu.';
        }

        foreach ($receipts as $receipt) {
            if (!$receipt->isValid()) {
                return 'Jedan od odabranih računa nije moguće učitati.';
            }

            if (($receipt->getSize() ?: 0) > self::MAX_RECEIPT_SIZE) {
                return 'Svaki račun može imati najviše 5 MB.';
            }

            if (!in_array($receipt->getMimeType(), self::ALLOWED_RECEIPT_TYPES, true)) {
                return 'Račun mora biti JPG, PNG, WebP ili PDF datoteka.';
            }
        }

        return null;
    }
}
