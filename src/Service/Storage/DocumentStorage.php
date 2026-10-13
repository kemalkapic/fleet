<?php

namespace App\Service\Storage;

use App\Entity\Company;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class DocumentStorage
{
    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function store(UploadedFile $file, Company $company): string
    {
        $companyId = $company->getId();

        if ($companyId === null) {
            throw new FileException('Company must be saved before document upload.');
        }

        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $storageKey = $companyId.'/'.bin2hex(random_bytes(16)).'.'.$extension;
        $targetDir = $this->getStorageRoot().DIRECTORY_SEPARATOR.(string) $companyId;

        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new FileException('Document storage directory could not be created.');
        }

        $file->move($targetDir, basename($storageKey));

        return $storageKey;
    }

    public function path(string $storageKey): string
    {
        return $this->getStorageRoot().DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storageKey);
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

    private function getStorageRoot(): string
    {
        return $this->projectDir.DIRECTORY_SEPARATOR.'var'.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'documents';
    }
}
