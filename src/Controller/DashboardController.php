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
        $chartStart = $today->modify('first day of this month');
        $chartEnd = $chartStart->modify('+6 months');
        $monthNames = [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'Maj',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Aug',
            9 => 'Sep',
            10 => 'Okt',
            11 => 'Nov',
            12 => 'Dec',
        ];
        $expiryChartLabels = [];
        $expiryChartValues = [];
        $expiryChartIndexes = [];

        for ($monthOffset = 0; $monthOffset < 6; ++$monthOffset) {
            $month = $chartStart->modify(sprintf('+%d months', $monthOffset));
            $key = $month->format('Y-m');
            $expiryChartIndexes[$key] = $monthOffset;
            $expiryChartLabels[] = sprintf('%s %s', $monthNames[(int) $month->format('n')], $month->format('Y'));
            $expiryChartValues[] = 0;
        }

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

            foreach ($documents->findExpiringBetweenForCompany($company, $today, $chartEnd) as $document) {
                $monthIndex = $expiryChartIndexes[$document->getValidUntil()->format('Y-m')] ?? null;

                if ($monthIndex !== null) {
                    ++$expiryChartValues[$monthIndex];
                }
            }
        }

        return $this->render('dashboard/overview.html.twig', [
            'stats' => $stats,
            'today' => $today,
            'upcoming_documents' => $upcomingDocuments,
            'expired_documents' => $expiredDocuments,
            'latest_notifications' => $latestNotifications,
            'expiry_chart_labels' => $expiryChartLabels,
            'expiry_chart_values' => $expiryChartValues,
        ]);
    }
}
