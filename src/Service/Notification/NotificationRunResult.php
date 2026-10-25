<?php

namespace App\Service\Notification;

final readonly class NotificationRunResult
{
    public function __construct(
        public int $emailsSent = 0,
        public int $emailsFailed = 0,
        public int $notificationsSent = 0,
        public int $notificationsFailed = 0,
        public int $skipped = 0,
    ) {
    }

    public function merge(self $other): self
    {
        return new self(
            $this->emailsSent + $other->emailsSent,
            $this->emailsFailed + $other->emailsFailed,
            $this->notificationsSent + $other->notificationsSent,
            $this->notificationsFailed + $other->notificationsFailed,
            $this->skipped + $other->skipped,
        );
    }
}
