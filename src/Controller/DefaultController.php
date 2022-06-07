<?php

namespace App\Controller;

use App\Entity\Code;
use App\Entity\SessionUser;
use App\Service\CookieReader;
use App\Form\EnterPollingType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;

class DefaultController extends AbstractController
{

    private $reader;
    public function __construct(CookieReader $reader)
    {
        $this->reader=$reader;
    }

    /**
     * @Route("/", name="app_home")
     */
    public function index(Request $request): Response
    {
        $form=$this->createForm(EnterPollingType::class,[]);
        $form->handleRequest($request);

        if($form->isSubmitted()&&$form->isValid())
        {
            $em=$this->getDoctrine()->getManager();
            $data=$form->getData();
            $code=$em->getRepository(Code::class)->findOneBy(['content'=>$data['code']]);
            dump($data,$code);
            if($code!==null)
            {
                if($code->getPolling()->isOpen())
                {
                    $cookieName=$code->getContent();
                    $cookie= $this->reader->getCookie($cookieName);
                    $cookieCode=$this->reader->getCookie('code');
                    if(!$code->isMulti()&&sizeof($code->getSessionUsers())>0)
                    {
                        $user=$code->getSessionUsers()[0];
                        if($cookieCode!==$code->getContent()&&$cookie==null)
                        {
                            $form->get('code')->addError(new FormError('Kod już został wykorzystany'));
                            return $this->render('default/index.html.twig', [
                                'form' => $form->createView(),
                            ]);
                        }
                        return $this->redirectToRoute('app_vote_thankyou_page',['hash'=>$code->getPolling()->getHash()]);
                    }

                    if($cookie==null)
                    {
                        $user= new SessionUser($code,$request->getClientIp());
                        $em->persist($user);
                        $em->flush();
                    }else{
                        $data=json_decode($cookie,true);
                        $user=$em->getRepository(SessionUser::class)->find($data["user"]);
                        if($user->getCode()!==$code)
                        {
                            $user=$em->getRepository(SessionUser::class)->findOneBy(['code'=>$code,'ipAddress'=>$request->getClientIp()]);
                            $user= new SessionUser($code,$request->getClientIp());
                            $em->persist($user);
                            $em->flush();
                        }
                    }

                    $data=["user"=>$user->getId()];

                    $cookie=$this->reader->setCookie($cookieName,json_encode($data));
                    $cookieCode=$this->reader->setCookie('code',$code->getContent());
                    $response=$this->redirectToRoute('app_vote_polling',['hash'=>$code->getPolling()->getHash(),'page'=>1]);
                    $response->headers->setCookie($cookie);
                    $response->headers->setCookie($cookieCode);
                    return $response;
                }else{
                    $form->get('code')->addError(new FormError('Błędny kod'));
                }
            }else{
                $form->get('code')->addError(new FormError('Błędny kod'));
            }
        }

        return $this->render('default/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
