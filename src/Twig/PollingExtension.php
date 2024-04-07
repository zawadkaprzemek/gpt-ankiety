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
        $this->em = $manager;
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
            new TwigFilter('print_answer', [$this, 'printAnswer']),
            new TwigFilter('answer_time', [$this, 'answerTime']),
            new TwigFilter('result_colorNPS', [$this, 'resultColorNPS']),
            new TwigFilter('resultPercent', [$this, 'resultPercent']),
            new TwigFilter('result_colorClosed', [$this, 'resultColorClosed']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('question_number', [$this, 'questionNumber']),
            new TwigFunction('polling_answers', [$this, 'pollingAnswers']),
            new TwigFunction('logic_info', [$this, 'logicInfo']),
            new TwigFunction('print_answer', [$this, 'printAnswer']),
            new TwigFunction('answer_time', [$this, 'answerTime']),
            new TwigFunction('result_colorNPS', [$this, 'resultColorNPS']),
            new TwigFunction('resultPercent', [$this, 'resultPercent']),
            new TwigFunction('result_colorClosed', [$this, 'resultColorClosed']),
        ];
    }

    public function questionNumber(Question $value): int
    {
        $repo = $this->em->getRepository(Question::class);
        $previousPages = (int)$repo->getQuestionsCountFromPreviousPages($value->getPolling(), $value->getPage());
        $currentPage = (int)$repo->getPreviousQuestionsCountFromCurrentPage($value->getPolling(), $value->getPage(), $value->getSort());
        return $previousPages + $currentPage + 1;
    }


    public function pollingAnswers(Polling $value): bool
    {
        $repo = $this->em->getRepository(Vote::class);
        $votes = $repo->getAnswersCountForPolling($value);
        return $votes > 0;

    }

    public function logicInfo(Logic $value): string
    {
        $text = "Jeżeli użytkownik ";
        $info = [
            'answer_question' => 'Odpowie na to pytanie',
            'dont_answer_question' => 'Nie odpowie na to pytanie',
            'choose_answer' => 'Wybierze odpowiedź',
            'dont_choose_answer' => 'Nie wybierze odpowiedzi',
            'answer_value_less' => 'Wybierze wartość <',
            'answer_value_equal' => 'Wybierze wartość =',
            'answer_value_greather' => 'Wybierze wartość >',
            'go_to_page' => 'przejdzie do strony',
            'skip_page' => 'Pominie stronę',
            'end_polling' => 'Zakończy ankiete'
        ];
        $text .= strtolower($info[$value->getIfAction()['begin_action']]);
        if ($value->getIfAction()['begin_action_value'] !== null) {
            if ($value->getQuestion()->getType()->getId() == 2) {
                $choose = $this->getAnswerContent($value->getIfAction()['begin_action_value']);
            } else {
                $choose = $value->getIfAction()['begin_action_value'];
            }
            $text .= " " . $choose;
        }

        $text .= ", to " . strtolower($info[$value->getThenAction()['end_action']]);

        if ($value->getThenAction()['end_action_value'] != null) {
            $text .= " " . $value->getThenAction()['end_action_value'];
        }

        return $text;
    }

    private function getAnswerContent($answerId): string
    {
        $repo = $this->em->getRepository(Answer::class);
        $answer = $repo->find($answerId);

        return ($answer != null ? $answer->getContent() : '');
    }

    public function printAnswer(array $answer, Question $question): string
    {
        $value = $answer[0];
        if ($question->getType()->getId() === 1 || $question->getType()->getId() === 3) {
            return $value;
        } else {
            $answer = $this->em->getRepository(Answer::class)->find($value);
            return $answer->getContent();
        }
    }

    public function answerTime(\DateTime $createAt, $votes): string
    {
        $lastVoteTime = null;
        /** @var Vote $vote */
        foreach ($votes as $vote) {
            if ($lastVoteTime === null || $vote->getUpdatedAt() > $lastVoteTime) {
                $lastVoteTime = $vote->getUpdatedAt();
            }
        }
        if ($lastVoteTime === null) {
            $lastVoteTime = $createAt;
        }

        $diff = $createAt->diff($lastVoteTime);
        $response = '';

        if ($diff->d > 0) {
            $response .= $diff->d . ' dni ';
        }

        if ($diff->h > 0) {
            $response .= $diff->h . ' godzin ';
        }
        return $response . $diff->i . ' minut ' . $diff->s . ' sekund';

    }

    public function resultColorNPS(int $value): string
    {
        $result = 'bg-';
        if ($value <= 6) {
            $result .= 'danger';
        } elseif ($value <= 8) {
            $result .= 'warning';
        } else {
            $result .= 'success';
        }

        return $result;
    }

    public function resultPercent(int $value, int $totalCount)
    {
        if ($totalCount === 0) {
            return 0;
        }
        return number_format(($value / $totalCount) * 100, 0);
    }

    public function resultColorClosed(float $value, array $votes)
    {
        $result = 'bg-';
        $resultTable = [];
        foreach ($votes as $vote)
        {
            $resultTable[] = $vote['percent'];
        }
        rsort($resultTable);
        $index =array_search($value,$resultTable);

        if($value == 0)
        {
            $result.='gray';
        }else{
            switch ($index){
                case 0:
                    $result.='success';
                    break;
                    case 1;
                    $result.='warning';
                    break;
                default:
                    $result.='danger';
                    break;
            }
        }

        return $result;
    }
}
