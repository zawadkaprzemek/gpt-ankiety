<?php

namespace App\Repository;

use App\Entity\Page;
use App\Entity\Polling;
use App\Entity\Question;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Question>
 *
 * @method Question|null find($id, $lockMode = null, $lockVersion = null)
 * @method Question|null findOneBy(array $criteria, array $orderBy = null)
 * @method Question[]    findAll()
 * @method Question[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    public function add(Question $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Question $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


    public function getPollingQuestionsSorted(Polling $polling,Page $page)
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.polling = :polling')
            ->andWhere('q.page = :page')
            ->addOrderBy('q.sort', 'ASC')
            ->setParameter('polling',$polling)
            ->setParameter('page',$page)
            ->getQuery()
            ->getResult()
            ;
    }

    public function getQuestionsCountFromPreviousPages(Polling $polling,Page $page)
    {
        return $this->createQueryBuilder('q')
            ->select('count(q.id) as count')
            ->join('q.page','p')
            ->andWhere('q.polling = :polling')
            ->andWhere('p.number < :number')
            ->addOrderBy('q.sort', 'ASC')
            ->setParameter('polling',$polling)
            ->setParameter('number',$page->getNumber())
            ->getQuery()
            ->getSingleScalarResult()
            ;
    }

    public function getPreviousQuestionsCountFromCurrentPage(Polling $polling,Page $page,int $sort)
    {
        return $this->createQueryBuilder('q')
            ->select('count(q.id) as count')
            ->andWhere('q.polling = :polling')
            ->andWhere('q.page = :page')
            ->andWhere('q.sort < :sort')
            ->addOrderBy('q.sort', 'ASC')
            ->setParameter('polling',$polling)
            ->setParameter('page',$page)
            ->setParameter('sort',$sort)
            ->getQuery()
            ->getSingleScalarResult()
            ;
    }

    public function getQuestionsFromPageWithHiggerSort(Polling $polling,Page $page,int $sort)
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.polling = :polling')
            ->andWhere('q.page = :page')
            ->andWhere('q.sort > :sort')
            ->addOrderBy('q.sort', 'ASC')
            ->setParameter('polling',$polling)
            ->setParameter('page',$page)
            ->setParameter('sort',$sort)
            ->getQuery()
            ->getResult()
            ;
    }

//    /**
//     * @return Question[] Returns an array of Question objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('q')
//            ->andWhere('q.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('q.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Question
//    {
//        return $this->createQueryBuilder('q')
//            ->andWhere('q.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
