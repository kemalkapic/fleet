<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'app_user')]
#[ORM\UniqueConstraint(name: 'UNIQ_APP_USER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Korisnik s ovom email adresom već postoji.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Company $company = null;

    #[ORM\Column(length: 180)]
    private string $email = '';

    /**
     * @var list<string>
     */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private string $password = '';

    #[ORM\Column(length: 100)]
    private string $firstName = '';

    #[ORM\Column(length: 100)]
    private string $lastName = '';

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastActiveAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $termsAcceptedAt = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $termsAcceptedVersion = null;

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

    public function setCompany(?Company $company): self
    {
        $this->company = $company;
        $this->touch();

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = mb_strtolower(trim($email));
        $this->touch();

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /**
     * @return list<string>
     */
    public function getAssignedRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): self
    {
        $this->roles = array_values(array_unique($roles));
        $this->touch();

        return $this;
    }

    /**
     * @param list<string> $roles
     */
    public function setAssignedRoles(array $roles): self
    {
        return $this->setRoles($roles);
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        $this->touch();

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        $this->touch();

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        $this->touch();

        return $this;
    }

    public function getFullName(): string
    {
        return trim($this->firstName.' '.$this->lastName);
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
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

    public function getLastActiveAt(): ?\DateTimeImmutable
    {
        return $this->lastActiveAt;
    }

    public function recordActivity(?\DateTimeImmutable $at = null): self
    {
        $this->lastActiveAt = $at ?? new \DateTimeImmutable();

        return $this;
    }

    public function getTermsAcceptedAt(): ?\DateTimeImmutable
    {
        return $this->termsAcceptedAt;
    }

    public function getTermsAcceptedVersion(): ?string
    {
        return $this->termsAcceptedVersion;
    }

    public function hasAcceptedTerms(string $version): bool
    {
        return $this->termsAcceptedAt !== null && $this->termsAcceptedVersion === $version;
    }

    public function acceptTerms(string $version): self
    {
        $this->termsAcceptedAt = new \DateTimeImmutable();
        $this->termsAcceptedVersion = $version;
        $this->touch();

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
