<?php

namespace App\Controller\Platform;

use App\Entity\Company;
use App\Entity\CompanyModule;
use App\Enum\Module;
use App\Repository\CompanyModuleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/platform/companies/{id}/modules', name: 'platform_company_module_')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class CompanyModuleController extends AbstractController
{
    #[Route('', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Company $company,
        Request $request,
        CompanyModuleRepository $companyModules,
        EntityManagerInterface $entityManager,
    ): Response {
        $settings = $companyModules->findForCompanyIndexed($company);

        if ($request->isMethod('POST')) {
            $enabledModuleKeys = $request->request->all('modules');
            $enabledModuleKeys = is_array($enabledModuleKeys) ? $enabledModuleKeys : [];

            foreach ($this->assignableModules() as $module) {
                $companyModule = $settings[$module->value] ?? CompanyModule::forCompany($company, $module);
                $companyModule->setEnabled(in_array($module->value, $enabledModuleKeys, true));

                $entityManager->persist($companyModule);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Moduli su ažurirani.');

            return $this->redirectToRoute('platform_company_module_edit', ['id' => $company->getId()]);
        }

        return $this->render('platform/company_module/edit.html.twig', [
            'company' => $company,
            'modules' => $this->assignableModules(),
            'settings' => $settings,
        ]);
    }

    /**
     * @return list<Module>
     */
    private function assignableModules(): array
    {
        return array_values(array_filter(
            Module::cases(),
            static fn (Module $module): bool => $module !== Module::REMINDERS,
        ));
    }
}
