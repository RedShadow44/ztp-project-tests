<?php

/**
 * User repository tests.
 */

namespace App\Tests\Repository;

use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class UserRepositoryTest.
 */
class UserRepositoryTest extends KernelTestCase
{
    /**
     * User repository.
     */
    private ?UserRepository $userRepository;

    /**
     * Set up.
     */
    public function setUp(): void
    {
        $container = static::getContainer();

        $this->userRepository = $container->get(UserRepository::class);
    }

    /**
     * Test upgrade password.
     */
    public function testUpgradePassword(): void
    {
        // given
        $user = new User();

        $user->setEmail(
            'upgrade' . uniqid() . '@example.com'
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
     * Test upgrade password exception.
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