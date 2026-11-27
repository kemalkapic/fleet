<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CompanyRepository;
use App\Repository\DriverRepository;
use App\Repository\FleetDocumentRepository;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use App\Repository\UserActivityRepository;
use App\Repository\VehicleFuelEntryRepository;
use App\Repository\VehicleExpenseRepository;
use App\Repository\VehicleRepository;
use App\Repository\VehicleServiceRecordRepository;
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
        UserRepository $users,
        UserActivityRepository $userActivities,
        VehicleFuelEntryRepository $fuelEntries,
        VehicleExpenseRepository $vehicleExpenses,
        VehicleServiceRecordRepository $serviceRecords,
        string $termsVersion,
    ): Response
    {
        $currentUser = $this->getUser();
        if ($currentUser instanceof User
            && $this->isGranted('ROLE_END_USER')
            && !$this->isGranted('ROLE_COMPANY_ADMIN')
            && $currentUser->hasAcceptedTerms($termsVersion)
        ) {
            return $this->redirectToRoute('vehicle_index');
        }

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
        $expiryChartDetails = [];
        $expiryChartIndexes = [];

        for ($monthOffset = 0; $monthOffset < 6; ++$monthOffset) {
            $month = $chartStart->modify(sprintf('+%d months', $monthOffset));
            $key = $month->format('Y-m');
            $expiryChartIndexes[$key] = $monthOffset;
            $expiryChartLabels[] = sprintf('%s %s', $monthNames[(int) $month->format('n')], $month->format('Y'));
            $expiryChartValues[] = 0;
            $expiryChartDetails[] = [];
        }

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $activityStart = $today->modify('-13 days');
            $activityLabels = [];
            $activityValues = [];
            $activityDetails = [];
            $activityIndexes = [];
            $notificationDetails = [];
            $notificationSeriesByCompany = [];
            for ($dayOffset = 0; $dayOffset < 14; ++$dayOffset) {
                $day = $activityStart->modify(sprintf('+%d days', $dayOffset));
                $key = $day->format('Y-m-d');
                $activityIndexes[$key] = $dayOffset;
                $activityLabels[] = $day->format('d.m.');
                $activityValues[] = 0;
                $activityDetails[] = [];
                $notificationDetails[] = [];
            }

            foreach ($userActivities->findPlatformBetween($activityStart, $today) as $activity) {
                $dayIndex = $activityIndexes[$activity->getActivityDate()->format('Y-m-d')] ?? null;
                $user = $activity->getUser();
                if ($dayIndex === null || $user === null) {
                    continue;
                }

                ++$activityValues[$dayIndex];
                $activityDetails[$dayIndex][] = [
                    'user' => $user->getFullName() ?: $user->getEmail(),
                    'email' => $user->getEmail(),
                    'company' => $activity->getCompany()?->getName() ?? '-',
                    'time' => $activity->getLastSeenAt()->format('H:i'),
                ];
            }

            foreach ($notifications->findPlatformSentBetween($activityStart, $today->modify('+1 day')) as $notification) {
                $sentAt = $notification->getSentAt();
                $dayIndex = $sentAt !== null ? ($activityIndexes[$sentAt->format('Y-m-d')] ?? null) : null;
                if ($dayIndex === null) {
                    continue;
                }

                $company = $notification->getCompany();
                if ($company === null) {
                    continue;
                }

                $companyKey = (string) ($company->getId() ?? spl_object_id($company));
                $notificationSeriesByCompany[$companyKey] ??= [
                    'name' => $company->getName(),
                    'data' => array_fill(0, 14, 0),
                ];
                ++$notificationSeriesByCompany[$companyKey]['data'][$dayIndex];
                $notificationDetails[$dayIndex][] = [
                    'company' => $company->getName(),
                    'channel' => $notification->getChannel() === 'web_push' ? 'Web push' : 'Email',
                    'recipient' => $notification->getRecipient() ?? '-',
                    'time' => $sentAt->format('H:i'),
                ];
            }

            $notificationSeries = array_values($notificationSeriesByCompany);
            usort($notificationSeries, static fn (array $left, array $right): int => strcasecmp($left['name'], $right['name']));

            foreach ($documents->findPlatformExpiringBetween($today, $chartEnd) as $document) {
                $monthIndex = $expiryChartIndexes[$document->getValidUntil()->format('Y-m')] ?? null;

                if ($monthIndex !== null) {
                    ++$expiryChartValues[$monthIndex];
                    $expiryChartDetails[$monthIndex][] = [
                        'type' => $document->getTypeLabel(),
                        'company' => $document->getCompany()?->getName() ?? '-',
                        'owner' => $document->getOwnerLabel(),
                        'date' => $document->getValidUntil()->format('d.m.Y'),
                    ];
                }
            }

            $platformStats = [
                'companies' => $companies->getPlatformStatusCounts(),
                'vehicles' => $vehicles->getPlatformStatusCounts(),
                'drivers' => $drivers->getPlatformStatusCounts(),
                'users' => $users->getPlatformStatusCounts(),
                'documents' => $documents->getPlatformStatusCounts($today),
                'notifications' => $notifications->getPlatformStatusCounts(),
            ];

            return $this->render('dashboard/platform_overview.html.twig', [
                'platform_stats' => $platformStats,
                'platform_companies' => $companies->findPlatformSummaries(6),
                'expiring_next_30_days' => $documents->countPlatformExpiringWithin($today, $next30Days),
                'expiry_chart_labels' => $expiryChartLabels,
                'expiry_chart_values' => $expiryChartValues,
                'expiry_chart_details' => $expiryChartDetails,
                'activity_chart_labels' => $activityLabels,
                'activity_chart_values' => $activityValues,
                'activity_chart_details' => $activityDetails,
                'notification_chart_labels' => $activityLabels,
                'notification_chart_series' => $notificationSeries,
                'notification_chart_details' => $notificationDetails,
            ]);
        }

        $upcomingDocuments = [];
        $expiredDocuments = [];
        $latestNotifications = [];
        $costYear = (int) $today->format('Y');
        $costSummary = ['total' => 0.0, 'fuel' => 0.0, 'services' => 0.0, 'expenses' => 0.0];
        $vehicleCosts = [];

        if ($company !== null) {
            $upcomingDocuments = $documents->findUpcomingForCompany($company, $today, 10);
            $expiredDocuments = $documents->findExpiredForCompany($company, $today, 10);
            $latestNotifications = $notifications->findLatestForCompanyLimited($company, 3);

            $costFrom = $today->setDate($costYear, 1, 1);
            $costUntil = $costFrom->modify('+1 year');
            $costsByVehicle = [];

            foreach ($vehicles->findForCompany($company) as $vehicle) {
                $vehicleId = $vehicle->getId();
                if ($vehicleId === null) {
                    continue;
                }

                $description = trim(sprintf('%s %s', $vehicle->getManufacturer() ?? '', $vehicle->getModel() ?? ''));
                $costsByVehicle[$vehicleId] = [
                    'vehicleId' => $vehicleId,
                    'registration' => $vehicle->getRegistrationNumber(),
                    'description' => $description,
                    'fuel' => 0.0,
                    'services' => 0.0,
                    'expenses' => 0.0,
                    'total' => 0.0,
                ];
            }

            foreach ($fuelEntries->findCostTotalsByVehicle($company, $costFrom, $costUntil) as $row) {
                $vehicleId = (int) $row['vehicleId'];
                if (isset($costsByVehicle[$vehicleId])) {
                    $costsByVehicle[$vehicleId]['fuel'] = (float) $row['total'];
                }
            }

            foreach ($serviceRecords->findCostTotalsByVehicle($company, $costFrom, $costUntil) as $row) {
                $vehicleId = (int) $row['vehicleId'];
                if (isset($costsByVehicle[$vehicleId])) {
                    $costsByVehicle[$vehicleId]['services'] = (float) $row['total'];
                }
            }

            foreach ($vehicleExpenses->findCostTotalsByVehicle($company, $costFrom, $costUntil) as $row) {
                $vehicleId = (int) $row['vehicleId'];
                if (isset($costsByVehicle[$vehicleId])) {
                    $costsByVehicle[$vehicleId]['expenses'] = (float) $row['total'];
                }
            }

            foreach ($costsByVehicle as $vehicleCost) {
                $vehicleCost['total'] = $vehicleCost['fuel'] + $vehicleCost['services'] + $vehicleCost['expenses'];
                $costSummary['fuel'] += $vehicleCost['fuel'];
                $costSummary['services'] += $vehicleCost['services'];
                $costSummary['expenses'] += $vehicleCost['expenses'];

                if ($vehicleCost['total'] > 0) {
                    $vehicleCosts[] = $vehicleCost;
                }
            }

            $costSummary['total'] = $costSummary['fuel'] + $costSummary['services'] + $costSummary['expenses'];
            usort($vehicleCosts, static fn (array $left, array $right): int => $right['total'] <=> $left['total']);

            foreach ($documents->findExpiringBetweenForCompany($company, $today, $chartEnd) as $document) {
                $monthIndex = $expiryChartIndexes[$document->getValidUntil()->format('Y-m')] ?? null;

                if ($monthIndex !== null) {
                    ++$expiryChartValues[$monthIndex];
                    $expiryChartDetails[$monthIndex][] = [
                        'type' => $document->getTypeLabel(),
                        'owner' => $document->getOwnerLabel(),
                        'date' => $document->getValidUntil()->format('d.m.Y'),
                    ];
                }
            }
        }

        return $this->render('dashboard/overview.html.twig', [
            'today' => $today,
            'upcoming_documents' => $upcomingDocuments,
            'expired_documents' => $expiredDocuments,
            'latest_notifications' => $latestNotifications,
            'expiry_chart_labels' => $expiryChartLabels,
            'expiry_chart_values' => $expiryChartValues,
            'expiry_chart_details' => $expiryChartDetails,
            'cost_year' => $costYear,
            'cost_summary' => $costSummary,
            'vehicle_costs' => $vehicleCosts,
        ]);
    }
}
