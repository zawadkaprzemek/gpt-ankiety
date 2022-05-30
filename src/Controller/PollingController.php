<?php

namespace App\Controller;

use App\Entity\Page;
use App\Entity\User;
use App\Entity\Polling;
use App\Entity\Question;
use App\Form\PollingType;
use App\Form\QuestionType;
use App\Service\PollingService;
use App\Service\ExcellGenerator;
use App\Repository\PageRepository;
use App\Repository\PollingRepository;
use App\Repository\QuestionRepository;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * Class PollingController
 * @package App\Controller
 * @IsGranted("ROLE_USER")
 * @Route("/panel/ankieta")
 */
class PollingController extends AbstractController
{

    private PollingService $pollingService;

    public function __construct(PollingService $pollingService)
    {
        $this->pollingService=$pollingService;
    }
    /**
     * @Route("/lista", name="app_my_pollings")
     */
    public function index(): Response
    {
        /** @var User $user */
        $user =$this->getUser();


        return $this->render('polling/list.html.twig', [
            'pollings' => $user->getPollings(),
        ]);
    }


    /**
     * @Route("/nowa", name="app_polling_new")
     * @Route("/{id}/edycja", name="app_polling_edit")
     */
    public function pollingForm(Request $request,?Polling $polling=null)
    {
        /** @var User $user */
        $user=$this->getUser();
        if($polling==null)
        {
            $polling =new Polling();
            $polling->setUser($user);
            $page=new Page();
            $page->setNumber(1);
            $polling->addPage($page);
        }

        if($user!==$polling->getUser())
        {
            return $this->redirectToRoute('app_my_pollings');
        }

        $form=$this->createForm(PollingType::class,$polling);
        $form->handleRequest($request);
        if($form->isSubmitted()&&$form->isValid())
        {
            $polling->setHash(uniqid());
            $em=$this->getDoctrine()->getManager();
            $em->persist($polling);
            $em->flush();
            $this->addFlash('success','Zapisano ankiete');
            return $this->redirectToRoute('app_my_pollings');
        }


        return $this->render('polling/_form.html.twig',[
            'form'=>$form->createView(),
            'new'=>$polling->getId()==null
        ]);
    }

    /**
     * @Route("/{id}/{page}", name="app_polling_panel", requirements={"page"="\d+"}, defaults={"page":1})
     */
    public function pollingPanel(Polling $polling,int $page=1)
    {
        $em=$this->getDoctrine()->getManager();
        $page=$em->getRepository(Page::class)->findOneBy(['polling'=>$polling,'number'=>$page]);
        if($page==null){
            return $this->redirectToRoute('app_polling_panel',['id'=>$polling->getId()]);
        }

        $questions=$this->pollingService->getPollingQuestions($polling,$page);


        return $this->render('polling/panel.html.twig',[
            'polling'=>$polling,
            'current_page'=>$page,
            'questions'=>$questions
        ]);
    }

    /**
     * @Route("/{id}/dodaj_strone", name="app_polling_add_page")
     */
    public function addNewPollingPage(Polling $polling)
    {
        $page=new Page();
        $page->setPolling($polling);
        $number=$this->pollingService->getPollingMaxPageNumber($polling);
        $page->setNumber((int)$number+1);
        $em=$this->getDoctrine()->getManager();
        $em->persist($page);
        $em->flush();
        return $this->redirectToRoute('app_polling_panel',[
            'id'=>$polling->getId(),
            'page'=>$page->getNumber()
        ]);
    }

    /**
     * @Route("/{id}/{page}/pytanie/nowe", name="app_polling_add_question", requirements={"page"="\d+"}, defaults={"page":1})
     */
    public function addQuestion(Polling $polling,int $page=1,Request $request)
    {
        $em=$this->getDoctrine()->getManager();
        $page=$em->getRepository(Page::class)->findOneBy(['polling'=>$polling,'number'=>$page]);
        if($page==null){
            return $this->redirectToRoute('app_polling_panel',['id'=>$polling->getId()]);
        }
        $questions=$this->pollingService->getPollingQuestions($polling,$page);
        $question=new Question();
        $question->setPolling($polling)->setPage($page)->setSort(sizeof($questions)+1);
        $form=$this->createForm(QuestionType::class,$question);
        $form->handleRequest($request);
        if($form->isSubmitted()&&$form->isValid())
        {
            $em=$this->getDoctrine()->getManager();
            $em->persist($question);
            $em->flush();
            $this->addFlash('success','Dodano pytanie');
            return $this->redirectToRoute('app_polling_panel',[
                'id'=>$polling->getId(),
                'page'=>$page->getNumber()
            ]);
        }


        return $this->render('polling/_question_form.html.twig',[
            'form'=>$form->createView(),

        ]);
    }


    /**
     * @Route("/{id}/{page}/delete", name="app_polling_delete_page", requirements={"page"="\d+"}, defaults={"page":1}, methods={"POST"})
     */
    public function deletePage(Polling $polling,int $page=1,PageRepository $pageRepository,Request $request)
    {
        $em=$this->getDoctrine()->getManager();
        $page=$em->getRepository(Page::class)->findOneBy(['polling'=>$polling,'number'=>$page]);
        if($page==null){
            return $this->redirectToRoute('app_polling_panel',['id'=>$polling->getId()]);
        }
        if ($this->isCsrfTokenValid('delete'.$page->getId(), $request->request->get('_token'))) {
            $pageRepository->remove($page, true);
            $this->pollingService->updatePagesNumber($page);
            $this->addFlash('success','Usunięto stronę');
        }

        return $this->redirectToRoute('app_polling_panel', ['id'=>$polling->getId()], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{id}/{page}/pytanie/{q_id}/edycja", name="app_polling_edit_question", requirements={"page"="\d+"}, defaults={"page":1})
     * @ParamConverter("question", options={"mapping": {"q_id": "id"}})
     */
    public function editQuestion(Polling $polling,int $page=1, Question $question,Request $request)
    {
        $em=$this->getDoctrine()->getManager();
        $page=$em->getRepository(Page::class)->findOneBy(['polling'=>$polling,'number'=>$page]);
        if($page==null){
            return $this->redirectToRoute('app_polling_panel',['id'=>$polling->getId()]);
        }
        if($question->getPage()!==$page||$question->getPolling()!==$polling)
        {
            return $this->redirectToRoute('app_polling_edit_question');
        }
        $form=$this->createForm(QuestionType::class,$question);
        $form->handleRequest($request);
        if($form->isSubmitted()&&$form->isValid())
        {
            $em=$this->getDoctrine()->getManager();
            $em->persist($question);
            $em->flush();
            $this->addFlash('success','Zapisano zmiany');
            return $this->redirectToRoute('app_polling_panel',[
                'id'=>$polling->getId(),
                'page'=>$page->getNumber()
            ]);
        }


        return $this->render('polling/_question_form.html.twig',[
            'form'=>$form->createView(),

        ]);
    }

    /**
     * @Route("/{id}/open", name="app_polling_open", methods={"POST"})
     */
    public function openPolling(Request $request, Polling $polling): Response
    {
        $user=$this->getUser();
        if($polling->getUser()!==$user)
        {
            return new JsonResponse(['status'=>'error']);
        }

        $polling->setOpen(!$polling->isOpen());
        $em=$this->getDoctrine()->getManager();
        $em->persist($polling);
        $em->flush();

        return new JsonResponse(['status'=>'success','open'=>$polling->isOpen()]);
    }


    /**
     * @Route("/{id}/delete", name="app_polling_delete", methods={"POST"})
     */
    public function deletePolling(Request $request, Polling $polling, PollingRepository $pollingRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$polling->getId(), $request->request->get('_token'))) {
            $pollingRepository->remove($polling, true);
            $this->addFlash('success','Usunięto ankietę');
        }

        return $this->redirectToRoute('app_my_pollings', [], Response::HTTP_SEE_OTHER);
    }


    /**
     * @Route("/{id}/{page}/pytanie/{q_id}/delete", name="app_polling_delete_question", requirements={"page"="\d+"}, defaults={"page":1}, methods={"POST"})
     * @ParamConverter("question", options={"mapping": {"q_id": "id"}})
     */
    public function deleteQuestion(Polling $polling,int $page=1,Request $request, Question $question): Response
    {
        if ($this->isCsrfTokenValid('delete'.$question->getId(), $request->request->get('_token'))) {
            $em=$this->getDoctrine()->getManager();
            $question->setDeleted(true);
            $em->persist($question);
            $em->flush();
            $this->pollingService->updateQuestionsSort($question);
            $this->addFlash('success','Usunięto pytanie');
        }

        return $this->redirectToRoute('app_polling_panel', ['id'=>$polling->getId(),'page'=>$page], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{id}/wyniki/pobierz", name="app_polling_results_excell")
     * @param Polling $polling
     * @param ExcellGenerator $generator
     */
    public function pollingResults(Polling $polling,ExcellGenerator $generator)
    {
        $user=$this->getUser();
        if($user!==$polling->getUser())
        {
            return $this->redirectToRoute('app_home');
        }

        $filename=str_replace(' ','_',strtolower($polling->getName())).'_wyniki.xlsx';
        $streamedResponse = new StreamedResponse();
        $streamedResponse->setCallback(function () use ($generator, $polling) {
            $excel=$generator->createExcel($polling);
            $writer =  new Xlsx($excel);
            $writer->save('php://output');
        });
        $streamedResponse->setStatusCode(Response::HTTP_OK);
        $streamedResponse->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $streamedResponse->send();
    }
}
