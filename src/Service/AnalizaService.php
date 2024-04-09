<?php

namespace App\Service;

use App\Entity\Polling;
use App\Entity\Question;
use App\Entity\SessionUser;
use App\Repository\SessionUserRepository;
use Doctrine\ORM\EntityManagerInterface;

class AnalizaService
{

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function getDefaultDataForForm(): array
    {
        return [
            'date_from' => (new \DateTime('first day of this month'))->setTime(0, 0),
            'date_to' => (new \DateTime('last day of this month'))->setTime(23, 59, 59),
            'all_data' => true
        ];
    }


    public function getPollingResults(Polling $polling, array $data): array
    {
        return [
            'users' => $this->getSessionUsers($polling, $data),
            'questions' => $polling->getQuestions(),
        ];
    }

    public function getPollingResultsPerQuestion(Polling $polling, array $data)
    {
        $tmpresults = $this->getPollingResults($polling, $data);

        $questionArray = [];
        $results = [];
        foreach ($polling->getQuestions() as $question) {
            if ($question->getType()->getId() !== 4) {
                $questionArray[$question->getId()] = $question;
                $results[$question->getId()] = [
                    'votes' => [],
                    'summary' => [],
                ];
            }

        }


        /** @var SessionUser $user */
        foreach ($tmpresults['users'] as $user) {
            foreach ($user->getVotes() as $vote) {
                $results[$vote->getQuestion()->getId()]['votes'][] = $vote;
            }
        }

        $totalCount = count($tmpresults['users']);

        foreach ($results as $qId => &$result) {
            if(array_key_exists($qId,$questionArray))
            {
                $result['summary'] = $this->generateVotesSumary($result['votes'], $questionArray[$qId], $totalCount);
                if ($questionArray[$qId]->getType()->getId() === 3) {
                    $result = $this->generateNPSSummary($result, $totalCount);
                }
            }

        }

        $results['totalCount'] = $totalCount;

        return $results;
    }

    private function generateVotesSumary(array $votes, Question $question, int $totalCount): array
    {
        $summary = [
            'results' => []
        ];
        $summary['voted'] = count($votes);
        $summary['skipped'] = $totalCount - $summary['voted'];
        if ($question->getType()->getId() !== 1) {
            $answers = [];
            if ($question->getType()->getId() === 2) {
                foreach ($question->getAnswers() as $answer) {
                    $answers[$answer->getId()] = 0;
                }
            } elseif ($question->getType()->getId() === 3) {
                for ($i = 1; $i <= 10; $i++) {
                    $answers[$i] = 0;
                }
            }


            foreach ($votes as $vote) {
                $answer = $vote->getAnswer()[0];
                if(array_key_exists($answer, $answers)){
                    $answers[$answer]++;
                }

            }


            foreach ($answers as $key => $answer) {
                $summary['results'][$key]['count'] = $answer;
                $summary['results'][$key]['percent'] = $summary['voted'] > 0 ? round(($answer / $summary['voted']) * 100, 2) : 0;
            }
        }

        return $summary;
    }

    private function getRepository(string $class)
    {
        return $this->em->getRepository($class);
    }


    private function getSessionUsers(Polling $polling, array $data)
    {
        /** @var SessionUserRepository $repo */
        $repo = $this->getRepository(SessionUser::class);
        if ($data['all_data']) {
            return $repo->getAllUsersForPolling($polling);
        }
        return $repo->getAllUsersForPollingByDate($polling, $data['date_from'], $data['date_to']);
    }

    private function generateNPSSummary(array $result, int $totalCount)
    {
        $promotors = 0;
        $destruktors = 0;
        foreach ($result['summary']['results'] as $i => $item) {
            if ($i > 8) {
                $promotors += $item['count'];
            } elseif ($i < 7) {
                $destruktors += $item['count'];
            }
        }

        $result['summary']['promotors'] = $promotors;
        $result['summary']['destruktors'] = $destruktors;
        $result['summary']['nps'] = $totalCount > 0 ? number_format(($promotors / $result['summary']['voted'] - $destruktors / $result['summary']['voted']) * 100) : 0;
        return $result;
    }
}