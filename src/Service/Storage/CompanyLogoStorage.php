<?php

namespace App\Service\Storage;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class CompanyLogoStorage
{
    private const PUBLIC_DIRECTORY = 'uploads/company-logos';

    public function __construct(
        private string $projectDir,
    ) {
    }

    public function store(UploadedFile $file): string
    {
        $extension = $file->guessExtension() ?: 'bin';
        $filename = bin2hex(random_bytes(16)).'.'.$extension;
        $targetDirectory = $this->getStorageRoot();

        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
            throw new FileException('Direktorij za logo firme nije moguće kreirati.');
        }

        $file->move($targetDirectory, $filename);

        return self::PUBLIC_DIRECTORY.'/'.$filename;
    }

    public function remove(?string $logoPath): void
    {
        if ($logoPath === null || !str_starts_with($logoPath, self::PUBLIC_DIRECTORY.'/')) {
            return;
        }

        $path = $this->projectDir.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.str_replace(
            ['/', '\\'],
            DIRECTORY_SEPARATOR,
            $logoPath,
        );

        if (is_file($path)) {
            unlink($path);
        }
    }

    private function getStorageRoot(): string
    {
        return $this->projectDir.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.str_replace(
            '/',
            DIRECTORY_SEPARATOR,
            self::PUBLIC_DIRECTORY,
        );
    }
}
