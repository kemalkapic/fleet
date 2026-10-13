<?php

namespace App\Service\DocumentScan;

interface DocumentDataExtractorInterface
{
    public function extractDrivingLicence(string $imagePath): DrivingLicenceScanResult;

    public function extractVehicleRegistration(string $imagePath): VehicleRegistrationScanResult;
}
