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
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class RentalServiceTest.
 */
class RentalServiceTest extends KernelTestCase
{
    /**
     * Entity manager instance.
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * Rental service under test.
     */
    private ?RentalServiceInterface $rentalService;

    /**
     * Rental repository instance.
     */
    private ?RentalRepository $rentalRepository;

    /**
     * Set up test environment.
     *
     * Initializes services and Doctrine entity manager from container.
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
     * Test renting a book.
     *
     * Ensures rental is created correctly and book is marked unavailable.
     */
    public function testRentBook(): void
    {
        $user = $this->createUser();
        $book = $this->createBook();

        $rental = $this->rentalService->rentBook($book, $user);

        $this->assertInstanceOf(Rental::class, $rental);
        $this->assertEquals($user->getId(), $rental->getOwner()->getId());
        $this->assertEquals($book->getId(), $rental->getBook()->getId());
        $this->assertFalse($rental->isStatus());
        $this->assertFalse($book->isAvailable());
    }

    /**
     * Test approving a rental.
     *
     * Ensures rental status is set to approved and ownership is updated.
     */
    public function testApproveRental(): void
    {
        $user = $this->createUser();
        $book = $this->createBook();

        $rental = $this->rentalService->rentBook($book, $user);

        $this->rentalService->approveRental($rental);

        $this->assertTrue($rental->isStatus());
        $this->assertEquals($user->getId(), $rental->getBook()->getOwner()->getId());
    }

    /**
     * Test paginated rentals filtered by status.
     *
     * Ensures only pending rentals are returned.
     */
    public function testGetPaginatedByStatus(): void
    {
        $page = 1;
        $expectedResultSize = 2;

        $counter = 0;

        while ($counter < $expectedResultSize) {
            $user = $this->createUser();
            $book = $this->createBook();

            $rental = $this->rentalService->rentBook($book, $user);

            $this->rentalService->save($rental);

            ++$counter;
        }

        $approvedRental = $this->rentalService->rentBook(
            $this->createBook(),
            $this->createUser()
        );

        $approvedRental->setStatus(true);
        $this->rentalService->save($approvedRental);

        $result = $this->rentalService->getPaginatedByStatus($page);

        $this->assertEquals($expectedResultSize, $result->count());

        foreach ($result as $rental) {
            $this->assertFalse($rental->isStatus());
        }
    }

    /**
     * Test paginated rentals filtered by owner.
     *
     * Ensures only rentals belonging to a specific user are returned.
     */
    public function testGetPaginatedByOwner(): void
    {
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

        $result = $this->rentalService
            ->getPaginatedByOwner($page, $user->getId());

        $this->assertEquals($expectedResultSize, $result->count());
    }

    /**
     * Test saving a rental entity.
     *
     * Ensures rental is persisted in database.
     */
    public function testSave(): void
    {
        $rental = new Rental();
        $rental->setOwner($this->createUser());
        $rental->setBook($this->createBook());
        $rental->setStatus(false);

        $this->rentalService->save($rental);

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
     * Test deleting a rental entity.
     *
     * Ensures rental is removed from persistence layer.
     *
     * @throws NonUniqueResultException
     */
    public function testDelete(): void
    {
        $user = $this->createUser();
        $book = $this->createBook();

        $rental = $this->rentalService->rentBook($book, $user);

        $deletedRentalId = $rental->getId();

        $this->rentalService->delete($rental);

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
     * Create test user entity.
     *
     * @return User User entity
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
     * Create test category entity.
     *
     * @return Category Category entity
     */
    private function createCategory(): Category
    {
        $repo = static::getContainer()->get(CategoryRepository::class);

        $category = new Category();
        $category->setTitle('Category '.uniqid());

        $repo->save($category);

        return $category;
    }

    /**
     * Create test book entity.
     *
     * @return Book Book entity
     */
    private function createBook(): Book
    {
        $repo = static::getContainer()->get(BookRepository::class);

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
