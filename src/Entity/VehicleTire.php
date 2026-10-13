<?php

namespace App\Entity;

use App\Repository\VehicleTireRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehicleTireRepository::class)]
#[ORM\Table(name: 'vehicle_tire')]
class VehicleTire
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

    #[ORM\Column(length: 120)]
    private string $name = '';

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $dot = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $position = null;

    #[ORM\Column]
    private bool $onVehicle = false;

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = trim($name);
        $this->touch();

        return $this;
    }

    public function getDot(): ?string
    {
        return $this->dot;
    }

    public function setDot(?string $dot): self
    {
        $dot = $dot !== null ? trim($dot) : null;
        $this->dot = $dot !== '' ? $dot : null;
        $this->touch();

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): self
    {
        $position = $position !== null ? trim($position) : null;
        $this->position = $position !== '' ? $position : null;
        $this->touch();

        return $this;
    }

    public function isOnVehicle(): bool
    {
        return $this->onVehicle;
    }

    public function setOnVehicle(bool $onVehicle): self
    {
        $this->onVehicle = $onVehicle;
        $this->touch();

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;
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

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
