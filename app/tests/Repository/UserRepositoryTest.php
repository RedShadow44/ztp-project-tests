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
     * Test query all.
     */
    public function testQueryAll(): void
    {
        // when
        $queryBuilder = $this->userRepository->queryAll();

        // then
        $this->assertNotNull($queryBuilder);
    }

    /**
     * Test find by role.
     */
    public function testFindByRole(): void
    {
        // given
        $admin = new User();
        $admin->setEmail('admin'.uniqid().'@example.com');
        $admin->setPassword('password');
        $admin->setRoles([UserRole::ROLE_ADMIN->value]);

        $this->userRepository->save($admin);

        // when
        $result = $this->userRepository
            ->findByRole(UserRole::ROLE_ADMIN->value);

        // then
        $this->assertNotEmpty($result);
    }
}
