<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\Vehicle;
use App\Entity\VehicleExpense;
use App\Enum\Module;
use App\Enum\VehicleExpenseType;
use App\Repository\VehicleExpenseRepository;
use App\Repository\VehicleRepository;
use App\Service\Module\ModuleChecker;
use App\Service\Tenant\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/vehicles', name: 'vehicle_expense_')]
#[IsGranted('ROLE_COMPANY_ADMIN')]
class VehicleExpenseController extends AbstractController
{
    #[Route('/{id}/expenses', name: 'add', methods: ['POST'])]
    public function add(
        int $id,
        Request $request,
        TenantContext $tenantContext,
        ModuleChecker $moduleChecker,
        VehicleRepository $vehicles,
        EntityManagerInterface $entityManager,
    ): Response {
        $company = $tenantContext->requireCompany();
        $this->denyAccessUnlessVehiclesEnabled($moduleChecker, $company);
        $vehicle = $vehicles->findOneForCompany($id, $company);

        if (!$vehicle instanceof Vehicle) {
            throw new NotFoundHttpException();
        }

        if (!$this->isCsrfTokenValid('add_vehicle_expense_'.$vehicle->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid vehicle expense token.');
        }

        $expenseDate = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $request->request->get('expenseDate'));
        $type = VehicleExpenseType::tryFrom((string) $request->request->get('type'));
        $amountBam = str_replace(',', '.', trim((string) $request->request->get('amountBam')));

        if (!$expenseDate instanceof \DateTimeImmutable || !$type instanceof VehicleExpenseType || !is_numeric($amountBam) || (float) $amountBam <= 0) {
            $this->addFlash('error', 'Unesite ispravan datum, vrstu i iznos troška.');

            return $this->redirectToRoute('vehicle_edit', ['id' => $vehicle->getId(), '_fragment' => 'vehicle-expenses']);
        }

        $expense = (new VehicleExpense())
            ->setCompany($company)
            ->setVehicle($vehicle)
            ->setExpenseDate($expenseDate)
            ->setType($type)
            ->setAmountBam($amountBam)
            ->setNotes((string) $request->request->get('notes'));

        $entityManager->persist($expense);
        $entityManager->flush();
        $this->addFlash('success', 'Trošak je sačuvan.');

        return $this->redirectToRoute('vehicle_edit', ['id' => $vehicle->getId(), '_fragment' => 'vehicle-expenses']);
    }

    #[Route('/{vehicleId}/expenses/{expenseId}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        int $vehicleId,
        int $expenseId,
        Request $request,
        TenantContext $tenantContext,
        ModuleChecker $moduleChecker,
        VehicleRepository $vehicles,
        VehicleExpenseRepository $expenses,
        EntityManagerInterface $entityManager,
    ): Response {
        $company = $tenantContext->requireCompany();
        $this->denyAccessUnlessVehiclesEnabled($moduleChecker, $company);
        $vehicle = $vehicles->findOneForCompany($vehicleId, $company);

        if (!$vehicle instanceof Vehicle) {
            throw new NotFoundHttpException();
        }

        $expense = $expenses->findOneForVehicle($expenseId, $vehicle, $company);
        if (!$expense instanceof VehicleExpense) {
            throw new NotFoundHttpException();
        }

        if (!$this->isCsrfTokenValid('delete_vehicle_expense_'.$expense->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid vehicle expense delete token.');
        }

        $entityManager->remove($expense);
        $entityManager->flush();
        $this->addFlash('success', 'Trošak je obrisan.');

        return $this->redirectToRoute('vehicle_edit', ['id' => $vehicle->getId(), '_fragment' => 'vehicle-expenses']);
    }

    private function denyAccessUnlessVehiclesEnabled(ModuleChecker $moduleChecker, Company $company): void
    {
        if (!$moduleChecker->isEnabled($company, Module::VEHICLES)) {
            throw $this->createAccessDeniedException('Vehicles module is disabled.');
        }
    }
}
