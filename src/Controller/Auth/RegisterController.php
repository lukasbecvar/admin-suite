<?php

namespace App\Controller\Auth;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class RegisterController extends AbstractController
{
    #[Route('/register', name: 'app_auth_register')]
    public function index(): Response
    {
        return $this->render('auth/register.html.twig');
    }
}
