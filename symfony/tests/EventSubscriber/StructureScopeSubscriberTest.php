<?php

namespace App\Tests\EventSubscriber;

use App\Entity\User;
use App\Tests\Fixtures\DataFixtures;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AllowMockObjectsWithoutExpectations]
class StructureScopeSubscriberTest extends KernelTestCase
{
    private DataFixtures $fixtures;
    private EntityManagerInterface $em;

    protected function setUp() : void
    {
        self::bootKernel();

        $container = static::getContainer();

        $this->em = $container->get('doctrine.orm.entity_manager');
        $this->fixtures = new DataFixtures(
            $this->em,
            $container->get('security.password_hasher')
        );
    }

    private function dispatchLoginSuccess(User $user) : void
    {
        $event = new LoginSuccessEvent(
            $this->createMock(AuthenticatorInterface::class),
            new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), function () use ($user) {
                return $user;
            })),
            new UsernamePasswordToken($user, 'main', $user->getRoles()),
            Request::create('/'),
            null,
            'main'
        );

        static::getContainer()->get('event_dispatcher')->dispatch($event);
    }

    public function testLoginMaterializesMissingDescendantStructures() : void
    {
        $user   = $this->fixtures->createRawUser('scope_sub1@test.com');
        $parent = $this->fixtures->createStructure('Login Parent DT', 'STR-SUB-PARENT');
        $this->fixtures->assignUserToStructure($user, $parent);

        // Sub-structure created after the assignment: absent from the
        // materialized user_structure rows until the next login.
        $child = $this->fixtures->createStructure('Login Child UL', 'STR-SUB-CHILD');
        $child->setParentStructure($parent);
        $this->em->persist($child);
        $this->em->flush();

        $this->dispatchLoginSuccess($user);

        $this->em->clear();
        $fresh = $this->em->getRepository(User::class)->find($user->getId());
        $ids   = array_map(function ($structure) {
            return $structure->getId();
        }, $fresh->getStructures(false)->toArray());

        $this->assertContains($child->getId(), $ids);
    }

    public function testLoginWithoutStructuresDoesNotFail() : void
    {
        $user = $this->fixtures->createRawUser('scope_sub2@test.com');

        $this->dispatchLoginSuccess($user);

        $this->em->clear();
        $fresh = $this->em->getRepository(User::class)->find($user->getId());

        $this->assertCount(0, $fresh->getStructures(false));
    }
}
