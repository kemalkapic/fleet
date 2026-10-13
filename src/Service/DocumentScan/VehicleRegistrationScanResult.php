<?php

namespace App\Service\DocumentScan;

final readonly class VehicleRegistrationScanResult
{
    /**
     * @param list<string> $warnings
     * @param array<string, mixed> $rawData
     */
    public function __construct(
        public ?string $registrationNumber = null,
        public ?string $vin = null,
        public ?string $manufacturer = null,
        public ?string $model = null,
        public ?string $vehicleType = null,
        public ?int $productionYear = null,
        public ?int $engineDisplacement = null,
        public ?int $enginePowerKw = null,
        public ?string $fuelType = null,
        public ?string $euroStandard = null,
        public array $warnings = [],
        public array $rawData = [],
    ) {
    }
}
