<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\CompanyModule;
use App\Entity\User;
use App\Enum\Module;
use App\Form\RegistrationType;
use App\Repository\UserRepository;
use App\Service\Registration\ActivationEmailSender;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ActivationEmailSender $activationEmailSender,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $company = (new Company())
                ->setName($user->getFullName())
                ->setVehicleLimit(1)
                ->setDriverLimit(0)
                ->setDocumentLimit(0);
            $vehicleModule = CompanyModule::forCompany($company, Module::VEHICLES)->setEnabled(true);
            $plainPassword = (string) $form->get('plainPassword')->getData();
            $activationToken = bin2hex(random_bytes(32));

            $user
                ->setCompany($company)
                ->setRoles(['ROLE_END_USER'])
                ->setPassword($passwordHasher->hashPassword($user, $plainPassword))
                ->prepareActivation($activationToken, new \DateTimeImmutable('+24 hours'));

            $connection = $entityManager->getConnection();
            $connection->beginTransaction();

            try {
                $entityManager->persist($company);
                $entityManager->persist($vehicleModule);
                $entityManager->persist($user);
                $entityManager->flush();

                $activationUrl = $this->generateUrl(
                    'app_activate_account',
                    ['token' => $activationToken],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                );
                $activationEmailSender->send($user, $activationUrl);
                $connection->commit();

                return $this->redirectToRoute('app_registration_check_email');
            } catch (UniqueConstraintViolationException) {
                $connection->rollBack();
                $form->get('email')->addError(new FormError('Korisnik s ovom email adresom već postoji.'));
            } catch (\Throwable) {
                $connection->rollBack();
                $form->addError(new FormError('Registraciju trenutno nije moguće završiti. Pokušajte ponovo kasnije.'));
            }
        }

        return $this->render('security/register.html.twig', ['registration_form' => $form]);
    }

    #[Route('/register/check-email', name: 'app_registration_check_email', methods: ['GET'])]
    public function checkEmail(): Response
    {
        return $this->render('security/check_email.html.twig');
    }

    #[Route('/activate/{token}', name: 'app_activate_account', requirements: ['token' => '[a-f0-9]{64}'], methods: ['GET'])]
    public function activate(
        string $token,
        UserRepository $users,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $users->findOneBy(['activationTokenHash' => hash('sha256', $token)]);

        if (!$user instanceof User || !$user->canActivateWith($token)) {
            $this->addFlash('error', 'Aktivacijski link nije ispravan ili je istekao.');

            return $this->redirectToRoute('app_login');
        }

        $user->activate();
        $entityManager->flush();
        $this->addFlash('success', 'Korisnički račun je aktiviran. Sada se možete prijaviti.');

        return $this->redirectToRoute('app_login');
    }
}
