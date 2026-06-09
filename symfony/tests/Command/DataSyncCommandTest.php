<?php

namespace App\Tests\Command;

use App\Entity\Volunteer;
use App\Manager\VolunteerManager;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DataSyncCommandTest extends KernelTestCase
{
    public function testDirOptionRunsSyncInlineFromLocalDirectory()
    {
        self::bootKernel();
        $container        = self::getContainer();
        $em               = $container->get('doctrine.orm.entity_manager');
        $volunteerManager = $container->get(VolunteerManager::class);

        $application = new Application(self::$kernel);
        $command     = $application->find('sync:data');
        $tester      = new CommandTester($command);

        $exitCode = $tester->execute([
            '--dir' => $container->getParameter('kernel.project_dir').'/tests/Fixtures/sync',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Running sync inline from', $tester->getDisplay());
        $this->assertStringContainsString('Done.', $tester->getDisplay());

        $em->clear();

        // Sanity check: PSE2_OK volunteer from the fixture set must have been
        // imported with their PSE2 badge.
        $volunteer = $volunteerManager->findOneByExternalId('T0000000001B');
        $this->assertNotNull($volunteer);
        $externalIds = array_map(fn ($b) => $b->getExternalId(), $volunteer->getBadges(false)->toArray());
        $this->assertContains('training-167', $externalIds);
    }

    public function testDirOptionRejectsUnknownDirectory()
    {
        self::bootKernel();

        $application = new Application(self::$kernel);
        $command     = $application->find('sync:data');
        $tester      = new CommandTester($command);

        $exitCode = $tester->execute([
            '--dir' => '/nonexistent/path/'.bin2hex(random_bytes(4)),
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Directory does not exist', $tester->getDisplay());
    }
}
