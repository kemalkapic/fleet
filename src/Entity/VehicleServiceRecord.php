<?php

namespace App\Entity;

use App\Repository\VehicleServiceRecordRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehicleServiceRecordRepository::class)]
#[ORM\Table(name: 'vehicle_service_record')]
class VehicleServiceRecord
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

    #[ORM\Column(length: 30)]
    private string $type = 'small';

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $serviceDate;

    #[ORM\Column]
    private int $odometerKm = 0;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $serviceProvider = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $costBam = null;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $items = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $additionalWork = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    /** @var Collection<int, VehicleServiceAttachment> */
    #[ORM\OneToMany(mappedBy: 'serviceRecord', targetEntity: VehicleServiceAttachment::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $attachments;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->serviceDate = new \DateTimeImmutable('today');
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->attachments = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getCompany(): ?Company { return $this->company; }
    public function getVehicle(): ?Vehicle { return $this->vehicle; }
    public function getType(): string { return $this->type; }
    public function getServiceDate(): \DateTimeImmutable { return $this->serviceDate; }
    public function getOdometerKm(): int { return $this->odometerKm; }
    public function getServiceProvider(): ?string { return $this->serviceProvider; }
    public function getCostBam(): ?string { return $this->costBam; }

    /** @return list<string> */
    public function getItems(): array { return $this->items; }

    public function getAdditionalWork(): ?string { return $this->additionalWork; }
    public function getNotes(): ?string { return $this->notes; }

    /** @return Collection<int, VehicleServiceAttachment> */
    public function getAttachments(): Collection { return $this->attachments; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    public function setCompany(Company $company): self { $this->company = $company; return $this->touch(); }
    public function setVehicle(Vehicle $vehicle): self { $this->vehicle = $vehicle; return $this->touch(); }
    public function setType(string $type): self { $this->type = trim($type); return $this->touch(); }
    public function setServiceDate(\DateTimeImmutable $serviceDate): self { $this->serviceDate = $serviceDate; return $this->touch(); }
    public function setOdometerKm(int $odometerKm): self { $this->odometerKm = max(0, $odometerKm); return $this->touch(); }

    public function setServiceProvider(?string $serviceProvider): self
    {
        $serviceProvider = $serviceProvider !== null ? trim($serviceProvider) : null;
        $this->serviceProvider = $serviceProvider !== '' ? $serviceProvider : null;

        return $this->touch();
    }

    public function setCostBam(?string $costBam): self
    {
        $costBam = $costBam !== null ? str_replace(',', '.', trim($costBam)) : null;
        $this->costBam = $costBam !== null && $costBam !== ''
            ? number_format(max(0, (float) $costBam), 2, '.', '')
            : null;

        return $this->touch();
    }

    /** @param list<string> $items */
    public function setItems(array $items): self { $this->items = array_values(array_unique($items)); return $this->touch(); }
    public function setAdditionalWork(?string $additionalWork): self { $this->additionalWork = $this->normalizeText($additionalWork); return $this->touch(); }
    public function setNotes(?string $notes): self { $this->notes = $this->normalizeText($notes); return $this->touch(); }

    public function addAttachment(VehicleServiceAttachment $attachment): self
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
            $attachment->setServiceRecord($this);
        }

        return $this;
    }

    private function normalizeText(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : null;

        return $value !== '' ? $value : null;
    }

    private function touch(): self
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }
}
