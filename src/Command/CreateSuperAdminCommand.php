<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-super-admin', description: 'Creates the first platform super admin user.')]
class CreateSuperAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $users,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED)
            ->addArgument('password', InputArgument::REQUIRED)
            ->addArgument('firstName', InputArgument::OPTIONAL, default: 'Super')
            ->addArgument('lastName', InputArgument::OPTIONAL, default: 'Admin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (string) $input->getArgument('email');

        if ($this->users->findOneByEmail($email)) {
            $output->writeln(sprintf('<error>User "%s" already exists.</error>', $email));

            return Command::FAILURE;
        }

        $user = (new User())
            ->setEmail($email)
            ->setFirstName((string) $input->getArgument('firstName'))
            ->setLastName((string) $input->getArgument('lastName'))
            ->setRoles(['ROLE_SUPER_ADMIN']);

        $user->setPassword($this->passwordHasher->hashPassword($user, (string) $input->getArgument('password')));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln(sprintf('<info>Super admin "%s" created.</info>', $email));

        return Command::SUCCESS;
    }
}
