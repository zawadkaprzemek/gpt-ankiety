<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CodeController extends AbstractController
{
    /**
     * @Route("/code", name="app_code")
     */
    public function index(): Response
    {
        return $this->render('code/index.html.twig', [
            'controller_name' => 'CodeController',
        ]);
    }
}
