<?php

namespace App\Controller;

use App\Repository\CompanyRepository;
use App\Repository\DriverRepository;
use App\Repository\FleetDocumentRepository;
use App\Repository\NotificationRepository;
use App\Repository\VehicleRepository;
use App\Service\Tenant\TenantContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        TenantContext $tenantContext,
        CompanyRepository $companies,
        VehicleRepository $vehicles,
        DriverRepository $drivers,
        FleetDocumentRepository $documents,
        NotificationRepository $notifications,
    ): Response
    {
        $company = $tenantContext->getCompany();
        $today = new \DateTimeImmutable('today');
        $next30Days = $today->modify('+30 days');

        $stats = [
            'companies' => $companies->count([]),
            'vehicles' => 0,
            'vehicleLimit' => null,
            'drivers' => 0,
            'driverLimit' => null,
            'documents' => 0,
            'documentLimit' => null,
            'sentNotifications' => 0,
            'expiringSoon' => 0,
            'expiredDocuments' => 0,
        ];
        $upcomingDocuments = [];
        $expiredDocuments = [];
        $latestNotifications = [];

        if ($company !== null) {
            $stats['vehicles'] = $vehicles->countForCompany($company);
            $stats['vehicleLimit'] = $company->getVehicleLimit();
            $stats['drivers'] = $drivers->countForCompany($company);
            $stats['driverLimit'] = $company->getDriverLimit();
            $stats['documents'] = $documents->countForCompany($company);
            $stats['documentLimit'] = $company->getDocumentLimit();
            $stats['sentNotifications'] = $notifications->countSentForCompany($company);
            $stats['expiringSoon'] = $documents->countExpiringWithinForCompany($company, $today, $next30Days);
            $stats['expiredDocuments'] = $documents->countExpiredForCompany($company, $today);
            $upcomingDocuments = $documents->findUpcomingForCompany($company, $today, 10);
            $expiredDocuments = $documents->findExpiredForCompany($company, $today, 10);
            $latestNotifications = $notifications->findLatestForCompanyLimited($company, 3);
        }

        return $this->render('dashboard/overview.html.twig', [
            'stats' => $stats,
            'today' => $today,
            'upcoming_documents' => $upcomingDocuments,
            'expired_documents' => $expiredDocuments,
            'latest_notifications' => $latestNotifications,
        ]);
    }
}
