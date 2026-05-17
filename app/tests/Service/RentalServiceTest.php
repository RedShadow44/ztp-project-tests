<?php

/**
 * Rental service tests.
 */

namespace App\Tests\Service;

use App\Entity\Book;
use App\Entity\Category;
use App\Entity\Enum\UserRole;
use App\Entity\Rental;
use App\Entity\User;
use App\Repository\BookRepository;
use App\Repository\CategoryRepository;
use App\Repository\RentalRepository;
use App\Repository\UserRepository;
use App\Service\RentalService;
use App\Service\RentalServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class RentalServiceTest.
 */
class RentalServiceTest extends KernelTestCase
{
    /**
     * Entity manager.
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * Rental service.
     */
    private ?RentalServiceInterface $rentalService;

    /**
     * Rental repository.
     */
    private ?RentalRepository $rentalRepository;

    /**
     * Set up test.
     */
    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $this->entityManager = $container->get(
            'doctrine.orm.entity_manager'
        );

        $this->rentalService = $container->get(RentalService::class);

        $this->rentalRepository = $container->get(
            RentalRepository::class
        );
    }

    /**
     * Test rent book.
     */
    public function testRentBook(): void
    {
        // given
        $user = $this->createUser();
        $book = $this->createBook();

        // when
        $rental = $this->rentalService->rentBook($book, $user);

        // then
        $this->assertInstanceOf(Rental::class, $rental);
        $this->assertEquals($user->getId(), $rental->getOwner()->getId());
        $this->assertEquals($book->getId(), $rental->getBook()->getId());
        $this->assertFalse($rental->isStatus());
        $this->assertFalse($book->isAvailable());
    }

    /**
     * Test approve rental.
     */
    public function testApproveRental(): void
    {
        // given
        $user = $this->createUser();
        $book = $this->createBook();

        $rental = $this->rentalService->rentBook($book, $user);

        // when
        $this->rentalService->approveRental($rental);

        // then
        $this->assertTrue($rental->isStatus());

        $this->assertEquals($user->getId(), $rental->getBook()->getOwner()->getId());
    }

    /**
     * Test get paginated by status.
     */
    public function testGetPaginatedByStatus(): void
    {
        // given
        $page = 1;
        $expectedResultSize = 2;

        $counter = 0;

        while ($counter < $expectedResultSize) {
            $user = $this->createUser();
            $book = $this->createBook();

            $rental = $this->rentalService->rentBook($book, $user);

            // keep status = false
            $this->rentalService->save($rental);

            ++$counter;
        }

        // approved rental (should NOT appear)
        $approvedRental = $this->rentalService->rentBook(
            $this->createBook(),
            $this->createUser()
        );

        $approvedRental->setStatus(true);

        $this->rentalService->save($approvedRental);

        // when
        $result = $this->rentalService
            ->getPaginatedByStatus($page);

        // then
        $this->assertEquals($expectedResultSize, $result->count());

        foreach ($result as $rental) {
            $this->assertFalse($rental->isStatus());
        }
    }

    /**
     * Test get paginated by owner.
     */
    public function testGetPaginatedByOwner(): void
    {
        // given
        $page = 1;
        $expectedResultSize = 2;

        $user = $this->createUser();

        $counter = 0;

        while ($counter < $expectedResultSize) {
            $rental = new Rental();
            $rental->setOwner($user);
            $rental->setBook($this->createBook());
            $rental->setStatus(false);

            $this->rentalService->save($rental);

            ++$counter;
        }

        // when
        $result = $this->rentalService
            ->getPaginatedByOwner($page, $user->getId());

        // then
        $this->assertEquals($expectedResultSize, $result->count());
    }

    /**
     * Test save.
     */
    public function testSave(): void
    {
        // given
        $rental = new Rental();
        $rental->setOwner($this->createUser());
        $rental->setBook($this->createBook());
        $rental->setStatus(false);

        // when
        $this->rentalService->save($rental);

        // then
        $savedRental = $this->entityManager
            ->createQueryBuilder()
            ->select('rental')
            ->from(Rental::class, 'rental')
            ->where('rental.id = :id')
            ->setParameter('id', $rental->getId())
            ->getQuery()
            ->getSingleResult();

        $this->assertEquals($rental, $savedRental);
    }

    /**
     * Test delete.
     */
    public function testDelete(): void
    {
        // given
        $user = $this->createUser();
        $book = $this->createBook();

        $rental = $this->rentalService->rentBook($book, $user);

        $deletedRentalId = $rental->getId();

        // when
        $this->rentalService->delete($rental);

        // then
        $result = $this->entityManager
            ->createQueryBuilder()
            ->select('rental')
            ->from(Rental::class, 'rental')
            ->where('rental.id = :id')
            ->setParameter('id', $deletedRentalId)
            ->getQuery()
            ->getOneOrNullResult();

        $this->assertNull($result);
    }

    /**
     * Create user helper.
     */
    private function createUser(): User
    {
        $container = static::getContainer();

        $passwordHasher = $container->get('security.password_hasher');

        $repo = $container->get(UserRepository::class);

        $user = new User();
        $user->setEmail('user'.uniqid().'@example.com');
        $user->setRoles([UserRole::ROLE_USER->value]);

        $user->setPassword($passwordHasher->hashPassword($user, 'password'));

        $repo->save($user);

        return $user;
    }

    /**
     * Create category helper.
     */
    private function createCategory(): Category
    {
        $repo = static::getContainer()
            ->get(CategoryRepository::class);

        $category = new Category();
        $category->setTitle('Category '.uniqid());

        $repo->save($category);

        return $category;
    }

    /**
     * Create book helper.
     */
    private function createBook(): Book
    {
        $repo = static::getContainer()
            ->get(BookRepository::class);

        $book = new Book();
        $book->setTitle('Book '.uniqid());
        $book->setAuthor('Author');
        $book->setDescription('Description');
        $book->setAvailable(true);
        $book->setCategory($this->createCategory());

        $repo->save($book);

        return $book;
    }
}