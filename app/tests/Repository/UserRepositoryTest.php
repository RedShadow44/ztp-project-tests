<?php

/**
 * User repository tests.
 */

namespace App\Tests\Repository;

use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class UserRepositoryTest.
 */
class UserRepositoryTest extends KernelTestCase
{
    /**
     * User repository instance.
     */
    private ?UserRepository $userRepository;

    /**
     * Set up test environment.
     *
     * Initializes UserRepository from service container.
     */
    public function setUp(): void
    {
        $container = static::getContainer();

        $this->userRepository = $container->get(UserRepository::class);
    }

    /**
     * Test successful password upgrade.
     *
     * Ensures password is updated and persisted correctly.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testUpgradePassword(): void
    {
        // given
        $user = new User();

        $user->setEmail(
            'upgrade'.uniqid().'@example.com'
        );

        $user->setPassword('old_password');

        $user->setRoles([
            UserRole::ROLE_USER->value,
        ]);

        $this->userRepository->save($user);

        // when
        $this->userRepository->upgradePassword(
            $user,
            'new_password'
        );

        // then
        $updatedUser = $this->userRepository->find(
            $user->getId()
        );

        $this->assertEquals(
            'new_password',
            $updatedUser->getPassword()
        );
    }

    /**
     * Test upgrade password throws exception for unsupported user type.
     */
    public function testUpgradePasswordException(): void
    {
        // given
        $this->expectException(
            \Symfony\Component\Security\Core\Exception\UnsupportedUserException::class
        );

        $unsupportedUser = $this->createMock(
            \Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface::class
        );

        // when
        $this->userRepository->upgradePassword(
            $unsupportedUser,
            'password'
        );
    }
}
