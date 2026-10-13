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

#[Route('/drivers/scan', name: 'driver_scan_')]
#[IsGranted('ROLE_COMPANY_ADMIN')]
class DriverScanController extends AbstractController
{
    private const MAX_UPLOAD_SIZE = 8_000_000;

    #[Route('/licence/extract', name: 'licence_extract', methods: ['POST'])]
    public function extractLicence(
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
            $scanResult = $documentExtractor->extractDrivingLicence($temporaryPath);

            return $this->json([
                'fields' => [
                    'firstName' => $scanResult->firstName,
                    'lastName' => $scanResult->lastName,
                    'birthDate' => $scanResult->dateOfBirth?->format('Y-m-d'),
                    'birthPlace' => $scanResult->placeOfBirth,
                    'licenceNumber' => $scanResult->licenceNumber,
                    'licenceIssuedAt' => $scanResult->issuedAt?->format('Y-m-d'),
                    'licenceExpiresAt' => $scanResult->validUntil?->format('Y-m-d'),
                    'licenceIssuedBy' => $scanResult->issuingAuthority,
                    'residenceMunicipality' => $scanResult->residenceMunicipality,
                    'licenceCategories' => implode(', ', $scanResult->categories),
                ],
                'warnings' => $this->humanizeScanWarnings($scanResult->warnings, [
                    'firstName' => $scanResult->firstName,
                    'lastName' => $scanResult->lastName,
                    'dateOfBirth' => $scanResult->dateOfBirth,
                    'placeOfBirth' => $scanResult->placeOfBirth,
                    'licenceNumber' => $scanResult->licenceNumber,
                    'issuedAt' => $scanResult->issuedAt,
                    'validUntil' => $scanResult->validUntil,
                    'issuingAuthority' => $scanResult->issuingAuthority,
                    'residenceMunicipality' => $scanResult->residenceMunicipality,
                    'categories' => $scanResult->categories,
                ]),
            ]);
        } catch (DocumentExtractionException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } finally {
            $temporaryFiles->delete($temporaryPath);
        }
    }

    private function denyAccessUnlessGrantedForModules(ModuleChecker $moduleChecker, Company $company): void
    {
        if (!$moduleChecker->isEnabled($company, Module::DRIVERS) || !$moduleChecker->isEnabled($company, Module::DRIVING_LICENCE_SCAN)) {
            throw $this->createAccessDeniedException('Drivers and driving licence scan modules are required.');
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
                str_contains($warning, 'name') || str_contains($warning, 'ime') => 'Slika nije dovoljno jasna, nisam uspio sigurno pročitati ime ili prezime.',
                str_contains($warning, 'birth') || str_contains($warning, 'rođ') || str_contains($warning, 'rod') => 'Slika nije dovoljno jasna, nisam uspio sigurno pročitati datum ili mjesto rođenja.',
                str_contains($warning, 'licence') || str_contains($warning, 'license') || str_contains($warning, 'number') => 'Slika nije dovoljno jasna, nisam uspio sigurno pročitati broj vozačke dozvole.',
                str_contains($warning, 'categor') || str_contains($warning, 'kategor') => 'Slika nije dovoljno jasna, nisam uspio sigurno pročitati kategorije.',
                str_contains($warning, 'blurry') || str_contains($warning, 'blur') || str_contains($warning, 'quality') => 'Slika nije dovoljno dobrog kvaliteta, provjerite popunjena polja.',
                str_contains($warning, 'partial') || str_contains($warning, 'cropped') || str_contains($warning, 'outside') => 'Dio dokumenta nije vidljiv na slici, provjerite popunjena polja.',
                default => 'Slika nije dovoljno jasna, nisam uspio pročitati sve podatke. Provjerite popunjena polja.',
            };
        }

        if (($fields['firstName'] ?? null) === null || trim((string) ($fields['firstName'] ?? '')) === '' || ($fields['lastName'] ?? null) === null || trim((string) ($fields['lastName'] ?? '')) === '') {
            $messages[] = 'Slika nije dovoljno jasna, nisam uspio sigurno pročitati ime ili prezime.';
        }

        if (($fields['dateOfBirth'] ?? null) === null) {
            $messages[] = 'Slika nije dovoljno jasna, nisam uspio sigurno pročitati datum rođenja.';
        }

        if (($fields['placeOfBirth'] ?? null) === null || trim((string) ($fields['placeOfBirth'] ?? '')) === '') {
            $messages[] = 'Slika nije dovoljno jasna, nisam uspio sigurno pročitati mjesto rođenja.';
        }

        if (($fields['licenceNumber'] ?? null) === null || trim((string) ($fields['licenceNumber'] ?? '')) === '') {
            $messages[] = 'Slika nije dovoljno jasna, nisam uspio sigurno pročitati broj vozačke dozvole.';
        }

        if (($fields['issuedAt'] ?? null) === null || ($fields['validUntil'] ?? null) === null) {
            $messages[] = 'Slika nije dovoljno jasna, nisam uspio sigurno pročitati datume izdavanja ili isteka.';
        }

        if (($fields['issuingAuthority'] ?? null) === null || trim((string) ($fields['issuingAuthority'] ?? '')) === '') {
            $messages[] = 'Slika nije dovoljno jasna, nisam uspio sigurno pročitati ko je izdao vozačku.';
        }

        if (($fields['residenceMunicipality'] ?? null) === null || trim((string) ($fields['residenceMunicipality'] ?? '')) === '') {
            $messages[] = 'Slika nije dovoljno jasna, nisam uspio sigurno pročitati općinu prebivališta.';
        }

        if (($fields['categories'] ?? []) === []) {
            $messages[] = 'Slika nije dovoljno jasna, nisam uspio sigurno pročitati kategorije.';
        }

        return array_values(array_unique($messages));
    }
}
