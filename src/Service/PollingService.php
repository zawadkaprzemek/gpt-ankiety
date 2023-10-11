<?php

namespace App\Service;

use App\Entity\Code;
use App\Entity\Page;
use App\Entity\User;
use App\Entity\Vote;
use App\Entity\Logic;
use App\Entity\Answer;
use App\Entity\Polling;
use App\Entity\Question;
use App\Entity\SessionUser;
use App\Repository\PageRepository;
use App\Repository\QuestionRepository;
use App\Repository\SessionUserRepository;
use App\Repository\VoteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;


class PollingService
{
    private EntityManagerInterface $em;


    public function __construct(EntityManagerInterface $manager)
    {
        $this->em=$manager;
    }

    public function getPollingsToCodeGenerator(User $user)
    {
        $repo = $this->em->getRepository(Code::class);
        return $user->isAdmin() ? (new ArrayCollection($repo->findAll()))->toArray() : $user->getPollings()->toArray();
    }


    public function getPollingQuestions(Polling $polling,Page $page)
    {
        /** @var $repo QuestionRepository */
        $repo=$this->em->getRepository(Question::class);
        return $repo->getPollingQuestionsSorted(
            $polling,
            $page
        );
    }

    public function getPollingMaxPageNumber(Polling $polling)
    {
        /** @var $repo PageRepository */
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
        /** @var $repo QuestionRepository */
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
        /** @var $repo PageRepository */
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
        return array_map("unserialize", array_unique(array_map("serialize", $rules)));
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

    public function getPollingUsers(Polling $polling)
    {
        /** @var $repo SessionUserRepository */
        $repo=$this->em->getRepository(SessionUser::class);

        return $repo->getAllUsersForPolling($polling);
    }

    public function getAnswerContent(int $answerId):string
    {
        $repo=$this->em->getRepository(Answer::class);
        $answer=$repo->find($answerId);

        return ($answer!=null ? $answer->getContent() : 'brak odpowiedzi');
    }

    public function getLastVoteTimeForUser(SessionUser $user)
    {
        /** @var $repo VoteRepository */
        $repo=$this->em->getRepository(Vote::class);
        $lastVote=$repo->getLastVoteForUser($user);

        return ($lastVote!=null ? $lastVote->getUpdatedAt() : null);
    }

    public function duplicatePolling(Polling $polling)
    {
        $d_polling=clone $polling;

        $pages=[];
        foreach($d_polling->getPages() as $page)
        {
            $d_polling->removePage($page);
            $d_page=clone $page;
            $pages[$page->getNumber()]=$d_page;
            $d_polling->addPage($d_page);

        }
        foreach($d_polling->getQuestions() as $question)
        {
            $d_question= $this->duplicateQuestion($question);
            $d_question->setPolling($d_polling);
            $d_question->setPage($pages[$question->getPage()->getNumber()]);
            $d_question->setSort($question->getSort());
        }

        foreach($d_polling->getCodes() as $code)
        {
            $d_polling->removeCode($code);
        }
        $this->em->persist($d_polling);
        $this->em->flush();

        return $d_polling;
    }

    public function duplicateQuestion(Question $question)
    {
        $d_question=clone $question;
        $org_answers=$d_question->getAnswers();
        foreach($org_answers as $ans)
        {
            $d_answ=clone $ans;
            $d_question->removeAnswer($ans);
            $d_question->addAnswer($d_answ);
        }

        $questions=$this->getPollingQuestions($d_question->getPolling(),$d_question->getPage());
        $d_question->setSort(sizeof($questions)+1);
        $this->em->persist($d_question->getPolling());
        $this->em->persist($d_question);
        $this->em->flush();
        foreach($question->getLogics() as $logic)
        {
            $d_logic=clone $logic;
            $d_logic->setQuestion($d_question);
            if($d_question->getType()->getId()==2)
            {
                $ifAction=$d_logic->getIfAction();
                if($ifAction['begin_action_value']!=null)
                {
                    $orgAnswer=$this->getAnswer($ifAction['begin_action_value']);
                    $d_answer_id=null;
                    foreach($d_question->getAnswers() as $answer)
                    {
                        if($answer->getContent()===$orgAnswer->getContent())
                        {
                            $d_answer_id=$answer->getId();
                        }
                    }
                    $ifAction['begin_action_value']=$d_answer_id;
                    $d_logic->setIfAction($ifAction);
                }
                
            }
            $this->em->persist($d_logic);
            $this->em->flush();
        }

        return $d_question;
    }

    private function getAnswer(int $id):Answer
    {
        return $this->em->getRepository(Answer::class)->find($id);
    }
}