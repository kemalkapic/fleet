<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\FleetDocument;
use App\Entity\Vehicle;
use App\Entity\VehicleFuelEntry;
use App\Entity\VehicleTire;
use App\Enum\Module;
use App\Form\VehicleType;
use App\Repository\FleetDocumentRepository;
use App\Repository\VehicleFuelEntryRepository;
use App\Repository\VehicleRepository;
use App\Repository\VehicleTireRepository;
use App\Service\Module\ModuleChecker;
use App\Service\Tenant\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/vehicles', name: 'vehicle_')]
#[IsGranted('ROLE_COMPANY_ADMIN')]
class VehicleController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        TenantContext $tenantContext,
        ModuleChecker $moduleChecker,
        VehicleRepository $vehicles,
    ): Response {
        $company = $tenantContext->requireCompany();
        $this->denyAccessUnlessGrantedForModule($moduleChecker, $company);
        $vehicleCount = $vehicles->countForCompany($company);

        return $this->render('vehicle/index.html.twig', [
            'vehicles' => $vehicles->findForCompany($company),
            'vehicle_count' => $vehicleCount,
            'vehicle_limit' => $company->getVehicleLimit(),
            'vehicle_limit_reached' => $this->isLimitReached($company->getVehicleLimit(), $vehicleCount),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        TenantContext $tenantContext,
        ModuleChecker $moduleChecker,
        VehicleRepository $vehicles,
        FleetDocumentRepository $documents,
        EntityManagerInterface $entityManager,
    ): Response {
        $company = $tenantContext->requireCompany();
        $this->denyAccessUnlessGrantedForModule($moduleChecker, $company);

        if ($this->isLimitReached($company->getVehicleLimit(), $vehicles->countForCompany($company))) {
            $this->addFlash('error', 'Dosegnut je limit broja vozila. Kontaktirajte administratora da dodate još vozila.');

            return $this->redirectToRoute('vehicle_index');
        }

        $vehicle = (new Vehicle())->setCompany($company);
        $form = $this->createForm(VehicleType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->isLimitReached($company->getVehicleLimit(), $vehicles->countForCompany($company))) {
                $this->addFlash('error', 'Dosegnut je limit broja vozila. Kontaktirajte administratora da dodate još vozila.');

                return $this->redirectToRoute('vehicle_index');
            }

            if ($vehicles->existsActiveRegistration($company, $vehicle->getRegistrationNumber())) {
                $form->get('registrationNumber')->addError(new FormError('Vozilo sa ovom registracijom već postoji.'));

                return $this->render('vehicle/new.html.twig', [
                    'vehicle' => $vehicle,
                    'form' => $form,
                ]);
            }

            $entityManager->persist($vehicle);
            $documentCreated = $this->createTechnicalInspectionDocumentIfNeeded($vehicle, $company, $moduleChecker, $documents, $entityManager);
            $entityManager->flush();

            $this->addFlash(
                'success',
                $documentCreated
                    ? 'Vozilo je kreirano i dokument tehničkog pregleda je dodan.'
                    : 'Vozilo je kreirano.'
            );

            return $this->redirectToRoute('vehicle_index');
        }

        return $this->render('vehicle/new.html.twig', [
            'vehicle' => $vehicle,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        TenantContext $tenantContext,
        ModuleChecker $moduleChecker,
        VehicleRepository $vehicles,
        FleetDocumentRepository $documents,
        VehicleTireRepository $tires,
        VehicleFuelEntryRepository $fuelEntries,
        EntityManagerInterface $entityManager,
    ): Response {
        $company = $tenantContext->requireCompany();
        $this->denyAccessUnlessGrantedForModule($moduleChecker, $company);

        $vehicle = $vehicles->findOneForCompany($id, $company);

        if (!$vehicle instanceof Vehicle) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(VehicleType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($vehicles->existsActiveRegistration($company, $vehicle->getRegistrationNumber(), $vehicle)) {
                $form->get('registrationNumber')->addError(new FormError('Vozilo sa ovom registracijom već postoji.'));

                return $this->render('vehicle/edit.html.twig', [
                    'vehicle' => $vehicle,
                    'form' => $form,
                    'documents' => $documents->findForVehicle($vehicle, $company),
                    'tires' => $tires->findForVehicle($vehicle, $company),
                    'fuel_entries' => $this->buildFuelEntryRows($fuelEntries->findForVehicle($vehicle, $company)),
                ]);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Vozilo je ažurirano.');

            return $this->redirectToRoute('vehicle_index');
        }

        return $this->render('vehicle/edit.html.twig', [
            'vehicle' => $vehicle,
            'form' => $form,
            'documents' => $documents->findForVehicle($vehicle, $company),
            'tires' => $tires->findForVehicle($vehicle, $company),
            'fuel_entries' => $this->buildFuelEntryRows($fuelEntries->findForVehicle($vehicle, $company)),
        ]);
    }

    #[Route('/{id}/tires', name: 'tires_update', methods: ['POST'])]
    public function updateTires(
        int $id,
        Request $request,
        TenantContext $tenantContext,
        ModuleChecker $moduleChecker,
        VehicleRepository $vehicles,
        VehicleTireRepository $tires,
        EntityManagerInterface $entityManager,
    ): Response {
        $company = $tenantContext->requireCompany();
        $this->denyAccessUnlessGrantedForModule($moduleChecker, $company);

        $vehicle = $vehicles->findOneForCompany($id, $company);

        if (!$vehicle instanceof Vehicle) {
            throw new NotFoundHttpException();
        }

        if (!$this->isCsrfTokenValid('update_vehicle_tires_'.$vehicle->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid tire update token.');
        }

        foreach ($tires->findForVehicle($vehicle, $company) as $existingTire) {
            $entityManager->remove($existingTire);
        }

        $rows = $request->request->all('tires');
        $sortOrder = 0;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            $dot = trim((string) ($row['dot'] ?? ''));
            $position = trim((string) ($row['position'] ?? ''));
            $onVehicle = isset($row['onVehicle']);

            if ($name === '' && $dot === '' && $position === '') {
                continue;
            }

            if ($name === '') {
                $this->addFlash('error', 'Naziv gume je obavezan ako unosite red gume.');

                return $this->redirectToRoute('vehicle_edit', ['id' => $vehicle->getId(), '_fragment' => 'vehicle-tires']);
            }

            $tire = (new VehicleTire())
                ->setCompany($company)
                ->setVehicle($vehicle)
                ->setName($name)
                ->setDot($dot)
                ->setPosition($position)
                ->setOnVehicle($onVehicle)
                ->setSortOrder($sortOrder++);

            $entityManager->persist($tire);
        }

        $entityManager->flush();
        $this->addFlash('success', 'Gume su sačuvane.');

        return $this->redirectToRoute('vehicle_edit', ['id' => $vehicle->getId(), '_fragment' => 'vehicle-tires']);
    }

    #[Route('/{id}/fuel', name: 'fuel_add', methods: ['POST'])]
    public function addFuel(
        int $id,
        Request $request,
        TenantContext $tenantContext,
        ModuleChecker $moduleChecker,
        VehicleRepository $vehicles,
        EntityManagerInterface $entityManager,
    ): Response {
        $company = $tenantContext->requireCompany();
        $this->denyAccessUnlessGrantedForModule($moduleChecker, $company);

        $vehicle = $vehicles->findOneForCompany($id, $company);

        if (!$vehicle instanceof Vehicle) {
            throw new NotFoundHttpException();
        }

        if (!$this->isCsrfTokenValid('add_vehicle_fuel_'.$vehicle->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid fuel token.');
        }

        $entryDate = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $request->request->get('entryDate'));
        $liters = $this->decimalFromRequest($request, 'liters');
        $amountBam = $this->decimalFromRequest($request, 'amountBam');
        $odometerKm = (int) $request->request->get('odometerKm');

        if (!$entryDate instanceof \DateTimeImmutable || (float) $liters <= 0 || (float) $amountBam < 0 || $odometerKm <= 0) {
            $this->addFlash('error', 'Unesite ispravan datum, litre, iznos i kilometražu vozila.');

            return $this->redirectToRoute('vehicle_edit', ['id' => $vehicle->getId(), '_fragment' => 'vehicle-fuel']);
        }

        $fuelEntry = (new VehicleFuelEntry())
            ->setCompany($company)
            ->setVehicle($vehicle)
            ->setEntryDate($entryDate)
            ->setLiters($liters)
            ->setAmountBam($amountBam)
            ->setOdometerKm($odometerKm)
            ->setNotes((string) $request->request->get('notes'));

        $entityManager->persist($fuelEntry);
        $entityManager->flush();

        $this->addFlash('success', 'Unos goriva je sačuvan.');

        return $this->redirectToRoute('vehicle_edit', ['id' => $vehicle->getId(), '_fragment' => 'vehicle-fuel']);
    }

    #[Route('/{vehicleId}/fuel/{fuelId}/delete', name: 'fuel_delete', methods: ['POST'])]
    public function deleteFuel(
        int $vehicleId,
        int $fuelId,
        Request $request,
        TenantContext $tenantContext,
        ModuleChecker $moduleChecker,
        VehicleRepository $vehicles,
        VehicleFuelEntryRepository $fuelEntries,
        EntityManagerInterface $entityManager,
    ): Response {
        $company = $tenantContext->requireCompany();
        $this->denyAccessUnlessGrantedForModule($moduleChecker, $company);

        $vehicle = $vehicles->findOneForCompany($vehicleId, $company);

        if (!$vehicle instanceof Vehicle) {
            throw new NotFoundHttpException();
        }

        $fuelEntry = $fuelEntries->findOneForVehicle($fuelId, $vehicle, $company);

        if (!$fuelEntry instanceof VehicleFuelEntry) {
            throw new NotFoundHttpException();
        }

        if (!$this->isCsrfTokenValid('delete_vehicle_fuel_'.$fuelEntry->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid fuel delete token.');
        }

        $entityManager->remove($fuelEntry);
        $entityManager->flush();

        $this->addFlash('success', 'Unos goriva je obrisan.');

        return $this->redirectToRoute('vehicle_edit', ['id' => $vehicle->getId(), '_fragment' => 'vehicle-fuel']);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        TenantContext $tenantContext,
        ModuleChecker $moduleChecker,
        VehicleRepository $vehicles,
        FleetDocumentRepository $documents,
        EntityManagerInterface $entityManager,
    ): Response {
        $company = $tenantContext->requireCompany();
        $this->denyAccessUnlessGrantedForModule($moduleChecker, $company);

        $vehicle = $vehicles->findOneForCompany($id, $company);

        if (!$vehicle instanceof Vehicle) {
            throw new NotFoundHttpException();
        }

        if (!$this->isCsrfTokenValid('delete_vehicle_'.$vehicle->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid delete token.');
        }

        foreach ($documents->findForVehicle($vehicle, $company) as $document) {
            $document->softDelete();
        }

        $vehicle->softDelete();
        $entityManager->flush();

        $this->addFlash('success', 'Vozilo je obrisano.');

        return $this->redirectToRoute('vehicle_index');
    }

    private function denyAccessUnlessGrantedForModule(ModuleChecker $moduleChecker, Company $company): void
    {
        if (!$moduleChecker->isEnabled($company, Module::VEHICLES)) {
            throw $this->createAccessDeniedException('Vehicles module is disabled.');
        }
    }

    private function createTechnicalInspectionDocumentIfNeeded(
        Vehicle $vehicle,
        Company $company,
        ModuleChecker $moduleChecker,
        FleetDocumentRepository $documents,
        EntityManagerInterface $entityManager,
    ): bool {
        if (!$moduleChecker->isEnabled($company, Module::DOCUMENTS) || $vehicle->getRegistrationValidUntil() === null) {
            return false;
        }

        if ($this->isLimitReached($company->getDocumentLimit(), $documents->countForCompany($company))) {
            return false;
        }

        if ($vehicle->getId() !== null && $documents->findOneForVehicleByType($vehicle, $company, 'registration') instanceof FleetDocument) {
            return false;
        }

        $document = (new FleetDocument())
            ->setCompany($company)
            ->setVehicle($vehicle)
            ->setType('registration')
            ->setDocumentNumber($vehicle->getRegistrationNumber())
            ->setValidUntil($vehicle->getRegistrationValidUntil())
            ->setNotes('Automatski kreirano iz datuma isteka registracije vozila.');

        $entityManager->persist($document);

        return true;
    }

    private function isLimitReached(?int $limit, int $currentCount): bool
    {
        return $limit !== null && $currentCount >= $limit;
    }

    /**
     * @param list<VehicleFuelEntry> $entries
     *
     * @return list<array{entry: VehicleFuelEntry, distanceKm: int|null, consumption: float|null, costPer100Km: float|null}>
     */
    private function buildFuelEntryRows(array $entries): array
    {
        $chronologicalEntries = array_reverse($entries);
        $previousEntry = null;
        $rowsById = [];

        foreach ($chronologicalEntries as $entry) {
            $distanceKm = $previousEntry instanceof VehicleFuelEntry
                ? $entry->getOdometerKm() - $previousEntry->getOdometerKm()
                : null;

            $rowsById[$entry->getId()] = [
                'entry' => $entry,
                'distanceKm' => $distanceKm !== null && $distanceKm > 0 ? $distanceKm : null,
                'consumption' => $entry->getConsumptionLitersPer100Km($previousEntry),
                'costPer100Km' => $entry->getCostBamPer100Km($previousEntry),
            ];

            $previousEntry = $entry;
        }

        return array_values(array_filter(array_map(
            static fn (VehicleFuelEntry $entry): ?array => $entry->getId() !== null ? ($rowsById[$entry->getId()] ?? null) : null,
            $entries,
        )));
    }

    private function decimalFromRequest(Request $request, string $key): string
    {
        return str_replace(',', '.', trim((string) $request->request->get($key)));
    }
}
