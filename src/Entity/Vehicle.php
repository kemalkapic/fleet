<?php

namespace App\Entity;

use App\Repository\VehicleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehicleRepository::class)]
class Vehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Company $company = null;

    #[ORM\Column(length: 50)]
    private string $registrationNumber = '';

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $vin = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $manufacturer = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $model = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $vehicleType = null;

    #[ORM\Column(nullable: true)]
    private ?int $productionYear = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $registrationValidUntil = null;

    #[ORM\Column(nullable: true)]
    private ?int $engineDisplacement = null;

    #[ORM\Column(nullable: true)]
    private ?int $enginePowerKw = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $fuelType = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $euroStandard = null;

    #[ORM\Column(nullable: true)]
    private ?int $currentMileage = null;

    #[ORM\Column(length: 30)]
    private string $status = 'active';

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

    public function getRegistrationNumber(): string
    {
        return $this->registrationNumber;
    }

    public function setRegistrationNumber(string $registrationNumber): self
    {
        $this->registrationNumber = mb_strtoupper(trim($registrationNumber));
        $this->touch();

        return $this;
    }

    public function getVin(): ?string
    {
        return $this->vin;
    }

    public function setVin(?string $vin): self
    {
        $vin = $vin !== null ? trim($vin) : null;
        $this->vin = $vin !== '' ? mb_strtoupper($vin) : null;
        $this->touch();

        return $this;
    }

    public function getManufacturer(): ?string
    {
        return $this->manufacturer;
    }

    public function setManufacturer(?string $manufacturer): self
    {
        $manufacturer = $manufacturer !== null ? trim($manufacturer) : null;
        $this->manufacturer = $manufacturer !== '' ? $manufacturer : null;
        $this->touch();

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): self
    {
        $model = $model !== null ? trim($model) : null;
        $this->model = $model !== '' ? $model : null;
        $this->touch();

        return $this;
    }

    public function getVehicleType(): ?string
    {
        return $this->vehicleType;
    }

    public function setVehicleType(?string $vehicleType): self
    {
        $vehicleType = $vehicleType !== null ? trim($vehicleType) : null;
        $this->vehicleType = $vehicleType !== '' ? $vehicleType : null;
        $this->touch();

        return $this;
    }

    public function getProductionYear(): ?int
    {
        return $this->productionYear;
    }

    public function setProductionYear(?int $productionYear): self
    {
        $this->productionYear = $productionYear;
        $this->touch();

        return $this;
    }

    public function getRegistrationValidUntil(): ?\DateTimeImmutable
    {
        return $this->registrationValidUntil;
    }

    public function setRegistrationValidUntil(?\DateTimeImmutable $registrationValidUntil): self
    {
        $this->registrationValidUntil = $registrationValidUntil;
        $this->touch();

        return $this;
    }

    public function getEngineDisplacement(): ?int
    {
        return $this->engineDisplacement;
    }

    public function setEngineDisplacement(?int $engineDisplacement): self
    {
        $this->engineDisplacement = $engineDisplacement;
        $this->touch();

        return $this;
    }

    public function getEnginePowerKw(): ?int
    {
        return $this->enginePowerKw;
    }

    public function setEnginePowerKw(?int $enginePowerKw): self
    {
        $this->enginePowerKw = $enginePowerKw;
        $this->touch();

        return $this;
    }

    public function getFuelType(): ?string
    {
        return $this->fuelType;
    }

    public function setFuelType(?string $fuelType): self
    {
        $fuelType = $fuelType !== null ? trim($fuelType) : null;
        $this->fuelType = $fuelType !== '' ? mb_strtoupper($fuelType) : null;
        $this->touch();

        return $this;
    }

    public function getEuroStandard(): ?string
    {
        return $this->euroStandard;
    }

    public function setEuroStandard(?string $euroStandard): self
    {
        $euroStandard = $euroStandard !== null ? trim($euroStandard) : null;
        $this->euroStandard = $euroStandard !== '' ? mb_strtoupper($euroStandard) : null;
        $this->touch();

        return $this;
    }

    public function getCurrentMileage(): ?int
    {
        return $this->currentMileage;
    }

    public function setCurrentMileage(?int $currentMileage): self
    {
        $this->currentMileage = $currentMileage;
        $this->touch();

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        $this->touch();

        return $this;
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
