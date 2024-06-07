<?php

namespace App\Controller\Auth;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_auth_login')]
    public function index(): Response
    {
        return $this->render('auth/login.html.twig');
    }
}
