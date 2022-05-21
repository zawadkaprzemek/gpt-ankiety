<?php

namespace App\Twig;

use Twig\TwigFilter;
use Twig\TwigFunction;
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
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('question_number', [$this, 'questionNumber']),
        ];
    }

    public function questionNumber(Question $value)
    {
        $repo=$this->em->getRepository(Question::class);
        $previousPages=(int)$repo->getQuestionsCountFromPreviousPages($value->getPolling(),$value->getPage());
        $currentPage=(int)$repo->getPreviousQuestionsCountFromCurrentPage($value->getPolling(),$value->getPage(),$value->getSort());
        return $previousPages+$currentPage +1;
    }
}
