<?php

namespace App\Service;

use App\Entity\Page;
use App\Entity\Logic;
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
        return $repo->getPollingQuestionsSorted(
            $polling,
            $page
        );
    }

    public function getPollingMaxPageNumber(Polling $polling)
    {
        $repo=$this->em->getRepository(Page::class);
        return $repo->getMaxPageNumber($polling)['number'];
    }

    public function getAllPollings()
    {
        $repo=$this->em->getRepository(Polling::class);
        return $repo->findAll();
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

    public function checkLogic($votes,$questions)
    {
        $rules=[];
        foreach($questions as $question)
        {
            if(sizeof($question->getLogics())>0)
            {
                $answers=$votes[$question->getId()]['answers'] ?? null;
                foreach($question->getLogics() as $logic)
                {
                    $matched=$this->compareAnswerWithLogic($logic,$answers);
                    if($matched)
                    {
                        $rules[]=$logic->getThenAction();
                    }
                }
            }
        }
        return array_map("unserialize", array_unique(array_map("serialize", $rules)));;
    }


    private function compareAnswerWithLogic(Logic $logic,$answers)
    {
        $action=$logic->getIfAction()['begin_action'];
        $value=$logic->getIfAction()['begin_action_value']?? null;
        $matched=false;
        switch($action)
        {
            case 'answer_value_less':
                $matched=$answers[0]<$value;
                break;
            case 'answer_value_equal':
                $matched=$answers[0]==$value;
                break;    
            case 'answer_value_greather':
                $matched=$answers[0]>$value;
                break;
            case 'choose_answer':
                $matched=in_array($value,$answers);
                break;
            case 'dont_choose_answer':
                $matched=!in_array($value,$answers);
                break;
            case 'answer_question':
                $matched=$answers!=null;
                break;
            case 'dont_answer_question':
                $matched=($answers==null||$answers[0]=="");
                break;
            default:
                $matched=false;
            break;
                
        }
        return $matched;
    }
}