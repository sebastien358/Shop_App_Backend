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

    public function findAllCommandByClient(User $user, int $page, int $limit)
    {
       return $this->createQueryBuilder('command')
           ->where('command.user = :user')
           ->setParameter('user', $user)
           ->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit)
           ->orderBy('command.createdAt', 'DESC')
           ->getQuery()
           ->getResult();
    }

    public function findAllCountCommand(User $user)
    {
        return $this->createQueryBuilder('command')
            ->select('count(command.id)')
            ->where('command.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

// --- Pour l'admin : toutes les commandes payées ---

    public function findAllPaidCommands(): array
    {
        return $this->createQueryBuilder('command')
            ->leftJoin('command.commandItems', 'ci')
            ->addSelect('ci')
            ->where('command.status = :paid')
            ->setParameter('paid', Command::STATUS_PAID)
            ->orderBy('command.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
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
