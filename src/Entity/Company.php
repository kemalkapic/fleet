<?php

namespace App\Entity;

use App\Repository\CompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
class Company
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $name = '';

    #[ORM\Column(length: 30)]
    private string $status = 'active';

    #[ORM\Column(nullable: true)]
    private ?int $vehicleLimit = null;

    #[ORM\Column(nullable: true)]
    private ?int $driverLimit = null;

    #[ORM\Column(nullable: true)]
    private ?int $documentLimit = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: User::class)]
    private Collection $users;

    /**
     * @var Collection<int, CompanyModule>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: CompanyModule::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $modules;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->users = new ArrayCollection();
        $this->modules = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
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

    public function getVehicleLimit(): ?int
    {
        return $this->vehicleLimit;
    }

    public function setVehicleLimit(?int $vehicleLimit): self
    {
        $this->vehicleLimit = $vehicleLimit !== null && $vehicleLimit >= 0 ? $vehicleLimit : null;
        $this->touch();

        return $this;
    }

    public function getDriverLimit(): ?int
    {
        return $this->driverLimit;
    }

    public function setDriverLimit(?int $driverLimit): self
    {
        $this->driverLimit = $driverLimit !== null && $driverLimit >= 0 ? $driverLimit : null;
        $this->touch();

        return $this;
    }

    public function getDocumentLimit(): ?int
    {
        return $this->documentLimit;
    }

    public function setDocumentLimit(?int $documentLimit): self
    {
        $this->documentLimit = $documentLimit !== null && $documentLimit >= 0 ? $documentLimit : null;
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

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /**
     * @return Collection<int, CompanyModule>
     */
    public function getModules(): Collection
    {
        return $this->modules;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
