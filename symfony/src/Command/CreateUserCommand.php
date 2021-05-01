<?php

namespace App\Command;

use App\Base\BaseCommand;
use App\Entity\User;
use App\Manager\PlatformConfigManager;
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

    /**
     * @var PlatformConfigManager
     */
    private $platformConfigManager;

    public function __construct(UserManager $userManager,
        VolunteerManager $volunteerManager,
        PlatformConfigManager $platformConfigManager)
    {
        parent::__construct();

        $this->userManager           = $userManager;
        $this->volunteerManager      = $volunteerManager;
        $this->platformConfigManager = $platformConfigManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('user:create')
            ->setDescription('Create users based on volunteer nivols')
            ->addArgument('platform', InputArgument::REQUIRED, 'Platform for which to create user')
            ->addArgument('external-id', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'External IDs from which to create a new user');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $platform = $input->getArgument('platform');

        foreach ($input->getArgument('external-id') as $externalId) {
            $volunteer = $this->volunteerManager->findOneByNivol($platform, $externalId);

            if (!$volunteer) {
                $output->writeln(sprintf('KO %s: nivol do not exist', $externalId));
                continue;
            }

            if (!$volunteer->getEmail()) {
                $output->writeln(sprintf('KO: %s: volunteer has no email', $externalId));
                continue;
            }

            if ($this->userManager->findOneByUsername($volunteer->getEmail())) {
                $output->writeln(sprintf('KO %s: user having this email already exist', $externalId));
                continue;
            }

            if ($this->userManager->findOneByExternalId($platform, $externalId)) {
                $output->writeln(sprintf('KO %s: nivol already connected to a user', $externalId));
                continue;
            }

            $user = new User();

            $platform = $this->platformConfigManager->getPlaform($volunteer->getPlatform());

            $user->setPlatform($platform->getName());
            $user->setLocale($platform->getDefaultLanguage()->getLocale());
            $user->setTimezone($platform->getTimezone());

            $user->setUsername($volunteer->getEmail());
            $user->setPassword(Uuid::uuid4());
            $user->setIsVerified(true);
            $user->setIsTrusted(true);
            $this->userManager->save($user);

            $this->userManager->changeVolunteer($user, $platform, $externalId);

            $output->writeln(sprintf('OK %s: user created', $externalId));
        }

        return 0;
    }
}