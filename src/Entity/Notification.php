<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{
    public const CHANNEL_EMAIL = 'email';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Company $company = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?FleetDocument $document = null;

    #[ORM\Column(length: 30)]
    private string $channel = self::CHANNEL_EMAIL;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(type: 'text')]
    private string $messageText = '';

    #[ORM\Column(length: 30)]
    private string $status = self::STATUS_SENT;

    #[ORM\Column(nullable: true)]
    private ?int $triggerDaysBefore = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $recipient = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $failureReason = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $eventKey = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

        return $this;
    }

    public function getDocument(): ?FleetDocument
    {
        return $this->document;
    }

    public function setDocument(?FleetDocument $document): self
    {
        $this->document = $document;

        return $this;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function setChannel(string $channel): self
    {
        $this->channel = $channel;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function markSent(): self
    {
        $this->status = self::STATUS_SENT;
        $this->sentAt = new \DateTimeImmutable();
        $this->failureReason = null;

        return $this;
    }

    public function markFailed(string $reason): self
    {
        $this->status = self::STATUS_FAILED;
        $this->sentAt = new \DateTimeImmutable();
        $this->failureReason = mb_substr($reason, 0, 2000);

        return $this;
    }

    public function getMessageText(): string
    {
        return $this->messageText;
    }

    public function setMessageText(string $messageText): self
    {
        $this->messageText = trim($messageText);

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getTriggerDaysBefore(): ?int
    {
        return $this->triggerDaysBefore;
    }

    public function setTriggerDaysBefore(?int $triggerDaysBefore): self
    {
        $this->triggerDaysBefore = $triggerDaysBefore;

        return $this;
    }

    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    public function setRecipient(?string $recipient): self
    {
        $recipient = $recipient !== null ? mb_strtolower(trim($recipient)) : null;
        $this->recipient = $recipient !== '' ? $recipient : null;

        return $this;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    public function getEventKey(): ?string
    {
        return $this->eventKey;
    }

    public function setEventKey(?string $eventKey): self
    {
        $eventKey = $eventKey !== null ? trim($eventKey) : null;
        $this->eventKey = $eventKey !== '' ? $eventKey : null;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
