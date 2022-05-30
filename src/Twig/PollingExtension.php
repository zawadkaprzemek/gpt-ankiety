<?php

namespace App\Twig;

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
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('question_number', [$this, 'questionNumber']),
            new TwigFunction('polling_answers', [$this, 'pollingAnswers']),
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
}
