<?php

namespace App\Repository;

use App\Entity\Command;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Command>
 */
class CommandRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Command::class);
    }

    public function findAllCommandUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.commandItems', 'ci')->addSelect('ci')
            ->leftJoin('ci.product', 'p')->addSelect('p')
            ->leftJoin('p.pictures', 'pic')->addSelect('pic')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getArrayResult();
    }

    //    /**
    //     * @return Command[] Returns an array of Command objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Command
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
