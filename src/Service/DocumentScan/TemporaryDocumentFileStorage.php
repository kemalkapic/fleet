<?php

namespace App\Service\DocumentScan;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TemporaryDocumentFileStorage
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/var/storage/tmp/document-scan')]
        private readonly string $directory,
    ) {
    }

    public function store(UploadedFile $file): string
    {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0775, true) && !is_dir($this->directory)) {
            throw new DocumentExtractionException('Privremeni folder za dokumente nije dostupan.');
        }

        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $filename = bin2hex(random_bytes(16)).'.'.$extension;
        $file->move($this->directory, $filename);

        return $this->directory.'/'.$filename;
    }

    public function delete(string $filePath): void
    {
        if (is_file($filePath)) {
            @unlink($filePath);
        }
    }
}
