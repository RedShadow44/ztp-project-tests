<?php

/**
 * Category repository tests.
 */

namespace App\Tests\Repository;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class CategoryRepositoryTest.
 */
class CategoryRepositoryTest extends KernelTestCase
{
    /**
     * Doctrine entity manager instance.
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * Category repository instance.
     */
    private ?CategoryRepository $categoryRepository;

    /**
     * Set up test environment.
     *
     * Initializes Doctrine entity manager and Category repository.
     */
    public function setUp(): void
    {
        $container = static::getContainer();

        $this->entityManager = $container->get('doctrine.orm.entity_manager');

        $this->categoryRepository = $this->entityManager
            ->getRepository(Category::class);
    }

    /**
     * Test queryAll() returns non-empty result set.
     *
     * Ensures persisted categories are returned from repository query.
     */
    public function testQueryAll(): void
    {
        // given
        $category = new Category();
        $category->setTitle('Category '.uniqid());

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        // when
        $result = $this->categoryRepository
            ->queryAll()
            ->getQuery()
            ->getResult();

        // then
        $this->assertNotEmpty($result);
    }
}
