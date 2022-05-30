<?php

namespace App\Repository;

use App\Entity\Polling;
use App\Entity\SessionUser;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<SessionUser>
 *
 * @method SessionUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method SessionUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method SessionUser[]    findAll()
 * @method SessionUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SessionUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SessionUser::class);
    }

    public function add(SessionUser $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SessionUser $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getAllUsersForPolling(Polling $polling)
    {
        return $this->createQueryBuilder('su')
            ->join('su.code','c')
            ->andWhere('c.polling = :polling')
            ->setParameter('polling',$polling)
            ->getQuery()
            ->getResult()
            ;
    }

//    /**
//     * @return SessionUser[] Returns an array of SessionUser objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?SessionUser
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
