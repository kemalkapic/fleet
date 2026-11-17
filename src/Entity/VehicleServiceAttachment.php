<?php

namespace App\Entity;

use App\Repository\VehicleServiceAttachmentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehicleServiceAttachmentRepository::class)]
#[ORM\Table(name: 'vehicle_service_attachment')]
class VehicleServiceAttachment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'attachments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?VehicleServiceRecord $serviceRecord = null;

    #[ORM\Column(length: 255)]
    private string $storageKey = '';

    #[ORM\Column(length: 255)]
    private string $originalFilename = '';

    #[ORM\Column(length: 100)]
    private string $mimeType = 'application/octet-stream';

    #[ORM\Column(nullable: true)]
    private ?int $sizeBytes = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getServiceRecord(): ?VehicleServiceRecord { return $this->serviceRecord; }
    public function getStorageKey(): string { return $this->storageKey; }
    public function getOriginalFilename(): string { return $this->originalFilename; }
    public function getMimeType(): string { return $this->mimeType; }
    public function getSizeBytes(): ?int { return $this->sizeBytes; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function setServiceRecord(VehicleServiceRecord $serviceRecord): self { $this->serviceRecord = $serviceRecord; return $this; }

    public function setStoredFile(string $storageKey, string $originalFilename, string $mimeType, ?int $sizeBytes): self
    {
        $this->storageKey = $storageKey;
        $this->originalFilename = $originalFilename;
        $this->mimeType = $mimeType;
        $this->sizeBytes = $sizeBytes;

        return $this;
    }
}
