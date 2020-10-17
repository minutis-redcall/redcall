<?php

namespace App\Command;

use App\Base\BaseCommand;
use App\Entity\User;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
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
     * @var VolunteerManager
     */
    private $volunteerManager;

    public function __construct(UserManager $userManager, VolunteerManager $volunteerManager)
    {
        parent::__construct();

        $this->userManager      = $userManager;
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
            ->addArgument('nivol', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Nivol from which to create a new user');
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

            if ($this->userManager->findOneByNivol($nivol)) {
                $output->writeln(sprintf('KO %s: nivol already connected to a user', $nivol));
                continue;
            }

            $user = new User();
            $user->setUsername($volunteer->getEmail());
            $user->setPassword(Uuid::uuid4());
            $user->setIsVerified(true);
            $user->setIsTrusted(true);
            $this->userManager->save($user);

            $this->userManager->updateNivol($user, $nivol);

            $output->writeln(sprintf('OK %s: user created', $nivol));
        }

        return 0;
    }
}