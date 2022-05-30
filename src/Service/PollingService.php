<?php

namespace App\Service;

use App\Entity\Page;
use App\Entity\Polling;
use App\Entity\Question;
use Doctrine\ORM\EntityManagerInterface;

class PollingService
{
    private EntityManagerInterface $em;


    public function __construct(EntityManagerInterface $manager)
    {
        $this->em=$manager;
    }


    public function getPollingQuestions(Polling $polling,Page $page)
    {
        $repo=$this->em->getRepository(Question::class);
        return $repo->findBy(
            [
                'polling'=>$polling,
                'page'=>$page,
                'deleted'=>false
            ]
        );
    }

    public function getPollingMaxPageNumber(Polling $polling)
    {
        $repo=$this->em->getRepository(Page::class);
        return $repo->getMaxPageNumber($polling)['number'];
    }


    public function updateQuestionsSort(Question $question)
    {
        $repo=$this->em->getRepository(Question::class);
        $questions=$repo->getQuestionsFromPageWithHiggerSort($question->getPolling(),$question->getPage(),$question->getSort());
        foreach($questions as $quest)
        {
            $quest->decreaseSort();
            $this->em->persist($quest);
        }
        $this->em->flush();
    }

    public function updatePagesNumber(Page $page)
    {
        $repo=$this->em->getRepository(Page::class);
        $pages=$repo->getPagesFromPollingWithHiggerNumber($page);
        foreach($pages as $tmp_page)
        {
            $tmp_page->decreaseNumber();
            $this->em->persist($tmp_page);
        }
        $this->em->flush();
    }
}