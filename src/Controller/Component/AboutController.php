<?php

namespace App\Controller\Component;

use App\Util\AppUtil;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class AboutController
 *
 * Controller for rendering about page
 *
 * @package App\Controller\Component
 */
class AboutController extends AbstractController
{
    private AppUtil $appUtil;

    public function __construct(AppUtil $appUtil)
    {
        $this->appUtil = $appUtil;
    }

    /**
     * Render About page
     *
     * @return Response About page view
     */
    #[Route('/about', methods: ['GET'], name: 'app_about')]
    public function about(): Response
    {
        // get about component data
        $adminContactEmail = $this->appUtil->getEnvValue('ADMIN_CONTACT');
        $projectGithubUrl = $this->appUtil->getEnvValue('PROJECT_GITHUB_URL');
        $authorWebsiteUrl = $this->appUtil->getEnvValue('AUTHOR_WEBSITE_URL');

        return $this->render('component/about/about.twig', [
            'adminContactEmail' => $adminContactEmail,
            'authorWebsiteUrl' => $authorWebsiteUrl,
            'projectGithubUrl' => $projectGithubUrl
        ]);
    }
}
