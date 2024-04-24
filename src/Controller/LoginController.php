<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function index(AuthenticationUtils $logInInfo): Response
    {


        //ako smo lose upisali lozinku i vratili se opet na ovu stranicu
        $lastUserName = $logInInfo->getLastUsername();
        $error = $logInInfo->getLastAuthenticationError();

        return $this->render('login/index.html.twig', [
            'lastUsername' => $lastUserName,
            'errorMessage' => $error
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(){} 
}
