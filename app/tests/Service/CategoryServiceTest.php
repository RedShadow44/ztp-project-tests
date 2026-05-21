<?php

namespace Service;

use App\Entity\Book;
use App\Entity\Category;
use App\Service\CategoryService;
use App\Service\CategoryServiceInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CategoryServiceTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;

    private ?CategoryServiceInterface $categoryService;

    public function setUp(): void
    {
        $container = static::getContainer();

        $this->entityManager = $container->get('doctrine.orm.entity_manager');

        $this->categoryService = $container->get(CategoryService::class);
    }

    /**
     * Test save().
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
     * Test delete().
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
     * Test findOneById().
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
     * Test getPaginatedList().
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
     * Test canBeDeleted() when category is empty.
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
     * Test canBeDeleted() when category contains books.
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
