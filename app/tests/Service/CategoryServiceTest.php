<?php

/**
 * Category service tests.
 */

namespace Service;

use App\Entity\Book;
use App\Entity\Category;
use App\Service\CategoryService;
use App\Service\CategoryServiceInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class CategoryServiceTest.
 */
class CategoryServiceTest extends KernelTestCase
{
    /**
     * Entity manager instance.
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * Category service under test.
     */
    private ?CategoryServiceInterface $categoryService;

    /**
     * Set up test environment.
     *
     * Initializes service and entity manager from container.
     */
    public function setUp(): void
    {
        $container = static::getContainer();

        $this->entityManager = $container->get('doctrine.orm.entity_manager');

        $this->categoryService = $container->get(CategoryService::class);
    }

    /**
     * Test saving a category.
     *
     * Ensures category is persisted in database.
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function testSave(): void
    {
        // given
        $category = new Category();
        $category->setTitle('Category '.uniqid());

        // when
        $this->categoryService->save($category);

        // then
        $result = $this->entityManager
            ->createQueryBuilder()
            ->select('category')
            ->from(Category::class, 'category')
            ->where('category.id = :id')
            ->setParameter('id', $category->getId(), Types::INTEGER)
            ->getQuery()
            ->getSingleResult();

        $this->assertEquals($category, $result);
    }

    /**
     * Test deleting a category.
     *
     * Ensures category is removed from persistence layer.
     *
     * @throws NonUniqueResultException
     */
    public function testDelete(): void
    {
        // given
        $category = new Category();
        $category->setTitle('Category '.uniqid());

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $id = $category->getId();

        // when
        $this->categoryService->delete($category);

        // then
        $result = $this->entityManager
            ->createQueryBuilder()
            ->select('category')
            ->from(Category::class, 'category')
            ->where('category.id = :id')
            ->setParameter('id', $id, Types::INTEGER)
            ->getQuery()
            ->getOneOrNullResult();

        $this->assertNull($result);
    }

    /**
     * Test finding category by id.
     *
     * @throws NonUniqueResultException
     */
    public function testFindOneById(): void
    {
        // given
        $category = new Category();
        $category->setTitle('Category '.uniqid());

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        // when
        $result = $this->categoryService->findOneById($category->getId());

        // then
        $this->assertEquals($category, $result);
    }

    /**
     * Test paginated category list retrieval.
     *
     * Ensures pagination returns at least created entities.
     */
    public function testGetPaginatedList(): void
    {
        // given
        $counter = 0;

        while ($counter < 3) {
            $category = new Category();
            $category->setTitle('Category '.$counter.' '.uniqid());

            $this->categoryService->save($category);

            ++$counter;
        }

        // when
        $result = $this->categoryService->getPaginatedList(1);

        // then
        $this->assertGreaterThanOrEqual(3, $result->count());
    }

    /**
     * Test canBeDeleted returns true when category has no books.
     */
    public function testCanBeDeletedTrue(): void
    {
        // given
        $category = new Category();
        $category->setTitle('Category '.uniqid());

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        // when
        $result = $this->categoryService->canBeDeleted($category);

        // then
        $this->assertTrue($result);
    }

    /**
     * Test canBeDeleted returns false when category contains books.
     */
    public function testCanBeDeletedFalse(): void
    {
        // given
        $category = new Category();
        $category->setTitle('Category '.uniqid());

        $this->entityManager->persist($category);

        $book = new Book();
        $book->setTitle('Book '.uniqid());
        $book->setAuthor('Author');
        $book->setDescription('Description');
        $book->setCategory($category);

        $this->entityManager->persist($book);
        $this->entityManager->flush();

        // when
        $result = $this->categoryService->canBeDeleted($category);

        // then
        $this->assertFalse($result);
    }
}
