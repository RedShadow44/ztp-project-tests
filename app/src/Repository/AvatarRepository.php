<?php
/**
 * Avatar repository.
 */

namespace App\Repository;

use App\Entity\Avatar;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Avatar>
 */
class AvatarRepository extends ServiceEntityRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry Manager registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Avatar::class);
    }// end __construct()

    /**
     * Save entity.
     *
     * @param Avatar $avatar Avatar entity
     */
    public function save(Avatar $avatar): void
    {
        // assert($this->_em instanceof EntityManager);
        $this->_em->persist($avatar);
        $this->_em->flush();
    }// end save()
}// end class
