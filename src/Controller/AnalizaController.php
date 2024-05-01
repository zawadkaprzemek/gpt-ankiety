<?php

namespace App\Controller;

use App\Entity\Polling;
use App\Entity\SessionUser;
use App\Service\AnalizaService;
use App\Form\AnalizaSettingsType;
use App\Service\ExcellGenerator;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Knp\Snappy\Pdf;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
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

        return $this->render('analiza/summary-pdf.html.twig', [
            'form' => $form->createView(),
            'results' => $results,
            'polling' => $polling,
            'respondent' => $respondent,
            'base_dir' => $request->getScheme()."://".$request->server->get('HTTP_HOST')
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
        $knpSnappyPdf->setOptions([
            "enable-local-file-access" => true,
            'no-outline' => true,
        ]);

        $filename= $polling->getName() .($respondent instanceof SessionUser ? ' Respondent_'.$respondent->getId() : '');

        $slugger = new AsciiSlugger();
        $filename = $slugger->slug($filename);

        return new PdfResponse(
            $knpSnappyPdf->getOutputFromHtml($html->getContent()),
            $filename->toString().'.pdf'
        );
    }

    /**
     * @Route("{id}/analiza/zbiorcze/excell", name="app_polling_analysis_zbiorcza_excell")
     * @param Request $request
     * @param Polling $polling
     * @param ExcellGenerator $generator
     * @return StreamedResponse
     */
    public function analizaZbiorczaExcell(Request $request, Polling $polling, ExcellGenerator $generator): StreamedResponse
    {
        $data = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'all_data' => $request->get('all_data'),
        ];
        $respondent = (int)$request->get('respondent');

        $respondent = $this->service->getRespondent($respondent);

        $results = $this->service->getPollingResultsPerQuestion($polling, $data, $respondent);
        $excel = $generator->createAnalizaZbiorczaExcel($polling, $results);
        $filename = str_replace(' ', '_', strtolower($polling->getName())) . '_analiza_zbiorcza.xlsx';
        $streamedResponse = new StreamedResponse();
        $streamedResponse->setCallback(function () use ($generator, $polling, $results) {
            $excel = $generator->createAnalizaZbiorczaExcel($polling, $results);
            $writer = new Xlsx($excel);
            $writer->save('php://output');
        });
        $streamedResponse->setStatusCode(Response::HTTP_OK);
        $streamedResponse->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $streamedResponse->send();
    }
}
