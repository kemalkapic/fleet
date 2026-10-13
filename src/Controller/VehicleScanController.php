<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\User;
use App\Enum\Module;
use App\Service\DocumentScan\DocumentDataExtractorInterface;
use App\Service\DocumentScan\DocumentExtractionException;
use App\Service\DocumentScan\DocumentScanRateLimiter;
use App\Service\DocumentScan\TemporaryDocumentFileStorage;
use App\Service\Module\ModuleChecker;
use App\Service\Tenant\TenantContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/vehicles/scan', name: 'vehicle_scan_')]
#[IsGranted('ROLE_COMPANY_ADMIN')]
class VehicleScanController extends AbstractController
{
    private const MAX_UPLOAD_SIZE = 8_000_000;

    #[Route('/registration/extract', name: 'registration_extract', methods: ['POST'])]
    public function extractRegistration(
        Request $request,
        TenantContext $tenantContext,
        ModuleChecker $moduleChecker,
        DocumentScanRateLimiter $rateLimiter,
        TemporaryDocumentFileStorage $temporaryFiles,
        DocumentDataExtractorInterface $documentExtractor,
    ): JsonResponse {
        $company = $tenantContext->requireCompany();
        $this->denyAccessUnlessGrantedForModules($moduleChecker, $company);

        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $rateLimiter->consumeForUser($user);

        $file = $request->files->get('document');

        if (!$file instanceof UploadedFile) {
            return $this->json(['message' => 'Odaberite sliku dokumenta.'], Response::HTTP_BAD_REQUEST);
        }

        if ($file->getSize() !== false && $file->getSize() > self::MAX_UPLOAD_SIZE) {
            return $this->json(['message' => 'Slika je prevelika. Maksimalna veličina je 8 MB.'], Response::HTTP_BAD_REQUEST);
        }

        if (!in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return $this->json(['message' => 'Podržane su samo JPG, PNG i WEBP slike.'], Response::HTTP_BAD_REQUEST);
        }

        $temporaryPath = $temporaryFiles->store($file);

        try {
            $scanResult = $documentExtractor->extractVehicleRegistration($temporaryPath);

            return $this->json([
                'fields' => [
                    'registrationNumber' => $scanResult->registrationNumber,
                    'vin' => $scanResult->vin,
                    'manufacturer' => $scanResult->manufacturer,
                    'model' => $scanResult->model,
                    'vehicleType' => $scanResult->vehicleType,
                    'productionYear' => $scanResult->productionYear,
                    'engineDisplacement' => $scanResult->engineDisplacement,
                    'enginePowerKw' => $scanResult->enginePowerKw,
                    'fuelType' => $scanResult->fuelType,
                    'euroStandard' => $scanResult->euroStandard,
                ],
                'warnings' => $this->humanizeScanWarnings($scanResult->warnings, [
                    'registrationNumber' => $scanResult->registrationNumber,
                    'vin' => $scanResult->vin,
                    'manufacturer' => $scanResult->manufacturer,
                    'model' => $scanResult->model,
                    'productionYear' => $scanResult->productionYear,
                ]),
                'readFields' => $this->humanizeReadFields($scanResult->rawData),
            ]);
        } catch (DocumentExtractionException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } finally {
            $temporaryFiles->delete($temporaryPath);
        }
    }

    private function denyAccessUnlessGrantedForModules(ModuleChecker $moduleChecker, Company $company): void
    {
        if (!$moduleChecker->isEnabled($company, Module::VEHICLES) || !$moduleChecker->isEnabled($company, Module::VEHICLE_REGISTRATION_SCAN)) {
            throw $this->createAccessDeniedException('Vehicles and vehicle registration scan modules are required.');
        }
    }

    /**
     * @param list<string> $warnings
     *
     * @return list<string>
     */
    /**
     * @param array<string, mixed> $fields
     */
    private function humanizeScanWarnings(array $warnings, array $fields = []): array
    {
        $messages = [];

        foreach ($warnings as $warning) {
            $warning = mb_strtolower($warning);

            $messages[] = match (true) {
                str_contains($warning, 'vin') => 'Slika nije dovoljno jasna, nisam uspio sigurno pročitati VIN.',
                str_contains($warning, 'registration') || str_contains($warning, 'registr') => 'Slika nije dovoljno jasna, nisam uspio sigurno pročitati registraciju.',
                str_contains($warning, 'blurry') || str_contains($warning, 'blur') || str_contains($warning, 'quality') => 'Slika nije dovoljno dobrog kvaliteta, provjerite popunjena polja.',
                str_contains($warning, 'partial') || str_contains($warning, 'cropped') || str_contains($warning, 'outside') => 'Dio dokumenta nije vidljiv na slici, provjerite popunjena polja.',
                default => 'Slika nije dovoljno jasna, nisam uspio pročitati sve podatke. Provjerite popunjena polja.',
            };
        }

        if (($fields['registrationNumber'] ?? null) === null || trim((string) ($fields['registrationNumber'] ?? '')) === '') {
            $messages[] = 'Slika nije dovoljno jasna, nisam uspio sigurno pročitati registraciju.';
        }

        if (($fields['vin'] ?? null) === null || trim((string) ($fields['vin'] ?? '')) === '') {
            $messages[] = 'Slika nije dovoljno jasna, nisam uspio sigurno pročitati VIN.';
        }

        if (($fields['manufacturer'] ?? null) === null || trim((string) ($fields['manufacturer'] ?? '')) === '') {
            $messages[] = 'Slika nije dovoljno jasna, nisam uspio sigurno pročitati proizvođača.';
        }

        if (($fields['model'] ?? null) === null || trim((string) ($fields['model'] ?? '')) === '') {
            $messages[] = 'Slika nije dovoljno jasna, nisam uspio sigurno pročitati model.';
        }

        if (($fields['productionYear'] ?? null) === null) {
            $messages[] = 'Slika nije dovoljno jasna, nisam uspio sigurno pročitati godinu proizvodnje.';
        }

        return array_values(array_unique($messages));
    }

    /**
     * @param array<string, mixed> $fields
     *
     * @return list<array{label: string, value: string}>
     */
    private function humanizeReadFields(array $fields): array
    {
        $labels = [
            'registrationNumber' => 'Registracija',
            'vin' => 'VIN',
            'manufacturer' => 'Proizvođač',
            'model' => 'Model',
            'vehicleType' => 'Tip vozila',
            'productionYear' => 'Godina proizvodnje',
            'engineDisplacement' => 'Radni obujam',
            'enginePowerKw' => 'Snaga kW',
            'fuelType' => 'Gorivo',
            'euroStandard' => 'EURO norma',
        ];

        $readFields = [];

        foreach ($labels as $key => $label) {
            $value = $fields[$key] ?? null;

            if ($value === null || trim((string) $value) === '') {
                continue;
            }

            $readFields[] = [
                'label' => $label,
                'value' => trim((string) $value),
            ];
        }

        return $readFields;
    }
}
