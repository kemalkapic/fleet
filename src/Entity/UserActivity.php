<?php

namespace App\Entity;

use App\Repository\UserActivityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserActivityRepository::class)]
#[ORM\Table(name: 'user_activity')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_ACTIVITY_DAY', columns: ['user_id', 'activity_date'])]
class UserActivity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Company $company = null;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $activityDate;

    #[ORM\Column]
    private \DateTimeImmutable $lastSeenAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->activityDate = $now->setTime(0, 0);
        $this->lastSeenAt = $now;
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }
    public function getCompany(): ?Company { return $this->company; }
    public function setCompany(?Company $company): self { $this->company = $company; return $this; }
    public function getActivityDate(): \DateTimeImmutable { return $this->activityDate; }
    public function setActivityDate(\DateTimeImmutable $activityDate): self { $this->activityDate = $activityDate->setTime(0, 0); return $this; }
    public function getLastSeenAt(): \DateTimeImmutable { return $this->lastSeenAt; }
    public function markSeen(\DateTimeImmutable $at): self { $this->lastSeenAt = $at; return $this; }
}
