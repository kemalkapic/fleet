<?php

namespace App\Entity;

use App\Repository\VehicleFuelEntryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehicleFuelEntryRepository::class)]
#[ORM\Table(name: 'vehicle_fuel_entry')]
class VehicleFuelEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Company $company = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Vehicle $vehicle = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $entryDate;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2)]
    private string $liters = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $amountBam = '0.00';

    #[ORM\Column]
    private int $odometerKm = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->entryDate = new \DateTimeImmutable('today');
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

    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle;
    }

    public function setVehicle(Vehicle $vehicle): self
    {
        $this->vehicle = $vehicle;
        $this->touch();

        return $this;
    }

    public function getEntryDate(): \DateTimeImmutable
    {
        return $this->entryDate;
    }

    public function setEntryDate(\DateTimeImmutable $entryDate): self
    {
        $this->entryDate = $entryDate;
        $this->touch();

        return $this;
    }

    public function getLiters(): string
    {
        return $this->liters;
    }

    public function setLiters(string $liters): self
    {
        $this->liters = $this->normalizeDecimal($liters);
        $this->touch();

        return $this;
    }

    public function getAmountBam(): string
    {
        return $this->amountBam;
    }

    public function setAmountBam(string $amountBam): self
    {
        $this->amountBam = $this->normalizeDecimal($amountBam);
        $this->touch();

        return $this;
    }

    public function getOdometerKm(): int
    {
        return $this->odometerKm;
    }

    public function setOdometerKm(int $odometerKm): self
    {
        $this->odometerKm = max(0, $odometerKm);
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

    public function getConsumptionLitersPer100Km(?self $previousEntry): ?float
    {
        if (!$previousEntry instanceof self) {
            return null;
        }

        $distanceKm = $this->odometerKm - $previousEntry->getOdometerKm();

        if ($distanceKm <= 0) {
            return null;
        }

        return ((float) $this->liters / $distanceKm) * 100;
    }

    public function getCostBamPer100Km(?self $previousEntry): ?float
    {
        if (!$previousEntry instanceof self) {
            return null;
        }

        $distanceKm = $this->odometerKm - $previousEntry->getOdometerKm();

        if ($distanceKm <= 0) {
            return null;
        }

        return ((float) $this->amountBam / $distanceKm) * 100;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function normalizeDecimal(string $value): string
    {
        $value = str_replace(',', '.', trim($value));

        return number_format(max(0, (float) $value), 2, '.', '');
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
