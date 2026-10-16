<?php

namespace App\Entity;

use App\Repository\FleetDocumentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FleetDocumentRepository::class)]
#[ORM\Table(name: 'fleet_document')]
class FleetDocument
{
    public const OWNER_VEHICLE = 'vehicle';
    public const OWNER_DRIVER = 'driver';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Company $company = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Vehicle $vehicle = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Driver $driver = null;

    #[ORM\Column(length: 80)]
    private string $type = '';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $documentNumber = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $validFrom = null;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $validUntil;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $storageKey = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $originalFilename = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mimeType = null;

    #[ORM\Column(nullable: true)]
    private ?int $sizeBytes = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->validUntil = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): self
    {
        $this->company = $company;
        $this->touch();

        return $this;
    }

    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle;
    }

    public function setVehicle(?Vehicle $vehicle): self
    {
        $this->vehicle = $vehicle;
        $this->touch();

        return $this;
    }

    public function getDriver(): ?Driver
    {
        return $this->driver;
    }

    public function setDriver(?Driver $driver): self
    {
        $this->driver = $driver;
        $this->touch();

        return $this;
    }

    public function getOwnerType(): string
    {
        return $this->driver instanceof Driver ? self::OWNER_DRIVER : self::OWNER_VEHICLE;
    }

    public function getOwnerLabel(): string
    {
        if ($this->vehicle instanceof Vehicle) {
            return $this->vehicle->getRegistrationNumber();
        }

        if ($this->driver instanceof Driver) {
            return $this->driver->getFullName();
        }

        return '-';
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'registration' => 'Registracija',
            'periodic_inspection_3_months' => 'Periodički tehnički 3 mjeseca',
            'periodic_inspection_4_months' => 'Periodični tehnički 4 mjeseca',
            'periodic_inspection_6_months' => 'Periodični tehnički 6 mjeseci',
            'insurance' => 'Osiguranje',
            'attest' => 'Atest',
            'driving_licence' => 'Vozačka dozvola',
            'driver_card' => 'Kartica vozača - taho kartica',
            'medical_certificate' => 'Ljekarsko uvjerenje',
            'licence' => 'Licenca',
            'fire_extinguisher' => 'Protupožarni aparat',
            'first_aid' => 'Prva pomoć',
            'service' => 'Servis',
            'contract' => 'Ugovor',
            default => 'Ostalo',
        };
    }

    public function setType(string $type): self
    {
        $this->type = trim($type);
        $this->touch();

        return $this;
    }

    public function getDocumentNumber(): ?string
    {
        return $this->documentNumber;
    }

    public function setDocumentNumber(?string $documentNumber): self
    {
        $documentNumber = $documentNumber !== null ? trim($documentNumber) : null;
        $this->documentNumber = $documentNumber !== '' ? $documentNumber : null;
        $this->touch();

        return $this;
    }

    public function getValidFrom(): ?\DateTimeImmutable
    {
        return $this->validFrom;
    }

    public function setValidFrom(?\DateTimeImmutable $validFrom): self
    {
        $this->validFrom = $validFrom;
        $this->touch();

        return $this;
    }

    public function getValidUntil(): \DateTimeImmutable
    {
        return $this->validUntil;
    }

    public function setValidUntil(\DateTimeImmutable $validUntil): self
    {
        $this->validUntil = $validUntil;
        $this->touch();

        return $this;
    }

    public function getStorageKey(): ?string
    {
        return $this->storageKey;
    }

    public function setStoredFile(string $storageKey, string $originalFilename, ?string $mimeType, ?int $sizeBytes): self
    {
        $this->storageKey = $storageKey;
        $this->originalFilename = $originalFilename;
        $this->mimeType = $mimeType;
        $this->sizeBytes = $sizeBytes;
        $this->touch();

        return $this;
    }

    public function clearStoredFile(): self
    {
        $this->storageKey = null;
        $this->originalFilename = null;
        $this->mimeType = null;
        $this->sizeBytes = null;
        $this->touch();

        return $this;
    }

    public function getOriginalFilename(): ?string
    {
        return $this->originalFilename;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function getSizeBytes(): ?int
    {
        return $this->sizeBytes;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $notes = $notes !== null ? trim($notes) : null;
        $this->notes = $notes !== '' ? $notes : null;
        $this->touch();

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function softDelete(): self
    {
        $now = new \DateTimeImmutable();
        $this->deletedAt = $now;
        $this->updatedAt = $now;

        return $this;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
