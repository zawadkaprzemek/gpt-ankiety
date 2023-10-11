<?php

namespace App\Controller;

use App\Entity\Code;
use App\Entity\User;
use App\Form\CodeGeneratorType;
use App\Repository\CodeRepository;
use App\Service\PollingService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/panel/moje_kody")
 * @IsGranted("ROLE_USER")
 */
class CodeController extends AbstractController
{

    private PollingService $pollingService;

    public function __construct(PollingService $pollingService)
    {
        $this->pollingService = $pollingService;
    }
    /**
     * @Route("/", name="app_my_codes")
     */
    public function index(): Response
    {
        /** @var User $user */
        $user=$this->getUser();
        if($user->isAdmin())
        {
            $em=$this->getDoctrine()->getManager();
            $codes=$em->getRepository(Code::class)->findAll();
        }else{
            $codes=$user->getCodes();
        }

        return $this->render('code/index.html.twig', [
            'codes'=>$codes,
            'pollings'=>$this->generatePollingsArray($codes)
        ]);
    }

    /**
     * @Route("/generuj", name="app_codes_generate")
     */
    public function createCodes(Request $request)
    {
         /** @var User $user */
         $user=$this->getUser();
         $form=$this->createForm(CodeGeneratorType::class,(new Code()),['pollings'=>$this->pollingService->getPollingsToCodeGenerator($user)]);
         $form->handleRequest($request);
         if($form->isSubmitted()&&$form->isValid())
         {
            $data=$form->getData();
            $em=$this->getDoctrine()->getManager();
            for($i=0;$i<$data['count'];$i++)
            {
                /** @var Code $code */
                $code=new Code();
                $str=str_replace(" ",'',$data['prefix']).$this->generateRandomString(6);
                $code->setContent($str)
                    ->setMulti($data['multi'])
                    ->setPolling($data['polling'])
                    ->setUser($user);
                $em->persist($code);
            }
            $em->flush();
            $this->addFlash('success',"Wygenerowano {$data['count']} kodów");
            return $this->redirectToRoute('app_my_codes');
         }

         return $this->render('code/form.html.twig',[
            'form'=>$form->createView()
         ]);
    }

    private function generateRandomString($length = 10) {
        return substr(str_shuffle(str_repeat($x='123456789abcdefghjkmnpqrstuvwxyz', ceil($length/strlen($x)) )),1,$length);
    }

    /**
     * @Route("/{id}/delete", name="app_code_delete", methods={"POST"})
     */
    public function deleteCode(Request $request, Code $code, CodeRepository $codeRepository): Response
    {
        $user=$this->getUser();
        if($user!==$code->getUser()&&!$user->isAdmin())
        {
            return $this->redirectToRoute('app_home');
        }

        if(sizeof($code->getSessionUsers())>0)
        {
            $this->addFlash('warning','Nie można usunąć tego kodu');
        }else{
            if ($this->isCsrfTokenValid('delete'.$code->getId(), $request->request->get('_token'))) {
                $codeRepository->remove($code, true);
                $this->addFlash('success','Usunięto kod');
            }
        }
        return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
    }

    private function generatePollingsArray(array $codes): array
    {
        $pollings=[];
        foreach($codes as $code)
        {
            $pollings[$code->getPolling()->getId()]=$code->getPolling()->getName();
        }
        return $pollings;
    }
}
