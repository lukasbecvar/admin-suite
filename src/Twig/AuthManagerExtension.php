<?php

namespace App\Twig;

use Twig\TwigFunction;
use App\Manager\AuthManager;
use Twig\Extension\AbstractExtension;

/**
 * Class AuthManagerExtension
 *
 * Twig extension for the auth manager
 *
 * @package App\Twig
 */
class AuthManagerExtension extends AbstractExtension
{
    private AuthManager $authManager;

    public function __construct(AuthManager $authManager)
    {
        $this->authManager = $authManager;
    }

    /**
     * Get the twig functions from auth manager
     *
     * isAdmin = isLoggedInUserAdmin
     * getUserData = getLoggedUserRepository
     *
     * @return TwigFunction[] An array of TwigFunction objects
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('isAdmin', [$this->authManager, 'isLoggedInUserAdmin']),
            new TwigFunction('getUserData', [$this->authManager, 'getLoggedUserRepository'])
        ];
    }
}
