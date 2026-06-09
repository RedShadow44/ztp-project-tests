<?php

/**
 * Book service tests.
 */

namespace App\Tests\Service;

use App\Dto\BookListInputFiltersDto;
use App\Entity\Book;
use App\Entity\Category;
use App\Service\BookService;
use App\Service\BookServiceInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class BookServiceTest.
 */
class BookServiceTest extends KernelTestCase
{
    /**
     * Entity manager instance.
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * Book service under test.
     */
    private ?BookServiceInterface $bookService;

    /**
     * Set up test environment.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function setUp(): void
    {
        $container = static::getContainer();

        $this->entityManager = $container->get('doctrine.orm.entity_manager');
        $this->bookService = $container->get(BookService::class);
    }

    /**
     * Test saving a book entity.
     *
     * Ensures the book is persisted and retrievable from database.
     *
     * @throws ORMException
     */
    public function testSave(): void
    {
        // given
        $category = new Category();
        $category->setTitle('Test Category');

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $expectedBook = new Book();
        $expectedBook->setTitle('Test Book');
        $expectedBook->setAuthor('Test Author');
        $expectedBook->setDescription('Test Description');
        $expectedBook->setCategory($category);
        $expectedBook->setAvailable(true);

        // when
        $this->bookService->save($expectedBook);

        // then
        $expectedBookId = $expectedBook->getId();

        $resultBook = $this->entityManager->createQueryBuilder()
            ->select('book')
            ->from(Book::class, 'book')
            ->where('book.id = :id')
            ->setParameter(':id', $expectedBookId, Types::INTEGER)
            ->getQuery()
            ->getSingleResult();

        $this->assertEquals($expectedBook, $resultBook);
    }

    /**
     * Test deleting a book entity.
     *
     * Ensures entity is removed from persistence layer.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testDelete(): void
    {
        // given
        $category = new Category();
        $category->setTitle('Test Category');

        $this->entityManager->persist($category);

        $bookToDelete = new Book();
        $bookToDelete->setTitle('Book To Delete');
        $bookToDelete->setAuthor('Author');
        $bookToDelete->setDescription('Description');
        $bookToDelete->setCategory($category);
        $bookToDelete->setAvailable(true);

        $this->entityManager->persist($bookToDelete);
        $this->entityManager->flush();

        $deletedBookId = $bookToDelete->getId();

        // when
        $this->bookService->delete($bookToDelete);

        // then
        $resultBook = $this->entityManager->createQueryBuilder()
            ->select('book')
            ->from(Book::class, 'book')
            ->where('book.id = :id')
            ->setParameter(':id', $deletedBookId, Types::INTEGER)
            ->getQuery()
            ->getOneOrNullResult();

        $this->assertNull($resultBook);
    }

    /**
     * Test setting book availability.
     *
     * Ensures availability flag is properly updated.
     */
    public function testSetAvailable(): void
    {
        // given
        $book = new Book();
        $book->setAvailable(true);

        // when
        $this->bookService->setAvailable($book, false);

        // then
        $this->assertFalse($book->isAvailable());
    }

    /**
     * Test paginated books for category.
     *
     * Ensures pagination returns correct number of results per category.
     */
    public function testGetPaginatedBooksForCategory(): void
    {
        // given
        $page = 1;
        $expectedResultSize = 3;

        $category = new Category();
        $category->setTitle('Fantasy');

        $this->entityManager->persist($category);

        $counter = 0;

        while ($counter < $expectedResultSize) {
            $book = new Book();
            $book->setTitle('Book #'.$counter);
            $book->setAuthor('Author');
            $book->setDescription('Description');
            $book->setCategory($category);
            $book->setAvailable(true);

            $this->bookService->save($book);

            ++$counter;
        }

        // when
        $result = $this->bookService
            ->getPaginatedBooksForCategory($page, $category);

        // then
        $this->assertEquals($expectedResultSize, $result->count());
    }

    /**
     * Test paginated book list with filters.
     *
     * Ensures filtering logic is applied correctly.
     */
    public function testGetPaginatedList(): void
    {
        // given
        $page = 1;
        $expectedResultSize = 3;

        $category = new Category();
        $category->setTitle('Sci-Fi');

        $this->entityManager->persist($category);

        $counter = 0;

        while ($counter < $expectedResultSize) {
            $book = new Book();
            $book->setTitle('Test Book #'.$counter);
            $book->setAuthor('Author #'.$counter);
            $book->setDescription('Description');
            $book->setCategory($category);
            $book->setAvailable(true);

            $this->bookService->save($book);

            ++$counter;
        }

        $filters = new BookListInputFiltersDto();

        // when
        $result = $this->bookService
            ->getPaginatedList($page, $filters);

        // then
        $this->assertEquals($expectedResultSize, $result->count());
    }
}
