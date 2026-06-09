<?php

/**
 * Tag service tests.
 */

namespace App\Tests\Service;

use App\Entity\Tag;
use App\Service\TagService;
use App\Service\TagServiceInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class TagServiceTest.
 */
class TagServiceTest extends KernelTestCase
{
    /**
     * Entity manager instance.
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * Tag service under test.
     */
    private ?TagServiceInterface $tagService;

    /**
     * Set up test environment.
     *
     * Initializes entity manager and service from container.
     */
    public function setUp(): void
    {
        $container = static::getContainer();

        $this->entityManager = $container->get('doctrine.orm.entity_manager');

        $this->tagService = $container->get(TagService::class);
    }

    /**
     * Test saving a tag.
     *
     * Ensures tag is persisted in the database.
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function testSave(): void
    {
        $tag = new Tag();
        $tag->setTitle('Test Tag '.uniqid());

        $this->tagService->save($tag);

        $result = $this->entityManager
            ->createQueryBuilder()
            ->select('tag')
            ->from(Tag::class, 'tag')
            ->where('tag.id = :id')
            ->setParameter('id', $tag->getId(), Types::INTEGER)
            ->getQuery()
            ->getSingleResult();

        $this->assertEquals($tag, $result);
    }

    /**
     * Test deleting a tag.
     *
     * Ensures tag is removed from persistence layer.
     *
     * @throws NonUniqueResultException
     */
    public function testDelete(): void
    {
        $tag = new Tag();
        $tag->setTitle('Test Tag '.uniqid());

        $this->entityManager->persist($tag);
        $this->entityManager->flush();

        $id = $tag->getId();

        $this->tagService->delete($tag);

        $result = $this->entityManager
            ->createQueryBuilder()
            ->select('tag')
            ->from(Tag::class, 'tag')
            ->where('tag.id = :id')
            ->setParameter('id', $id, Types::INTEGER)
            ->getQuery()
            ->getOneOrNullResult();

        $this->assertNull($result);
    }

    /**
     * Test finding tag by title.
     *
     * Ensures service correctly retrieves tag by its title.
     */
    public function testFindOneByTitle(): void
    {
        $tag = new Tag();
        $tag->setTitle('UniqueTag '.uniqid());

        $this->entityManager->persist($tag);
        $this->entityManager->flush();

        $result = $this->tagService->findOneByTitle($tag->getTitle());

        $this->assertEquals($tag, $result);
    }

    /**
     * Test finding tag by id.
     *
     * Ensures service correctly retrieves tag by its identifier.
     */
    public function testFindOneById(): void
    {
        $tag = new Tag();
        $tag->setTitle('Tag '.uniqid());

        $this->entityManager->persist($tag);
        $this->entityManager->flush();

        $result = $this->tagService->findOneById($tag->getId());

        $this->assertEquals($tag, $result);
    }

    /**
     * Test paginated tag list retrieval.
     *
     * Ensures pagination returns expected number of tags.
     */
    public function testGetPaginatedList(): void
    {
        $counter = 0;

        while ($counter < 3) {
            $tag = new Tag();
            $tag->setTitle('Tag '.$counter.' '.uniqid());

            $this->tagService->save($tag);

            ++$counter;
        }

        $result = $this->tagService->getPaginatedList(1);

        $this->assertEquals(3, $result->count());
    }
}
