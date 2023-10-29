<?php

namespace App\Controller;

use App\Entity\Page;
use App\Entity\User;
use App\Entity\Logic;
use App\Entity\Polling;
use App\Form\LogicType;
use App\Entity\Question;
use App\Form\PageIntroType;
use App\Form\PollingType;
use App\Form\QuestionType;
use App\Service\PollingService;
use App\Service\ExcellGenerator;
use App\Repository\PageRepository;
use App\Repository\PollingRepository;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
        $this->pollingService = $pollingService;
    }

    /**
     * @Route("/lista", name="app_my_pollings")
     */
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user->isAdmin()) {
            $pollings = $this->pollingService->getAllPollings();
        } else {
            $pollings = $user->getPollings();
        }

        return $this->render('polling/list.html.twig', [
            'pollings' => $pollings,
        ]);
    }


    /**
     * @Route("/nowa", name="app_polling_new")
     * @Route("/{id}/edycja", name="app_polling_edit")
     */
    public function pollingForm(Request $request, ?Polling $polling = null)
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($polling == null) {
            $polling = new Polling();
            $polling->setUser($user);
            $polling->setHash(uniqid());
            $page = new Page();
            $page->setNumber(1);
            $polling->addPage($page);
        }

        if ($user !== $polling->getUser() && !$user->isAdmin()) {
            return $this->redirectToRoute('app_my_pollings');
        }

        $form = $this->createForm(PollingType::class, $polling);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($polling);
            $em->flush();
            $this->addFlash('success', 'Zapisano ankiete');
            return $this->redirectToRoute('app_my_pollings');
        }


        return $this->render('polling/_form.html.twig', [
            'form' => $form->createView(),
            'new' => $polling->getId() == null,
            'polling' => $polling
        ]);
    }

    /**
     * @Route("/{id}/{page}", name="app_polling_panel", requirements={"page"="\d+"}, defaults={"page":1})
     */
    public function pollingPanel(Polling $polling, int $page = 1)
    {
        $user = $this->getUser();
        if ($user !== $polling->getUser() && !$user->isAdmin()) {
            return $this->redirectToRoute('app_home');
        }
        $em = $this->getDoctrine()->getManager();
        $page = $em->getRepository(Page::class)->findOneBy(['polling' => $polling, 'number' => $page]);
        if ($page == null) {
            return $this->redirectToRoute('app_polling_panel', ['id' => $polling->getId()]);
        }

        $questions = $this->pollingService->getPollingQuestions($polling, $page);


        return $this->render('polling/panel.html.twig', [
            'polling' => $polling,
            'current_page' => $page,
            'questions' => $questions
        ]);
    }

    /**
     * @Route("/{id}/dodaj_strone", name="app_polling_add_page")
     */
    public function addNewPollingPage(Polling $polling): RedirectResponse
    {
        $user = $this->getUser();
        if ($user !== $polling->getUser() && !$user->isAdmin()) {
            return $this->redirectToRoute('app_home');
        }
        $page = new Page();
        $page->setPolling($polling);
        $number = $this->pollingService->getPollingMaxPageNumber($polling);
        $page->setNumber((int)$number + 1);
        $em = $this->getDoctrine()->getManager();
        $em->persist($page);
        $em->flush();
        return $this->redirectToRoute('app_polling_panel', [
            'id' => $polling->getId(),
            'page' => $page->getNumber()
        ]);
    }

    /**
     * @Route("/{id}/{page}/pytanie/nowe", name="app_polling_add_question", requirements={"page"="\d+"}, defaults={"page":1})
     */
    public function addQuestion(Request $request, Polling $polling, int $page = 1)
    {
        $user = $this->getUser();
        if ($user !== $polling->getUser() && !$user->isAdmin()) {
            return $this->redirectToRoute('app_home');
        }
        $em = $this->getDoctrine()->getManager();
        $page = $em->getRepository(Page::class)->findOneBy(['polling' => $polling, 'number' => $page]);
        if ($page == null) {
            return $this->redirectToRoute('app_polling_panel', ['id' => $polling->getId()]);
        }
        $questions = $this->pollingService->getPollingQuestions($polling, $page);
        $question = new Question();
        $question->setPolling($polling)->setPage($page)->setSort(sizeof($questions) + 1);
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($question);
            $em->flush();
            $this->addFlash('success', 'Dodano pytanie');
            return $this->redirectToRoute('app_polling_panel', [
                'id' => $polling->getId(),
                'page' => $page->getNumber()
            ]);
        }


        return $this->render('polling/_question_form.html.twig', [
            'form' => $form->createView(),
            'polling' => $polling,
            'page' => $page
        ]);
    }


    /**
     * @Route("/{id}/{page}/delete", name="app_polling_delete_page", requirements={"page"="\d+"}, defaults={"page":1}, methods={"POST"})
     */
    public function deletePage(Polling $polling, PageRepository $pageRepository, Request $request, int $page = 1): RedirectResponse
    {
        $user = $this->getUser();
        if ($user !== $polling->getUser() && !$user->isAdmin()) {
            return $this->redirectToRoute('app_home');
        }
        $em = $this->getDoctrine()->getManager();
        $page = $em->getRepository(Page::class)->findOneBy(['polling' => $polling, 'number' => $page]);
        if ($page == null) {
            return $this->redirectToRoute('app_polling_panel', ['id' => $polling->getId()]);
        }
        if ($this->isCsrfTokenValid('delete' . $page->getId(), $request->request->get('_token'))) {
            $this->pollingService->hardDeleteQuestionsFromPage($page);
            $pageRepository->remove($page, true);
            $this->pollingService->updatePagesNumber($page);
            $this->addFlash('success', 'Usunięto stronę');
        }

        return $this->redirectToRoute('app_polling_panel', ['id' => $polling->getId()], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{id}/{page}/pytanie/{q_id}/edycja", name="app_polling_edit_question", requirements={"page"="\d+"}, defaults={"page":1})
     * @ParamConverter("question", options={"mapping": {"q_id": "id"}})
     */
    public function editQuestion(Polling $polling, Question $question, Request $request, int $page = 1)
    {
        $user = $this->getUser();
        if ($user !== $polling->getUser() && !$user->isAdmin()) {
            return $this->redirectToRoute('app_home');
        }
        $em = $this->getDoctrine()->getManager();
        $page = $em->getRepository(Page::class)->findOneBy(['polling' => $polling, 'number' => $page]);
        if ($page == null) {
            return $this->redirectToRoute('app_polling_panel', ['id' => $polling->getId()]);
        }
        if ($question->getPage() !== $page || $question->getPolling() !== $polling) {
            return $this->redirectToRoute('app_polling_panel', ['id' => $polling->getId()]);
        }
        $orgType = $question->getType()->getId();
        $orgAnswers = [];
        foreach ($question->getAnswers() as $answer) {
            $orgAnswers[] = $answer;
        }
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            if ($orgType != $question->getType()->getId()) {
                if ($orgType == 2) {
                    foreach ($orgAnswers as $answer) {
                        $em->remove($answer);
                    }
                }
            }


            $em->persist($question);
            $em->flush();
            $this->addFlash('success', 'Zapisano zmiany');
            return $this->redirectToRoute('app_polling_panel', [
                'id' => $polling->getId(),
                'page' => $page->getNumber()
            ]);
        }


        return $this->render('polling/_question_form.html.twig', [
            'form' => $form->createView(),
            'polling' => $polling,
            'question' => $question,
            'page' => $page

        ]);
    }

    /**
     * @Route("/{id}/{page}/edycja", name="app_polling_edit_page", requirements={"page"="\d+"}, defaults={"page":1})
     */
    public function editPage(Request $request, Polling $polling, int $page = 1)
    {
        $user = $this->getUser();
        if ($user !== $polling->getUser() && !$user->isAdmin()) {
            return $this->redirectToRoute('app_home');
        }
        $em = $this->getDoctrine()->getManager();
        $page = $em->getRepository(Page::class)->findOneBy(['polling' => $polling, 'number' => $page]);
        if ($page == null) {
            return $this->redirectToRoute('app_polling_panel', ['id' => $polling->getId()]);
        }

        $form = $this->createForm(PageIntroType::class, $page);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($page);
            $em->flush();
            $this->addFlash('success', 'Zapisano zmiany');
            return $this->redirectToRoute('app_polling_panel', [
                'id' => $polling->getId(),
                'page' => $page->getNumber()
            ]);
        }


        return $this->render('polling/_page_form.html.twig', [
            'form' => $form->createView(),

        ]);
    }

    /**
     * @Route("/{id}/open", name="app_polling_open", methods={"POST"})
     */
    public function openPolling(Polling $polling): Response
    {
        $user = $this->getUser();
        if ($polling->getUser() !== $user && !$user->isAdmin()) {
            return new JsonResponse(['status' => 'error']);
        }

        $polling->setOpen(!$polling->isOpen());
        $em = $this->getDoctrine()->getManager();
        $em->persist($polling);
        $em->flush();

        return new JsonResponse(['status' => 'success', 'open' => $polling->isOpen()]);
    }


    /**
     * @Route("/{id}/delete", name="app_polling_delete", methods={"POST"})
     */
    public function deletePolling(Request $request, Polling $polling, PollingRepository $pollingRepository): Response
    {
        $user = $this->getUser();
        if ($user !== $polling->getUser() && !$user->isAdmin()) {
            return $this->redirectToRoute('app_home');
        }

        if ($this->isCsrfTokenValid('delete' . $polling->getId(), $request->request->get('_token'))) {
            $pollingRepository->remove($polling, true);
            $this->addFlash('success', 'Usunięto ankietę');
        }

        return $this->redirectToRoute('app_my_pollings', [], Response::HTTP_SEE_OTHER);
    }


    /**
     * @Route("/{id}/{page}/pytanie/{q_id}/delete", name="app_polling_delete_question", requirements={"page"="\d+"}, defaults={"page":1}, methods={"POST"})
     * @ParamConverter("question", options={"mapping": {"q_id": "id"}})
     */
    public function deleteQuestion(Request $request, Polling $polling, Question $question, int $page = 1): Response
    {
        $user = $this->getUser();
        if ($user !== $polling->getUser() && !$user->isAdmin()) {
            return $this->redirectToRoute('app_home');
        }

        if ($this->isCsrfTokenValid('delete' . $question->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $question->setDeleted(true);
            $em->persist($question);
            $em->flush();
            $this->pollingService->updateQuestionsSort($question);
            $this->addFlash('success', 'Usunięto pytanie');
        }

        return $this->redirectToRoute('app_polling_panel', ['id' => $polling->getId(), 'page' => $page], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{id}/wyniki/pobierz", name="app_polling_results_excell")
     * @param Polling $polling
     * @param ExcellGenerator $generator
     */
    public function pollingResults(Polling $polling, ExcellGenerator $generator)
    {
        $user = $this->getUser();
        if ($user !== $polling->getUser() && !$user->isAdmin()) {
            return $this->redirectToRoute('app_home');
        }

        $filename = str_replace(' ', '_', strtolower($polling->getName())) . '_wyniki.xlsx';
        $streamedResponse = new StreamedResponse();
        $streamedResponse->setCallback(function () use ($generator, $polling) {
            $excel = $generator->createExcel($polling);
            $writer = new Xlsx($excel);
            $writer->save('php://output');
        });
        $streamedResponse->setStatusCode(Response::HTTP_OK);
        $streamedResponse->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $streamedResponse->send();
    }

    /**
     * @Route("/{id}/analiza/pobierz", name="app_polling_analysis_excell")
     * @param Polling $polling
     * @param ExcellGenerator $generator
     */
    public function pollingAnalysis(Polling $polling, ExcellGenerator $generator)
    {
        $user = $this->getUser();
        if ($user !== $polling->getUser() && !$user->isAdmin()) {
            return $this->redirectToRoute('app_home');
        }

        $filename = str_replace(' ', '_', strtolower($polling->getName())) . '_analiza.xlsx';
        $streamedResponse = new StreamedResponse();
        $streamedResponse->setCallback(function () use ($generator, $polling) {
            $excel = $generator->createExcel($polling, true);
            $writer = new Xlsx($excel);
            $writer->save('php://output');
        });
        $streamedResponse->setStatusCode(Response::HTTP_OK);
        $streamedResponse->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $streamedResponse->send();
    }


    /**
     * @Route("/{id}/{q_id}/ustaw_pozycje", name="app_polling_question_update_position", methods={"POST"})
     * @ParamConverter("question", options={"mapping": {"q_id": "id"}})
     * @param Polling $polling
     * @param Question $question
     * @param Request $request
     */
    public function updatePosition(Polling $polling, Question $question, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (($polling->getUser() !== $user && !$user->isAdmin()) || $question->getPolling() != $polling) {
            return new JsonResponse(['status' => 'error']);
        }
        $content = json_decode($request->getContent(), true);
        $em = $this->getDoctrine()->getManager();

        $question->setSort($content['position']);
        $em->persist($question);
        $em->flush();
        return new JsonResponse(['status' => 'success', 'position' => $question->getSort()]);
    }


    /**
     * @Route("/{id}/logika", name="app_polling_logic_list")
     */
    public function logicList(Polling $polling)
    {
        $user = $this->getUser();
        if ($polling->getUser() !== $user && !$user->isAdmin()) {
            return $this->redirectToRoute('app_my_pollings');
        }

        return $this->render('polling/logic_list.html.twig', [
            'polling' => $polling
        ]);

    }


    /**
     * @Route("/{id}/logika/{q_id}/dodaj", name="app_polling_logic_add")
     * @Route("/{id}/logika/{q_id}/{l_id}/edycja", name="app_polling_logic_edit")
     * @ParamConverter("question", options={"mapping": {"q_id": "id"}})
     * @ParamConverter("logic", options={"mapping": {"l_id": "id"}})
     */
    public function logicForm(Polling $polling, Question $question, Request $request, ?Logic $logic = null)
    {
        $user = $this->getUser();
        if (($polling->getUser() !== $user && !$user->isAdmin()) || $question->getPolling() != $polling) {
            return $this->redirectToRoute('app_my_pollings');
        }

        if ($logic == null) {
            $logic = new Logic();
            $logic->setQuestion($question);
        } else {
            if ($logic->getQuestion() != $question) {
                return $this->redirectToRoute('app_my_pollings');
            }
        }

        $form = $this->createForm(LogicType::class, $logic);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $this->prepareLogic($form->all(), $form);
            $logic->setIfAction($data['if'])
                ->setThenAction($data['then']);
            $em = $this->getDoctrine()->getManager();
            $em->persist($logic);
            $em->flush();
            $this->addFlash('success', 'Zapisano zmiany');
            return $this->redirectToRoute('app_polling_logic_list', ['id' => $question->getPolling()->getId()]);

        }

        return $this->render('polling/logic_form.html.twig', [
            'form' => $form->createView(),
            'question' => $question,
            'title' => ($logic->getId() == null ? "Dodaj regułę" : "Edytuj regułę")
        ]);
    }

    private function prepareLogic(array $data, FormInterface $form): array
    {
        $logic = [];
        $prefix = '';
        foreach ($data as $datum) {
            if (strpos($datum->getName(), 'begin') !== false) {
                $prefix = 'if';
            } elseif (strpos($datum->getName(), 'end') !== false) {
                $prefix = 'then';
            } else {
                $prefix = '';
            }
            if ($prefix != '') {
                $logic[$prefix][$datum->getName()] = $form->get($datum->getName())->getData();
            }

        }
        return $logic;
    }

    /**
     * @Route("/{id}/logika/{q_id}/{l_id}/usun", name="app_polling_logic_delete", methods={"POST"})
     * @ParamConverter("question", options={"mapping": {"q_id": "id"}})
     * @ParamConverter("logic", options={"mapping": {"l_id": "id"}})
     */
    public function deleteLogic(Polling $polling, Question $question, Logic $logic, Request $request)
    {
        $user = $this->getUser();
        if (($user !== $question->getPolling()->getUser() && !$user->isAdmin()) || $logic->getQuestion() != $question || $question->getPolling() != $polling) {
            return $this->redirectToRoute('app_home');
        }

        if ($this->isCsrfTokenValid('delete' . $logic->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($logic);
            $em->flush();
            $this->addFlash('success', 'Usunięto regułę logiki');
        }

        return $this->redirectToRoute('app_polling_logic_list', ['id' => $polling->getId()], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{id}/{page}/pytanie/{q_id}/duplikuj", name="app_question_duplicate", requirements={"page"="\d+"}, defaults={"page":1}, methods={"POST"})
     * @ParamConverter("question", options={"mapping": {"q_id": "id"}})
     */
    public function duplicateQuestion(Polling $polling, Question $question, Request $request, int $page = 1)
    {
        $user = $this->getUser();
        if (($user !== $question->getPolling()->getUser() && !$user->isAdmin()) || $question->getPolling() != $polling || $question->getPage()->getNumber() != $page) {
            return $this->redirectToRoute('app_home');
        }
        $em = $this->getDoctrine()->getManager();
        $page = $em->getRepository(Page::class)->findOneBy(['polling' => $polling, 'number' => $page]);
        if ($page == null) {
            return $this->redirectToRoute('app_polling_panel', ['id' => $polling->getId()]);
        }

        if ($this->isCsrfTokenValid('duplicate' . $question->getId(), $request->request->get('_token'))) {

            $d_question = $this->pollingService->duplicateQuestion($question);
            $em->persist($d_question);
            $em->flush();

            $this->addFlash('success', 'Powielono pytanie');
        }

        return $this->redirectToRoute('app_polling_panel', ['id' => $polling->getId(), 'page' => $page->getNumber()], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{id}/duplikuj", name="app_polling_duplicate", methods={"POST"})
     */
    public function duplicatePolling(Polling $polling, Request $request, int $page = 1)
    {
        $user = $this->getUser();
        if ($user !== $polling->getUser() && !$user->isAdmin()) {
            return $this->redirectToRoute('app_home');
        }
        $em = $this->getDoctrine()->getManager();
        if ($this->isCsrfTokenValid('duplicate' . $polling->getId(), $request->request->get('_token'))) {

            $d_polling = $this->pollingService->duplicatePolling($polling);
            $em->persist($d_polling);
            $em->flush();

            $this->addFlash('success', 'Powielono ankietę');
        }

        return $this->redirectToRoute('app_polling_panel', ['id' => $d_polling->getId(), 'page' => 1], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{id}/lista_kodow", name="app_polling_codes_list")
     * @param Polling $polling
     * @return Response
     */
    public function codesList(Polling $polling): Response
    {
        $codes = $polling->getCodes();
        return $this->render('code/index.html.twig', [
            'codes' => $codes,
            'polling' => $polling
        ]);
    }
}
