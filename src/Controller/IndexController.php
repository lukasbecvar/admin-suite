<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class IndexController
 *
 * The controller for the index page
 *
 * @package App\Controller
 */
class IndexController extends AbstractController
{
    /**
     * Show the app index
     *
     * @return Response The index view
     */
    #[Route('/', name: 'app_index')]
    public function index(): Response
    {
        return $this->render('index.html.twig');
    }
}
