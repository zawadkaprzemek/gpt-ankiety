<?php

namespace App\Controller;

use App\Entity\Polling;
use App\Entity\Vote;
use App\Service\AnalizaService;
use App\Form\AnalizaSettingsType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * Class AnalizaController
 * @package App\Controller
 * @IsGranted("ROLE_USER")
 * @Route("/panel/ankieta/")
 */
class AnalizaController extends AbstractController
{

    private AnalizaService $service;

    public function __construct(AnalizaService $service)
    {
        $this->service = $service;
    }

    /**
     * @Route("{id}/analiza/surowe", name="app_panel_analiza_surowa")
     */
    public function index(Request $request, Polling $polling): Response
    {
        $data = $this->service->getDefaultDataForForm();
        $form = $this->createForm(AnalizaSettingsType::class, $data);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $data = $form->getData();
        }

        $results = $this->service->getPollingResults($polling, $data);

        return $this->render('analiza/index.html.twig', [
            'form' => $form->createView(),
            'results' => $results,
            'polling' => $polling,
        ]);
    }

    /**
     * @Route("{id}/analiza/zbiorcze", name="app_panel_analiza_zbiorcza")
     */
    public function summaryResults(Request $request, Polling $polling): Response
    {
        $data = $this->service->getDefaultDataForForm();
        $form = $this->createForm(AnalizaSettingsType::class, $data);
        $respondent = (int)$request->get('respondent');
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $data = $form->getData();
        }
        $respondent = $this->service->getRespondent($respondent);

        $results = $this->service->getPollingResultsPerQuestion($polling, $data, $respondent);

        return $this->render('analiza/summary.html.twig', [
            'form' => $form->createView(),
            'results' => $results,
            'polling' => $polling,
            'respondent' => $respondent,
        ]);
    }


    public function deleteVote(Request $request, Vote $vote)
    {

    }
}
