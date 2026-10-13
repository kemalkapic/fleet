<?php

namespace App\Twig;

use App\Entity\User;
use App\Enum\Module;
use App\Service\Module\ModuleChecker;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ModuleExtension extends AbstractExtension
{
    public function __construct(
        private readonly Security $security,
        private readonly ModuleChecker $moduleChecker,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('module_enabled', $this->isModuleEnabled(...)),
        ];
    }

    public function isModuleEnabled(string $moduleKey): bool
    {
        $module = Module::tryFrom($moduleKey);
        $user = $this->security->getUser();

        if (!$module instanceof Module || !$user instanceof User || !$user->getCompany()) {
            return false;
        }

        return $this->moduleChecker->isEnabled($user->getCompany(), $module);
    }
}
