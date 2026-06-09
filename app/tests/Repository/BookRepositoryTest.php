<?php

/**
 * Book repository tests.
 */

namespace App\Tests\Repository;

use App\Dto\BookListFiltersDto;
use App\Entity\Book;
use App\Entity\Category;
use App\Entity\Tag;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class BookRepositoryTest.
 */
class BookRepositoryTest extends KernelTestCase
{
    /**
     * Doctrine entity manager instance.
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * Book repository instance.
     */
    private ?BookRepository $bookRepository;

    /**
     * Set up test environment.
     *
     * Initializes Doctrine entity manager and Book repository.
     */
    public function setUp(): void
    {
        $container = static::getContainer();

        $this->entityManager = $container->get('doctrine.orm.entity_manager');

        $this->bookRepository = $this->entityManager
            ->getRepository(Book::class);
    }

    /**
     * Test retrieving books for a given category.
     *
     * Ensures only books belonging to the category are returned.
     */
    public function testFindBooksForCategory(): void
    {
        // given
        $category = new Category();
        $category->setTitle('Fantasy');

        $this->entityManager->persist($category);

        $book1 = new Book();
        $book1->setTitle('Book 1');
        $book1->setAuthor('Author');
        $book1->setDescription('Desc');
        $book1->setCategory($category);

        $book2 = new Book();
        $book2->setTitle('Book 2');
        $book2->setAuthor('Author');
        $book2->setDescription('Desc');
        $book2->setCategory($category);

        $this->entityManager->persist($book1);
        $this->entityManager->persist($book2);
        $this->entityManager->flush();

        // when
        $result = $this->bookRepository->findBooksForCategory($category);

        // then
        $this->assertCount(2, $result);
    }

    /**
     * Test counting books by category.
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function testCountByCategory(): void
    {
        // given
        $category = new Category();
        $category->setTitle('Fantasy');

        $this->entityManager->persist($category);

        $counter = 0;

        while ($counter < 3) {
            $book = new Book();
            $book->setTitle('Book '.$counter);
            $book->setAuthor('Author');
            $book->setDescription('Description');
            $book->setCategory($category);

            $this->entityManager->persist($book);

            ++$counter;
        }

        $this->entityManager->flush();

        // when
        $result = $this->bookRepository
            ->countByCategory($category);

        // then
        $this->assertEquals(3, $result);
    }

    /**
     * Test queryAll without any filters.
     */
    public function testQueryAllWithoutFilters(): void
    {
        // given
        $category = new Category();
        $category->setTitle('Horror');

        $this->entityManager->persist($category);

        $book = new Book();
        $book->setTitle('Test Book');
        $book->setAuthor('Author');
        $book->setDescription('Desc');
        $book->setCategory($category);

        $this->entityManager->persist($book);
        $this->entityManager->flush();

        $filters = new BookListFiltersDto(null, null, null, null);

        // when
        $result = $this->bookRepository->queryAll($filters)->getQuery()->getResult();

        // then
        $this->assertNotEmpty($result);
    }

    /**
     * Test queryAll filtered by title.
     */
    public function testQueryAllWithTitleFilter(): void
    {
        // given
        $category = new Category();
        $category->setTitle('Drama');

        $this->entityManager->persist($category);

        $book = new Book();
        $book->setTitle('UniqueTitle');
        $book->setAuthor('Author');
        $book->setDescription('Desc');
        $book->setCategory($category);

        $this->entityManager->persist($book);
        $this->entityManager->flush();

        $filters = new BookListFiltersDto(null, null, 'UniqueTitle', null);

        // when
        $result = $this->bookRepository->queryAll($filters)->getQuery()->getResult();

        // then
        $this->assertCount(1, $result);
    }

    /**
     * Test queryAll filtered by author.
     */
    public function testQueryAllWithAuthorFilter(): void
    {
        // given
        $category = new Category();
        $category->setTitle('Drama');

        $this->entityManager->persist($category);

        $book = new Book();
        $book->setTitle('Book');
        $book->setAuthor('UniqueAuthor');
        $book->setDescription('Desc');
        $book->setCategory($category);

        $this->entityManager->persist($book);
        $this->entityManager->flush();

        $filters = new BookListFiltersDto(null, null, null, 'UniqueAuthor');

        // when
        $result = $this->bookRepository->queryAll($filters)->getQuery()->getResult();

        // then
        $this->assertCount(1, $result);
    }

    /**
     * Test queryAll filtered by category.
     */
    public function testQueryAllWithCategoryFilter(): void
    {
        // given
        $category = new Category();
        $category->setTitle('UniqueCategory');

        $this->entityManager->persist($category);

        $book = new Book();
        $book->setTitle('Book');
        $book->setAuthor('Author');
        $book->setDescription('Desc');
        $book->setCategory($category);

        $this->entityManager->persist($book);
        $this->entityManager->flush();

        $filters = new BookListFiltersDto($category, null, null, null);

        // when
        $result = $this->bookRepository->queryAll($filters)->getQuery()->getResult();

        // then
        $this->assertCount(1, $result);
    }

    /**
     * Test queryAll filtered by tag.
     */
    public function testQueryAllWithTagFilter(): void
    {
        // given
        $category = new Category();
        $category->setTitle('Category');

        $tag = new Tag();
        $tag->setTitle('UniqueTag');

        $this->entityManager->persist($category);
        $this->entityManager->persist($tag);

        $book = new Book();
        $book->setTitle('Book');
        $book->setAuthor('Author');
        $book->setDescription('Desc');
        $book->setCategory($category);
        $book->addTag($tag);

        $this->entityManager->persist($book);
        $this->entityManager->flush();

        $filters = new BookListFiltersDto(null, $tag, null, null);

        // when
        $result = $this->bookRepository->queryAll($filters)->getQuery()->getResult();

        // then
        $this->assertCount(1, $result);
    }
}
