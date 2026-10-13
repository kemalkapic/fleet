<?php

namespace App\Entity;

use App\Repository\DriverRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DriverRepository::class)]
class Driver
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Company $company = null;

    #[ORM\Column(length: 100)]
    private string $firstName = '';

    #[ORM\Column(length: 100)]
    private string $lastName = '';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $licenceNumber = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $birthDate = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $birthPlace = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $licenceIssuedAt = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $licenceExpiresAt = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $licenceIssuedBy = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $residenceMunicipality = null;

    #[ORM\Column(type: 'json')]
    private array $licenceCategories = [];

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

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = trim($firstName);
        $this->touch();

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = trim($lastName);
        $this->touch();

        return $this;
    }

    public function getFullName(): string
    {
        return trim($this->firstName.' '.$this->lastName);
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $phone = $phone !== null ? trim($phone) : null;
        $this->phone = $phone !== '' ? $phone : null;
        $this->touch();

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $email = $email !== null ? mb_strtolower(trim($email)) : null;
        $this->email = $email !== '' ? $email : null;
        $this->touch();

        return $this;
    }

    public function getLicenceNumber(): ?string
    {
        return $this->licenceNumber;
    }

    public function setLicenceNumber(?string $licenceNumber): self
    {
        $licenceNumber = $licenceNumber !== null ? trim($licenceNumber) : null;
        $this->licenceNumber = $licenceNumber !== '' ? $licenceNumber : null;
        $this->touch();

        return $this;
    }

    public function getBirthDate(): ?\DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeImmutable $birthDate): self
    {
        $this->birthDate = $birthDate;
        $this->touch();

        return $this;
    }

    public function getBirthPlace(): ?string
    {
        return $this->birthPlace;
    }

    public function setBirthPlace(?string $birthPlace): self
    {
        $birthPlace = $birthPlace !== null ? trim($birthPlace) : null;
        $this->birthPlace = $birthPlace !== '' ? $birthPlace : null;
        $this->touch();

        return $this;
    }

    public function getLicenceIssuedAt(): ?\DateTimeImmutable
    {
        return $this->licenceIssuedAt;
    }

    public function setLicenceIssuedAt(?\DateTimeImmutable $licenceIssuedAt): self
    {
        $this->licenceIssuedAt = $licenceIssuedAt;
        $this->touch();

        return $this;
    }

    public function getLicenceExpiresAt(): ?\DateTimeImmutable
    {
        return $this->licenceExpiresAt;
    }

    public function setLicenceExpiresAt(?\DateTimeImmutable $licenceExpiresAt): self
    {
        $this->licenceExpiresAt = $licenceExpiresAt;
        $this->touch();

        return $this;
    }

    public function getLicenceIssuedBy(): ?string
    {
        return $this->licenceIssuedBy;
    }

    public function setLicenceIssuedBy(?string $licenceIssuedBy): self
    {
        $licenceIssuedBy = $licenceIssuedBy !== null ? trim($licenceIssuedBy) : null;
        $this->licenceIssuedBy = $licenceIssuedBy !== '' ? $licenceIssuedBy : null;
        $this->touch();

        return $this;
    }

    public function getResidenceMunicipality(): ?string
    {
        return $this->residenceMunicipality;
    }

    public function setResidenceMunicipality(?string $residenceMunicipality): self
    {
        $residenceMunicipality = $residenceMunicipality !== null ? trim($residenceMunicipality) : null;
        $this->residenceMunicipality = $residenceMunicipality !== '' ? $residenceMunicipality : null;
        $this->touch();

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getLicenceCategories(): array
    {
        return $this->licenceCategories;
    }

    /**
     * @param list<string> $licenceCategories
     */
    public function setLicenceCategories(array $licenceCategories): self
    {
        $this->licenceCategories = array_values(array_unique(array_filter(array_map(
            static fn (mixed $category): string => trim((string) $category),
            $licenceCategories,
        ))));
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
