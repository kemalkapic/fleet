<?php

namespace App\Entity;

use App\Repository\UserNotificationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserNotificationRepository::class)]
#[ORM\Table(name: 'user_notification')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_NOTIFICATION_EVENT', columns: ['user_id', 'event_key'])]
class UserNotification
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
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?FleetDocument $document = null;

    /** @var Collection<int, Notification> */
    #[ORM\OneToMany(mappedBy: 'inboxItem', targetEntity: Notification::class)]
    private Collection $deliveryLogs;

    #[ORM\Column(length: 191)]
    private string $eventKey = '';

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(type: 'text')]
    private string $messageText = '';

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $targetUrl = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->deliveryLogs = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getCompany(): ?Company { return $this->company; }
    public function setCompany(Company $company): self { $this->company = $company; return $this; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }
    public function getDocument(): ?FleetDocument { return $this->document; }
    public function setDocument(?FleetDocument $document): self { $this->document = $document; return $this; }
    public function getEventKey(): string { return $this->eventKey; }
    public function setEventKey(string $eventKey): self { $this->eventKey = trim($eventKey); return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = trim($title); return $this; }
    public function getMessageText(): string { return $this->messageText; }
    public function setMessageText(string $messageText): self { $this->messageText = trim($messageText); return $this; }
    public function getTargetUrl(): ?string { return $this->targetUrl; }
    public function setTargetUrl(?string $targetUrl): self { $this->targetUrl = $targetUrl !== null ? trim($targetUrl) : null; return $this; }
    public function getReadAt(): ?\DateTimeImmutable { return $this->readAt; }
    public function isRead(): bool { return $this->readAt !== null; }
    public function markRead(): self { $this->readAt ??= new \DateTimeImmutable(); return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    /** @return list<string> */
    public function getDeliveryChannels(): array
    {
        $channels = [];
        foreach ($this->deliveryLogs as $deliveryLog) {
            if ($deliveryLog->getStatus() === Notification::STATUS_SENT) {
                $channels[] = $deliveryLog->getChannel();
            }
        }

        return array_values(array_unique($channels));
    }
}
