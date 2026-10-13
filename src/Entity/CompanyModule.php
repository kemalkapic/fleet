<?php

namespace App\Entity;

use App\Enum\Module;
use App\Repository\CompanyModuleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompanyModuleRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_COMPANY_MODULE', fields: ['company', 'moduleKey'])]
class CompanyModule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'modules')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Company $company = null;

    #[ORM\Column(length: 50)]
    private string $moduleKey = '';

    #[ORM\Column]
    private bool $enabled = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $enabledAt = null;

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

    public static function forCompany(Company $company, Module $module): self
    {
        return (new self())
            ->setCompany($company)
            ->setModule($module);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): self
    {
        $this->company = $company;
        $this->touch();

        return $this;
    }

    public function getModuleKey(): string
    {
        return $this->moduleKey;
    }

    public function getModule(): ?Module
    {
        return Module::tryFrom($this->moduleKey);
    }

    public function setModule(Module $module): self
    {
        $this->moduleKey = $module->value;
        $this->touch();

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        if ($enabled && !$this->enabledAt instanceof \DateTimeImmutable) {
            $this->enabledAt = new \DateTimeImmutable();
        }

        if (!$enabled) {
            $this->enabledAt = null;
        }

        $this->enabled = $enabled;
        $this->touch();

        return $this;
    }

    public function getEnabledAt(): ?\DateTimeImmutable
    {
        return $this->enabledAt;
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
