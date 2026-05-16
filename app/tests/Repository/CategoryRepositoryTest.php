<?php

namespace App\Tests\Repository;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CategoryRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;

    private ?CategoryRepository $categoryRepository;

    public function setUp(): void
    {
        $container = static::getContainer();

        $this->entityManager = $container->get('doctrine.orm.entity_manager');

        $this->categoryRepository = $this->entityManager
            ->getRepository(Category::class);
    }

    /**
     * Test queryAll().
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