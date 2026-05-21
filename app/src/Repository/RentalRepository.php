<?php

/**
 * Rental repository.
 */

namespace App\Repository;

use App\Entity\Rental;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rental>
 */
class RentalRepository extends ServiceEntityRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry Manager registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rental::class);
    }

    /**
     * Query all.
     *
     * @return QueryBuilder Query builder
     */
    public function queryAll(): QueryBuilder
    {
        return $this->getOrCreateQueryBuilder()
//            ->select(
//                'partial rental.{id, owner, book, status, rentalDate}',
//                'partial user.{id, email}',
//                'partial book.{id, title}'
//            )
            ->select('rental', 'user', 'book')
            ->join('rental.book', 'book')
            ->join('rental.owner', 'user');
    }

    /**
     * Query rental by status.
     *
     * @return QueryBuilder Query builder
     */
    public function queryByStatus(): QueryBuilder
    {
        return $this->queryAll()
             ->where('rental.status = :status')
             ->setParameter('status', false);
    }

    /**
     * Query rental by owner.
     *
     * @param int $owner Owner id
     *
     * @return QueryBuilder Query builder
     */
    public function queryByOwner(int $owner): QueryBuilder
    {
        return $this->queryAll()
            ->where('rental.owner = :owner')
            ->setParameter('owner', $owner);
    }

    /**
     * Save entity.
     *
     * @param Rental $rental Rental entity
     */
    public function save(Rental $rental): void
    {
        // assert($this->_em instanceof EntityManager);
        $this->_em->persist($rental);
        $this->_em->flush();
    }

    /**
     * Delete entity.
     *
     * @param Rental $rental Rental entity
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function delete(Rental $rental): void
    {
        // assert($this->_em instanceof EntityManager);
        $this->_em->remove($rental);
        $this->_em->flush();
    }

    /**
     * Get or create new query builder.
     *
     * @param QueryBuilder|null $queryBuilder Query builder
     *
     * @return QueryBuilder Query builder
     */
    private function getOrCreateQueryBuilder(?QueryBuilder $queryBuilder = null): QueryBuilder
    {
        return $queryBuilder ?? $this->createQueryBuilder('rental');
    }
}
