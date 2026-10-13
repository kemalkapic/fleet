<?php

namespace App\Service\DocumentScan;

final readonly class DrivingLicenceScanResult
{
    /**
     * @param list<string> $categories
     * @param list<string> $warnings
     */
    public function __construct(
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?\DateTimeImmutable $dateOfBirth = null,
        public ?string $placeOfBirth = null,
        public ?string $licenceNumber = null,
        public ?\DateTimeImmutable $issuedAt = null,
        public ?\DateTimeImmutable $validUntil = null,
        public ?string $issuingAuthority = null,
        public ?string $residenceMunicipality = null,
        public array $categories = [],
        public array $warnings = [],
    ) {
    }
}
