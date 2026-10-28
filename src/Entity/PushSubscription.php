<?php

namespace App\Entity;

use App\Repository\PushSubscriptionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PushSubscriptionRepository::class)]
#[ORM\Table(name: 'push_subscription')]
#[ORM\UniqueConstraint(name: 'UNIQ_PUSH_ENDPOINT_HASH', columns: ['endpoint_hash'])]
class PushSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'text')]
    private string $endpoint = '';

    #[ORM\Column(length: 64)]
    private string $endpointHash = '';

    #[ORM\Column(type: 'text')]
    private string $publicKey = '';

    #[ORM\Column(type: 'text')]
    private string $authToken = '';

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $contentEncoding = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

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

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; $this->touch(); return $this; }
    public function getEndpoint(): string { return $this->endpoint; }
    public function setEndpoint(string $endpoint): self { $this->endpoint = trim($endpoint); $this->endpointHash = hash('sha256', $this->endpoint); $this->touch(); return $this; }
    public function getEndpointHash(): string { return $this->endpointHash; }
    public function getPublicKey(): string { return $this->publicKey; }
    public function setPublicKey(string $publicKey): self { $this->publicKey = trim($publicKey); $this->touch(); return $this; }
    public function getAuthToken(): string { return $this->authToken; }
    public function setAuthToken(string $authToken): self { $this->authToken = trim($authToken); $this->touch(); return $this; }
    public function getContentEncoding(): ?string { return $this->contentEncoding; }
    public function setContentEncoding(?string $contentEncoding): self { $this->contentEncoding = $contentEncoding; $this->touch(); return $this; }
    public function setUserAgent(?string $userAgent): self { $this->userAgent = $userAgent !== null ? mb_substr($userAgent, 0, 500) : null; $this->touch(); return $this; }
    public function getUserAgent(): ?string { return $this->userAgent; }
    public function isActive(): bool { return $this->revokedAt === null; }
    public function revoke(): self { $this->revokedAt = new \DateTimeImmutable(); $this->touch(); return $this; }
    public function activate(): self { $this->revokedAt = null; $this->touch(); return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    private function touch(): void { $this->updatedAt = new \DateTimeImmutable(); }
}
