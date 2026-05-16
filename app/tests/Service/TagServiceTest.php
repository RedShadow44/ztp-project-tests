<?php

namespace App\Tests\Service;

use App\Entity\Tag;
use App\Service\TagService;
use App\Service\TagServiceInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TagServiceTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;

    private ?TagServiceInterface $tagService;

    public function setUp(): void
    {
        $container = static::getContainer();

        $this->entityManager = $container->get('doctrine.orm.entity_manager');

        $this->tagService = $container->get(TagService::class);
    }

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

    public function testFindOneByTitle(): void
    {
        $tag = new Tag();
        $tag->setTitle('UniqueTag '.uniqid());

        $this->entityManager->persist($tag);
        $this->entityManager->flush();

        $result = $this->tagService->findOneByTitle($tag->getTitle());

        $this->assertEquals($tag, $result);
    }

    public function testFindOneById(): void
    {
        $tag = new Tag();
        $tag->setTitle('Tag '.uniqid());

        $this->entityManager->persist($tag);
        $this->entityManager->flush();

        $result = $this->tagService->findOneById($tag->getId());

        $this->assertEquals($tag, $result);
    }

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
