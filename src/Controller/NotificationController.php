<?php

namespace App\Controller;

use App\Entity\Company;
use App\Enum\Module;
use App\Repository\NotificationRepository;
use App\Service\Module\ModuleChecker;
use App\Service\Tenant\TenantContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications', name: 'notification_')]
#[IsGranted('ROLE_COMPANY_ADMIN')]
class NotificationController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        TenantContext $tenantContext,
        ModuleChecker $moduleChecker,
        NotificationRepository $notifications,
    ): Response {
        $company = $tenantContext->requireCompany();
        $this->denyAccessUnlessGrantedForModule($moduleChecker, $company);

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications->findForCompany($company),
        ]);
    }

    private function denyAccessUnlessGrantedForModule(ModuleChecker $moduleChecker, Company $company): void
    {
        if (!$moduleChecker->isEnabled($company, Module::DOCUMENTS)) {
            throw $this->createAccessDeniedException('Documents module is disabled.');
        }
    }
}
