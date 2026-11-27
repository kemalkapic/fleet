<?php

namespace App\Entity;

use App\Enum\VehicleExpenseType;
use App\Repository\VehicleExpenseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehicleExpenseRepository::class)]
#[ORM\Table(name: 'vehicle_expense')]
class VehicleExpense
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
    private \DateTimeImmutable $expenseDate;

    #[ORM\Column(length: 40)]
    private string $type = VehicleExpenseType::OTHER->value;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $amountBam = '0.00';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->expenseDate = new \DateTimeImmutable('today');
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int { return $this->id; }
    public function getCompany(): ?Company { return $this->company; }
    public function getVehicle(): ?Vehicle { return $this->vehicle; }
    public function getExpenseDate(): \DateTimeImmutable { return $this->expenseDate; }
    public function getType(): string { return $this->type; }
    public function getAmountBam(): string { return $this->amountBam; }
    public function getNotes(): ?string { return $this->notes; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    public function getTypeLabel(): string
    {
        return VehicleExpenseType::tryFrom($this->type)?->label() ?? 'Ostalo';
    }

    public function setCompany(Company $company): self { $this->company = $company; return $this->touch(); }
    public function setVehicle(Vehicle $vehicle): self { $this->vehicle = $vehicle; return $this->touch(); }
    public function setExpenseDate(\DateTimeImmutable $expenseDate): self { $this->expenseDate = $expenseDate; return $this->touch(); }
    public function setType(VehicleExpenseType $type): self { $this->type = $type->value; return $this->touch(); }

    public function setAmountBam(string $amountBam): self
    {
        $amountBam = str_replace(',', '.', trim($amountBam));
        $this->amountBam = number_format(max(0, (float) $amountBam), 2, '.', '');

        return $this->touch();
    }

    public function setNotes(?string $notes): self
    {
        $notes = $notes !== null ? trim($notes) : null;
        $this->notes = $notes !== '' ? $notes : null;

        return $this->touch();
    }

    private function touch(): self
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }
}
