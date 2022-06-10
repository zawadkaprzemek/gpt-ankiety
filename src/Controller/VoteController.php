<?php

namespace App\Controller;

use App\Entity\Page;
use App\Entity\Polling;
use App\Entity\Question;
use App\Entity\SessionUser;
use App\Entity\Vote;
use App\Repository\VoteRepository;
use App\Service\CookieReader;
use App\Service\PollingService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/ankieta")
 */
class VoteController extends AbstractController
{
    private PollingService $pollingService;
    private CookieReader $reader;

    public function __construct(PollingService $pollingService,CookieReader $cookieReader)
    {
        $this->pollingService=$pollingService;
        $this->reader=$cookieReader;
    }

    /**
     * @Route("/{hash}/{page}", name="app_vote_polling", requirements={"page"="\d+"}, defaults={"page":1})
     */
    public function index(Polling $polling,int $page=1,Request $request): Response
    {
        $em=$this->getDoctrine()->getManager();
        $page=$em->getRepository(Page::class)->findOneBy(['polling'=>$polling,'number'=>$page]);
        if($page==null){
            return $this->redirectToRoute('app_vote_polling',['hash'=>$polling->getHash(),'page'=>1]);
        }
        $cookieCode=$this->reader->getCookie('code');
        if($cookieCode==null)
        {
            return $this->redirectToRoute('app_home');
        }
        $cookieName=$cookieCode;
        $cookie= $this->reader->getCookie($cookieName);
        if($cookie==null)
        {
            return $this->redirectToRoute('app_home');
        }
        $data=json_decode($cookie,true);
        $user=$em->getRepository(SessionUser::class)->find($data["user"]);
        if($user->getStatus()==1)
        {
            return $this->redirectToRoute('app_vote_thankyou_page',['hash'=>$polling->getHash()]);
        }
        $questions=$this->pollingService->getPollingQuestions($polling,$page);
        $votes=$em->getRepository(Vote::class)->findSessionUserAnswers($user,$questions);
        
        if($page->getNumber()>1)
        {
            for($i=1;$i<$page->getNumber();$i++)
            {
                $p_page=$em->getRepository(Page::class)->findOneBy(['polling'=>$polling,'number'=>$i]);
                $p_questions=$this->pollingService->getPollingQuestions($polling,$p_page);
                $p_votes=$em->getRepository(Vote::class)->findSessionUserAnswers($user,$p_questions);
                $p_votesArray=$this->prepareVotes($p_votes);
                $p_logic=$this->pollingService->checkLogic($p_votesArray,$p_questions);
                if(!empty($p_logic))
                {
                    if(!empty($p_logic))
                    {
                        /** Sprawdzam czy nie ma wymuszenia pominięcia strony */
                        foreach($p_logic as $item)
                        {
                            if($item['end_action']=='skip_page'&&$item['end_action_value']==$page->getNumber())
                            return $this->redirectToRoute('app_vote_polling',['hash'=>$polling->getHash(),'page'=>$page->getNumber()+1]);
                        }
                    }
                }
            }
        }

        $votesArray=$this->prepareVotes($votes);
        $errors=[];
        if($request->getMethod()=="POST")
        {
            $answers=$request->request->all();
            $errors=$this->checkErrors($answers,$questions);
            if(sizeof($errors)===0)
            {
                $logic=$this->pollingService->checkLogic($votesArray,$questions);
                if(!empty($logic))
                {
                    /** Najpierw sprawdzam czy nie ma wymuszenia zakończenia ankiety */
                    foreach($logic as $item)
                    {
                        if($item['end_action']=='end_polling')
                        return $this->redirectToRoute('app_vote_thankyou_page',['hash'=>$polling->getHash()]);
                    }
                    /*foreach($logic as $item)
                    {
                        if($item['end_action']=='skip_page'&&$item['end_action_value']==($page->getNumber()+1))
                        return $this->redirectToRoute('app_vote_polling',['hash'=>$polling->getHash(),'page'=>$page->getNumber()+2]);
                    }*/
                    foreach($logic as $item)
                    {
                        if($item['end_action']=='go_to_page')
                        return $this->redirectToRoute('app_vote_polling',['hash'=>$polling->getHash(),'page'=>$item['end_action_value']]);
                    }
                }
                $page=$em->getRepository(Page::class)->findOneBy(['polling'=>$polling,'number'=>$page->getNumber()+1]);
                if($page==null)
                {
                    return $this->redirectToRoute('app_vote_thankyou_page',['hash'=>$polling->getHash()]);
                }

                return $this->redirectToRoute('app_vote_polling',['hash'=>$polling->getHash(),'page'=>$page->getNumber()]);
            }
        }
        
        return $this->render('vote/index.html.twig',[
            'polling'=>$polling,
            'current_page'=>$page,
            'questions'=>$questions,
            'votes'=>$votesArray,
            'errors'=>$errors
        ]);
    }

    private function prepareVotes(array $votes=null)
    {
        $array=[];
        foreach($votes as $vote)
        {
            $array[$vote->getQuestion()->getId()]=[
                'answers'=>$vote->getAnswer()
            ];
        }

        return $array;
    }

    private function checkErrors(array $answers, array $questions)
    {
        $errors=[];
        $answers=$this->reorganizeAnswers($answers);
        foreach($questions as $question)
        {
            if($question->isRequired())
            {
                if(array_key_exists($question->getId(),$answers))
                {
                    $value=$answers[$question->getId()];
                    if($question->getType()->getId()==1)
                    {
                        if(($value==''||$value==null)||strlen(trim($value))< $this->getParameter('text_answer_min_lenght'))
                        {
                            $errors[]=$question->getId();
                        }
                    }else{
                        if($value==''||$value==null)
                        {
                            $errors[]=$question->getId();
                        }
                    }
                }else{
                    $errors[]=$question->getId();
                }
            }
        }
        return $errors;
    }

    private function reorganizeAnswers(array $answers)
    {
        $array=[];
        foreach($answers as $key=>$value)
        {
            $array[str_replace('question-','',$key)]=$value;
        }
        return $array;
    }

    /**
     * @Route("/{hash}/save-vote", name="app_vote_save",methods={"POST"})
     */
    public function saveVote(Polling $polling,Request $request,VoteRepository $repository)
    {
        $cookieCode=$this->reader->getCookie('code');
        if($cookieCode==null)
        {
            return $this->redirectToRoute('app_home');
        }
        $cookieName=$cookieCode;
        $content=json_decode($request->getContent(),true);
        $cookie= $this->reader->getCookie($cookieName);
        $em=$this->getDoctrine()->getManager();
        if($cookie==null)
        {
            return new JsonResponse(['status'=>'error']);
        }

        $data=json_decode($cookie,true);
        $user=$em->getRepository(SessionUser::class)->find($data["user"]);
        $question=$em->getRepository(Question::class)->find($content['question']);
        $vote=$repository->findOneBy(['sessionUser'=>$user,'question'=>$question]);
        if($vote==null)
        {
            $vote=new Vote();
            $vote->setSessionUser($user)->setQuestion($question);
        }

        $vote->setAnswer([trim($content['value'])]);
        $em->persist($vote);
        $em->flush();

        return new JsonResponse(['status'=>'success']);
    }

    /**
     * @Route("/{hash}/dziekujemy", name="app_vote_thankyou_page")
     */
    public function thankyouPage(Polling $polling)
    {
        $em=$this->getDoctrine()->getManager();
        $cookieCode=$this->reader->getCookie('code');
        if($cookieCode==null)
        {
            return $this->redirectToRoute('app_home');
        }
        $cookieName=$cookieCode;
        $cookie= $this->reader->getCookie($cookieName);
        if($cookie==null)
        {
            return $this->redirectToRoute('app_home');
        }
        $data=json_decode($cookie,true);
        $user=$em->getRepository(SessionUser::class)->find($data["user"]);
        $user->setStatus(1);
        $em->persist($user);
        $em->flush();

        return $this->render('vote/thankyouPage.html.twig',[
            'polling'=>$polling
        ]);
    }
}
