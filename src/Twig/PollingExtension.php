<?php

namespace App\Twig;

use App\Entity\Answer;
use App\Entity\Logic;
use App\Entity\Vote;
use Twig\TwigFilter;
use Twig\TwigFunction;
use App\Entity\Polling;
use App\Entity\Question;
use Twig\Extension\AbstractExtension;
use Doctrine\ORM\EntityManagerInterface;

class PollingExtension extends AbstractExtension
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->em=$manager;
    }

    public function getFilters(): array
    {
        return [
            // If your filter generates SAFE HTML, you should add a third
            // parameter: ['is_safe' => ['html']]
            // Reference: https://twig.symfony.com/doc/3.x/advanced.html#automatic-escaping
            new TwigFilter('question_number', [$this, 'questionNumber']),
            new TwigFilter('polling_answers', [$this, 'pollingAnswers']),
            new TwigFilter('logic_info', [$this, 'logicInfo']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('question_number', [$this, 'questionNumber']),
            new TwigFunction('polling_answers', [$this, 'pollingAnswers']),
            new TwigFunction('logic_info', [$this, 'logicInfo']),
        ];
    }

    public function questionNumber(Question $value)
    {
        $repo=$this->em->getRepository(Question::class);
        $previousPages=(int)$repo->getQuestionsCountFromPreviousPages($value->getPolling(),$value->getPage());
        $currentPage=(int)$repo->getPreviousQuestionsCountFromCurrentPage($value->getPolling(),$value->getPage(),$value->getSort());
        return $previousPages+$currentPage +1;
    }


    public function pollingAnswers(Polling $value)
    {
        $repo=$this->em->getRepository(Vote::class);
        $votes=$repo->getAnswersCountForPolling($value);
        return $votes>0;
        
    }

    public function logicInfo(Logic $value)
    {
        $text="Jeżeli użytkownik ";
        $info=[
            'answer_question'=>'Odpowie na to pytanie',
            'dont_answer_question'=>'Nie odpowie na to pytanie',
            'choose_answer' =>'Wybierze odpowiedź' ,
            'dont_choose_answer' =>   'Nie wybierze odpowiedzi',
            'answer_value_less'=>'Wybierze wartość <',
            'answer_value_equal'=>        'Wybierze wartość =' ,
            'answer_value_greather'=>        'Wybierze wartość >' ,
            'go_to_page'=>'przejdzie do strony',
            'skip_page'=>        'Pominie stronę',
            'end_polling'=>        'Zakończy ankiete'
        ];
        $text.=strtolower($info[$value->getIfAction()['begin_action']]);
        if($value->getIfAction()['begin_action_value']!==null)
        {
            if($value->getQuestion()->getType()->getId()==2)
            {
                $choose=$this->getAnswerContent($value->getIfAction()['begin_action_value']);
            }else{
                $choose=$value->getIfAction()['begin_action_value'];
            }
            $text.=" ".$choose;
        }

        $text.=", to ".strtolower($info[$value->getThenAction()['end_action']]);

        if($value->getThenAction()['end_action_value']!=null)
        {
            $text.=" ".$value->getThenAction()['end_action_value'];
        }

        return $text;
    }

    private function getAnswerContent($answerId)
    {
        $repo=$this->em->getRepository(Answer::class);
        $answer=$repo->find($answerId);

        return ($answer!=null ? $answer->getContent() : '');
    }
}
