<?php

namespace App\Controller\Auth;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LogoutController extends AbstractController
{
    #[Route('/logout', name: 'app_auth_logout')]
    public function index(): Response
    {

        return $this->render('auth/login.html.twig');
    }
}
