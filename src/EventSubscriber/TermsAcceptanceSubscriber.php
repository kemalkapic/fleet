<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class TermsAcceptanceSubscriber implements EventSubscriberInterface
{
    private const ALLOWED_ROUTES = ['app_dashboard', 'terms_accept', 'app_logout'];

    public function __construct(
        private Security $security,
        private UrlGeneratorInterface $urlGenerator,
        #[Autowire('%app.terms_version%')] private string $termsVersion,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['requireAcceptance', -8]];
    }

    public function requireAcceptance(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || $user->hasAcceptedTerms($this->termsVersion)) {
            return;
        }

        $route = (string) $event->getRequest()->attributes->get('_route');
        if (in_array($route, self::ALLOWED_ROUTES, true)) {
            return;
        }

        $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_dashboard')));
    }
}
