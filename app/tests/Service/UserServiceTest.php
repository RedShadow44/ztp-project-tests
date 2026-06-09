<?php

/**
 * User service tests.
 */

namespace App\Tests\Service;

use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Service\UserService;
use App\Service\UserServiceInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class UserServiceTest.
 */
class UserServiceTest extends KernelTestCase
{
    /**
     * Entity manager instance.
     *
     * @var EntityManagerInterface|null
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * User service under test.
     *
     * @var UserServiceInterface|null
     */
    private ?UserServiceInterface $userService;

    /**
     * Set up test environment.
     *
     * Initializes entity manager and service from Symfony container.
     *
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function setUp(): void
    {
        $container = static::getContainer();

        $this->entityManager = $container->get('doctrine.orm.entity_manager');
        $this->userService = $container->get(UserService::class);
    }

    /**
     * Test saving a user.
     *
     * Ensures user entity is persisted correctly.
     *
     * @return void
     * @throws ORMException
     */
    public function testSave(): void
    {
        // given
        $expectedUser = new User();
        $expectedUser->setEmail('save'.uniqid().'@example.com');
        $expectedUser->setPassword('password');
        $expectedUser->setRoles([UserRole::ROLE_USER->value]);

        // when
        $this->userService->save($expectedUser);

        // then
        $expectedUserId = $expectedUser->getId();

        $resultUser = $this->entityManager->createQueryBuilder()
            ->select('user')
            ->from(User::class, 'user')
            ->where('user.id = :id')
            ->setParameter(':id', $expectedUserId, Types::INTEGER)
            ->getQuery()
            ->getSingleResult();

        $this->assertEquals($expectedUser, $resultUser);
    }

    /**
     * Test deleting a user.
     *
     * Ensures user is removed from persistence layer.
     *
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testDelete(): void
    {
        // given
        $userToDelete = new User();
        $userToDelete->setEmail('delete'.uniqid().'@example.com');
        $userToDelete->setPassword('password');
        $userToDelete->setRoles([UserRole::ROLE_USER->value]);

        $this->entityManager->persist($userToDelete);
        $this->entityManager->flush();

        $deletedUserId = $userToDelete->getId();

        // when
        $this->userService->delete($userToDelete);

        // then
        $resultUser = $this->entityManager->createQueryBuilder()
            ->select('user')
            ->from(User::class, 'user')
            ->where('user.id = :id')
            ->setParameter(':id', $deletedUserId, Types::INTEGER)
            ->getQuery()
            ->getOneOrNullResult();

        $this->assertNull($resultUser);
    }

    /**
     * Test retrieving paginated user list.
     *
     * Ensures pagination returns at least created users.
     *
     * @return void
     */
    public function testGetPaginatedList(): void
    {
        // given
        $page = 1;
        $expectedResultSize = 3;

        $counter = 0;

        while ($counter < $expectedResultSize) {
            $user = new User();
            $user->setEmail('user'.$counter.uniqid().'@example.com');
            $user->setPassword('password');
            $user->setRoles([UserRole::ROLE_USER->value]);

            $this->userService->save($user);

            ++$counter;
        }

        // when
        $result = $this->userService->getPaginatedList($page);

        // then
        $this->assertGreaterThanOrEqual(
            $expectedResultSize,
            $result->count()
        );
    }

    /**
     * Test detecting last admin.
     *
     * Ensures service correctly identifies single remaining admin.
     *
     * @return void
     */
    public function testIsLastAdmin(): void
    {
        // given
        $admin = new User();
        $admin->setEmail('admin'.uniqid().'@example.com');
        $admin->setPassword('password');
        $admin->setRoles([UserRole::ROLE_ADMIN->value]);

        $this->userService->save($admin);

        // when
        $result = $this->userService->isLastAdmin($admin);

        // then
        $this->assertTrue($result);
    }

    /**
     * Test detecting when user is not last admin.
     *
     * Ensures service returns false when multiple admins exist.
     *
     * @return void
     */
    public function testIsNotLastAdmin(): void
    {
        // given
        $admin1 = new User();
        $admin1->setEmail('admin1'.uniqid().'@example.com');
        $admin1->setPassword('password');
        $admin1->setRoles([UserRole::ROLE_ADMIN->value]);

        $admin2 = new User();
        $admin2->setEmail('admin2'.uniqid().'@example.com');
        $admin2->setPassword('password');
        $admin2->setRoles([UserRole::ROLE_ADMIN->value]);

        $this->userService->save($admin1);
        $this->userService->save($admin2);

        // when
        $result = $this->userService->isLastAdmin($admin1);

        // then
        $this->assertFalse($result);
    }
}