<?php

namespace App\Controller;

use App\Entity\Code;
use App\Entity\User;
use App\Form\CodeGeneratorType;
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
    /**
     * @Route("/", name="app_my_codes")
     */
    public function index(): Response
    {
        /** @var User $user */
        $user=$this->getUser();

        $codes=$user->getCodes();
        return $this->render('code/index.html.twig', [
            'codes'=>$codes
        ]);
    }

    /**
     * @Route("/generuj", name="app_codes_generate")
     */
    public function createCodes(Request $request)
    {
         /** @var User $user */
         $user=$this->getUser();
         $form=$this->createForm(CodeGeneratorType::class,[]);
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
}
