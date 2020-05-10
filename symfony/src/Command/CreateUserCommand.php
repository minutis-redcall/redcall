<?php

namespace App\Command;

use App\Base\BaseCommand;
use App\Entity\UserInformation;
use App\Manager\UserInformationManager;
use App\Manager\VolunteerManager;
use Bundles\PasswordLoginBundle\Entity\AbstractUser;
use Bundles\PasswordLoginBundle\Manager\UserManager;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateUserCommand extends BaseCommand
{
    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var UserInformationManager
     */
    private $userInformationManager;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @param UserManager            $userManager
     * @param UserInformationManager $userInformationManager
     * @param VolunteerManager       $volunteerManager
     */
    public function __construct(UserManager $userManager, UserInformationManager $userInformationManager, VolunteerManager $volunteerManager)
    {
        parent::__construct();

        $this->userManager = $userManager;
        $this->userInformationManager = $userInformationManager;
        $this->volunteerManager = $volunteerManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('user:create')
            ->setDescription('Create users based on volunteer nivols')
            ->addArgument('nivol', InputArgument::REQUIRED|InputArgument::IS_ARRAY, 'Nivol from which to create a new user')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($input->getArgument('nivol') as $nivol) {
            $volunteer = $this->volunteerManager->findOneByNivol($nivol);

            if (!$volunteer) {
                $output->writeln(sprintf('KO %s: nivol do not exist', $nivol));
                continue;
            }

            if (!$volunteer->getEmail()) {
                $output->writeln(sprintf('KO: %s: volunteer has no email', $nivol));
                continue;
            }

            if ($this->userManager->findOneByUsername($volunteer->getEmail())) {
                $output->writeln(sprintf('KO %s: user having this email already exist', $nivol));
                continue;
            }

            if ($this->userInformationManager->findOneByNivol($nivol)) {
                $output->writeln(sprintf('KO %s: nivol already connected to a user', $nivol));
                continue;
            }

            $user = new AbstractUser();
            $user->setUsername($volunteer->getEmail());
            $user->setPassword(Uuid::uuid4());
            $user->setIsVerified(true);
            $user->setIsTrusted(true);
            $this->userManager->save($user);

            $userInformation = new UserInformation();
            $userInformation->setUser($user);
            $userInformation->setVolunteer($volunteer);
            $userInformation->setNivol($nivol);
            $this->userInformationManager->updateNivol($userInformation, $nivol);
            $this->userInformationManager->save($userInformation);

            $output->writeln(sprintf('OK %s: user created', $nivol));
        }

        return 0;
    }
}