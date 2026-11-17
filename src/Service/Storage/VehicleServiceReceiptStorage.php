<?php

namespace App\Service\Storage;

use App\Entity\Company;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class VehicleServiceReceiptStorage
{
    public function __construct(private string $projectDir)
    {
    }

    public function store(UploadedFile $file, Company $company): string
    {
        $companyId = $company->getId();

        if ($companyId === null) {
            throw new FileException('Company must be saved before receipt upload.');
        }

        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $storageKey = $companyId.'/'.bin2hex(random_bytes(16)).'.'.$extension;
        $targetDirectory = $this->storageRoot().DIRECTORY_SEPARATOR.$companyId;

        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
            throw new FileException('Service receipt storage directory could not be created.');
        }

        $file->move($targetDirectory, basename($storageKey));

        return $storageKey;
    }

    public function path(string $storageKey): string
    {
        return $this->storageRoot().DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storageKey);
    }

    public function remove(?string $storageKey): void
    {
        if ($storageKey === null) {
            return;
        }

        $path = $this->path($storageKey);

        if (is_file($path)) {
            unlink($path);
        }
    }

    private function storageRoot(): string
    {
        return $this->projectDir.DIRECTORY_SEPARATOR.'var'.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'service-receipts';
    }
}
