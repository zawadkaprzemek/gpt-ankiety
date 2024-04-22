<?php

namespace App\Controller;

use App\Entity\Polling;
use App\Entity\SessionUser;
use App\Service\AnalizaService;
use App\Form\AnalizaSettingsType;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Knp\Snappy\Pdf;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;


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


    /**
     * @Route("{id}/analiza/zbiorcze/pdf", name="app_pdf_generalmeetingresultspdf")
     * @param Pdf $knpSnappyPdf
     * @param Polling $polling
     * @param Request $request
     * @return PdfResponse
     */
    public function generalMeetingResultsPdf(Pdf $knpSnappyPdf,Polling $polling,Request $request): PdfResponse
    {
        $data = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'all_data' => $request->get('all_data'),
        ];
        $respondent = (int)$request->get('respondent');

        $respondent = $this->service->getRespondent($respondent);

        $results = $this->service->getPollingResultsPerQuestion($polling, $data, $respondent);

        $html = $this->render('analiza/summary-pdf.html.twig', [
            'results' => $results,
            'polling' => $polling,
            'respondent' => $respondent,
            'base_dir' => $request->getScheme()."://".$request->server->get('HTTP_HOST')
        ]);
        $knpSnappyPdf->setOption("enable-local-file-access",true);

        $filename= $polling->getName() .($respondent instanceof SessionUser ? ' Respondent_'.$respondent->getId() : '');

        $slugger = new AsciiSlugger();
        $filename = $slugger->slug($filename);

        return new PdfResponse(
            $knpSnappyPdf->getOutputFromHtml($html->getContent()),
            $filename->toString().'.pdf'
        );
    }
}
