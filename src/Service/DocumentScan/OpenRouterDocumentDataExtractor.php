<?php

namespace App\Service\DocumentScan;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenRouterDocumentDataExtractor implements DocumentDataExtractorInterface
{
    private const DRIVING_LICENCE = 'driving_licence';
    private const VEHICLE_REGISTRATION = 'vehicle_registration';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(OPENROUTER_API_KEY)%')]
        private readonly string $apiKey,
        #[Autowire('%env(OPENROUTER_MODEL)%')]
        private readonly string $model,
        #[Autowire('%env(OPENROUTER_ENDPOINT)%')]
        private readonly string $endpoint,
        #[Autowire('%app.name%')]
        private readonly string $appName,
    ) {
    }

    public function extractDrivingLicence(string $imagePath): DrivingLicenceScanResult
    {
        $payload = $this->extract(self::DRIVING_LICENCE, $imagePath, $this->drivingLicenceInstructions());
        $this->assertDocumentType($payload, self::DRIVING_LICENCE);
        $data = $this->data($payload);

        return new DrivingLicenceScanResult(
            firstName: $this->nullableString($data['firstName'] ?? null),
            lastName: $this->nullableString($data['lastName'] ?? null),
            dateOfBirth: $this->nullableDate($data['dateOfBirth'] ?? null),
            placeOfBirth: $this->nullableString($data['placeOfBirth'] ?? null),
            licenceNumber: $this->nullableString($data['licenceNumber'] ?? null),
            issuedAt: $this->nullableDate($data['issuedAt'] ?? null),
            validUntil: $this->nullableDate($data['validUntil'] ?? null),
            issuingAuthority: $this->nullableString($data['issuingAuthority'] ?? null),
            residenceMunicipality: $this->nullableString($data['residenceMunicipality'] ?? null),
            categories: $this->stringList($data['categories'] ?? []),
            warnings: $this->warningList($payload),
        );
    }

    public function extractVehicleRegistration(string $imagePath): VehicleRegistrationScanResult
    {
        $payload = $this->extract(self::VEHICLE_REGISTRATION, $imagePath, $this->vehicleRegistrationInstructions());
        $this->assertDocumentType($payload, self::VEHICLE_REGISTRATION);
        $data = $this->data($payload);

        return new VehicleRegistrationScanResult(
            registrationNumber: $this->nullableString($data['registrationNumber'] ?? null),
            vin: $this->nullableVin($data['vin'] ?? null),
            manufacturer: $this->nullableString($data['manufacturer'] ?? null),
            model: $this->nullableString($data['model'] ?? null),
            vehicleType: $this->nullableString($data['vehicleType'] ?? null),
            productionYear: $this->nullableYear($data['productionYear'] ?? null),
            engineDisplacement: $this->nullablePositiveInt($data['engineDisplacement'] ?? null),
            enginePowerKw: $this->nullablePositiveInt($data['enginePowerKw'] ?? null),
            fuelType: $this->nullableString($data['fuelType'] ?? null),
            euroStandard: $this->nullableString($data['euroStandard'] ?? null),
            warnings: $this->warningList($payload),
            rawData: $this->publicScanData($data),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function extract(string $documentType, string $imagePath, string $instructions): array
    {
        if ($this->apiKey === '' || $this->model === '') {
            throw new DocumentExtractionException('Čitanje dokumenta nije podešeno. Nedostaje OpenRouter konfiguracija.');
        }

        $dataUri = $this->imageDataUri($imagePath);

        try {
            $response = $this->httpClient->request('POST', $this->endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => 'http://localhost:8000',
                    'X-Title' => $this->appName,
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You extract structured JSON from document images. Return JSON only, with no markdown or prose. Never invent missing values.',
                        ],
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => $instructions,
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => $dataUri,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'temperature' => 0,
                ],
                'timeout' => 60,
            ]);

            $statusCode = $response->getStatusCode();
            $responseData = $response->toArray(false);
        } catch (TransportExceptionInterface) {
            throw new DocumentExtractionException('Servis za čitanje dokumenta trenutno nije dostupan. Pokušajte ponovo.');
        } catch (DecodingExceptionInterface) {
            throw new DocumentExtractionException('Servis za čitanje dokumenta je vratio neispravan odgovor. Pokušajte ponovo.');
        }

        if ($statusCode >= 400) {
            throw new DocumentExtractionException($this->openRouterErrorMessage($statusCode, $responseData ?? []));
        }

        $content = $responseData['choices'][0]['message']['content'] ?? null;

        if (!is_string($content) || trim($content) === '') {
            throw new DocumentExtractionException('Model nije vratio čitljive podatke. Pokušajte ponovo sa jasnijom slikom.');
        }

        return $this->decodeJsonContent($content, $documentType);
    }

    private function imageDataUri(string $imagePath): string
    {
        if (!is_file($imagePath) || !is_readable($imagePath)) {
            throw new DocumentExtractionException('Slika dokumenta nije dostupna.');
        }

        $mimeType = mime_content_type($imagePath) ?: '';

        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new DocumentExtractionException('Podržane su samo JPG, PNG i WEBP slike.');
        }

        $contents = file_get_contents($imagePath);

        if ($contents === false) {
            throw new DocumentExtractionException('Slika dokumenta nije dostupna.');
        }

        return sprintf('data:%s;base64,%s', $mimeType, base64_encode($contents));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonContent(string $content, string $expectedDocumentType): array
    {
        $content = trim($content);
        $content = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $content) ?? $content;
        $payload = json_decode(trim($content), true);

        if (!is_array($payload)) {
            throw new DocumentExtractionException('Model je vratio neispravan format podataka. Pokušajte ponovo.');
        }

        if (($payload['documentType'] ?? null) !== $expectedDocumentType) {
            throw new DocumentExtractionException('Slika ne izgleda kao očekivani dokument.');
        }

        if (!isset($payload['data']) || !is_array($payload['data'])) {
            throw new DocumentExtractionException('Model nije vratio podatke dokumenta.');
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function assertDocumentType(array $payload, string $expectedDocumentType): void
    {
        if (($payload['documentType'] ?? null) !== $expectedDocumentType) {
            throw new DocumentExtractionException('Slika ne izgleda kao očekivani dokument.');
        }
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function data(array $payload): array
    {
        return is_array($payload['data'] ?? null) ? $payload['data'] : [];
    }

    private function nullableString(mixed $value): ?string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function nullableDate(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));

        return $date instanceof \DateTimeImmutable ? $date : null;
    }

    private function nullableYear(mixed $value): ?int
    {
        if (!is_int($value) && !(is_string($value) && preg_match('/^\d{4}$/', $value) === 1)) {
            return null;
        }

        $year = (int) $value;

        return $year >= 1900 && $year <= ((int) date('Y') + 1) ? $year : null;
    }

    private function nullablePositiveInt(mixed $value): ?int
    {
        if (is_string($value)) {
            $value = preg_replace('/[^\d]/', '', $value) ?? '';
        }

        if (!is_int($value) && !(is_string($value) && preg_match('/^\d+$/', $value) === 1)) {
            return null;
        }

        $value = (int) $value;

        return $value > 0 ? $value : null;
    }

    private function nullableVin(mixed $value): ?string
    {
        $value = $this->nullableString($value);

        if ($value === null) {
            return null;
        }

        $value = preg_replace('/[^A-HJ-NPR-Z0-9]/', '', mb_strtoupper($value)) ?? mb_strtoupper($value);

        return preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $value) === 1 ? $value : null;
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $item): string => mb_strtoupper(trim((string) $item)),
            $value,
        ))));
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return list<string>
     */
    private function warningList(array $payload): array
    {
        $value = $payload['warnings'] ?? [];

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $item): string => trim((string) $item),
            $value,
        ))));
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function publicScanData(array $data): array
    {
        $publicData = [];

        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if ($value instanceof \DateTimeInterface) {
                $publicData[$key] = $value->format('Y-m-d');
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $publicData[$key] = $value;
                continue;
            }

            if (is_array($value)) {
                $publicData[$key] = implode(', ', array_filter(array_map(
                    static fn (mixed $item): string => trim((string) $item),
                    $value,
                )));
            }
        }

        return $publicData;
    }

    /**
     * @param array<string, mixed> $responseData
     */
    private function openRouterErrorMessage(int $statusCode, array $responseData): string
    {
        $rawMessage = $responseData['error']['message'] ?? '';
        $rawMessage = is_string($rawMessage) ? trim($rawMessage) : '';

        $rawMetadata = $responseData['error']['metadata']['raw'] ?? '';
        $rawMetadata = is_string($rawMetadata) ? trim($rawMetadata) : '';

        $apiMessage = $rawMessage !== '' ? $rawMessage : $rawMetadata;
        $normalizedMessage = mb_strtolower($apiMessage);

        if (str_contains($normalizedMessage, 'privacy') || str_contains($normalizedMessage, 'no endpoints found')) {
            return 'Odabrani AI model trenutno nije dostupan zbog podešavanja privatnosti. Pokušajte kasnije ili promijenite model.';
        }

        if ($statusCode === 401 || $statusCode === 403) {
            return 'Čitanje dokumenta nije odobreno. Provjerite OpenRouter konfiguraciju.';
        }

        if ($statusCode === 408 || $statusCode === 429 || $statusCode >= 500) {
            return 'Servis za čitanje dokumenta je trenutno zauzet. Pokušajte ponovo.';
        }

        if ($statusCode === 400 && $apiMessage !== '') {
            return 'AI servis je odbio zahtjev: '.$this->publicApiErrorMessage($apiMessage);
        }

        if ($apiMessage !== '') {
            return 'AI servis je vratio grešku: '.$this->publicApiErrorMessage($apiMessage);
        }

        return 'Čitanje dokumenta nije uspjelo. Pokušajte ponovo sa jasnijom slikom.';
    }

    private function publicApiErrorMessage(string $message): string
    {
        $message = preg_replace('/\s+/', ' ', trim($message)) ?? trim($message);

        return mb_substr($message, 0, 240);
    }

    private function drivingLicenceInstructions(): string
    {
        return <<<'PROMPT'
Analyze the image as a Bosnia and Herzegovina driving licence.
Return strict JSON only:
{
  "documentType": "driving_licence",
  "data": {
    "firstName": string|null,
    "lastName": string|null,
    "dateOfBirth": "YYYY-MM-DD"|null,
    "placeOfBirth": string|null,
    "licenceNumber": string|null,
    "issuedAt": "YYYY-MM-DD"|null,
    "validUntil": "YYYY-MM-DD"|null,
    "issuingAuthority": string|null,
    "residenceMunicipality": string|null,
    "categories": string[]
  },
  "warnings": string[]
}
Use only visible values. Do not infer hidden or cropped text. Field 1 is surname, field 2 is first name, field 3 contains date and place of birth, field 4a issue date, field 4b expiry date, field 4c issuing authority, field 5 licence number, field 8 residence municipality, field 9 categories. If uncertain, use null and add a warning.
PROMPT;
    }

    private function vehicleRegistrationInstructions(): string
    {
        return <<<'PROMPT'
Analyze the image as a Bosnia and Herzegovina vehicle registration document.

STRICT VIN EXTRACTION PROTOCOL (Zero Tolerance for Error):
The VIN must be exactly 17 characters. To prevent visual confusion between similar characters (like V, B, 8, 0, O), follow this logic:

1. Coordinate-Based Scanning: Scan the VIN field from left to right. For each character, analyze the shape:
   - Check for B vs V: A B has two closed loops; a V is open at the top. If the character has a closed loop, it is B.
   - Check for 8 vs B: Compare the character to the numbers in other fields (like production year). If it matches the shape of a letter, it is B.
2. No Guessing: If a character is slightly blurred, do not guess based on typical patterns. Look at the exact pixels.
3. Verification Loop:
   - First, extract the VIN.
   - Second, compare the extracted VIN character-by-character against the original image.
   - Third, if any character like V or B seems ambiguous, re-verify the shape.

Data Extraction Rules:
- Registration Number: Use hyphens between visible groups (e.g., T96-O-759).
- Technical Data:
  - (P.1) engineDisplacement (number)
  - (P.2) enginePowerKw (number)
  - Fuel type (e.g., DIZEL) fuelType
  - Emission standard (e.g., EURO 4) euroStandard
- Numbers: Remove units (kW, cm3) and return as numbers.

Strict JSON Output:
Return ONLY the JSON object.

{
  "documentType": "vehicle_registration",
  "data": {
    "registrationNumber": string|null,
    "vin": string|null,
    "manufacturer": string|null,
    "model": string|null,
    "vehicleType": string|null,
    "productionYear": number|null,
    "engineDisplacement": number|null,
    "enginePowerKw": number|null,
    "fuelType": string|null,
    "euroStandard": string|null
  },
  "warnings": string[]
}

Language for Warnings: All warnings must be in Bosnian.
PROMPT;
    }
}
