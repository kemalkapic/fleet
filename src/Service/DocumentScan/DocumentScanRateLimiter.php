<?php

namespace App\Service\DocumentScan;

use App\Entity\User;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class DocumentScanRateLimiter
{
    public function __construct(private readonly RateLimiterFactory $documentScanLimiter)
    {
    }

    public function consumeForUser(User $user): void
    {
        $limiter = $this->documentScanLimiter->create((string) $user->getId());
        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            throw new DocumentScanRateLimitExceededException('Previše pokušaja čitanja dokumenta. Pokušajte ponovo za nekoliko minuta.');
        }
    }
}
