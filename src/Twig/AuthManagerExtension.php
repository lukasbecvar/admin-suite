<?php

namespace App\Twig;

use Twig\TwigFunction;
use App\Manager\AuthManager;
use Twig\Extension\AbstractExtension;

/**
 * Class AuthManagerExtension
 *
 * Extension for providing auth manager methods
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
     * Get twig functions from auth manager
     *
     * isAdmin = isLoggedInUserAdmin
     * getUserData = getLoggedUserRepository
     *
     * @return TwigFunction[] Array of TwigFunction objects
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('isAdmin', [$this->authManager, 'isLoggedInUserAdmin']),
            new TwigFunction('getUserData', [$this->authManager, 'getLoggedUserRepository'])
        ];
    }
}
