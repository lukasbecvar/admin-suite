<?php

namespace App\Tests;

use DateTime;
use Exception;
use App\Entity\User;
use App\Manager\AuthManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class CustomTestCase
 *
 * Custom test case extending WebTestCase to provide additional methods
 *
 * @package App\Tests
 */
class CustomTestCase extends WebTestCase
{
    /**
     * Simulate user login
     *
     * @param KernelBrowser $client The KernelBrowser instance
     *
     * @return void
     */
    public function simulateLogin(KernelBrowser $client): void
    {
        // create mock user
        $mockUser = new User();
        $mockUser->setUsername('test');
        $mockUser->setPassword('$argon2id$v=19$m=16384,t=6,p=4$Q0ZSLlBtVmZMR0JxdThGUg$MRBG4L4FyD853oBxOYs3+W3S9MNecP9kACc0zZuZR5k');
        $mockUser->setRole('OWNER');
        $mockUser->setIpAddress('172.19.0.1');
        $mockUser->setUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36');
        $mockUser->setRegisterTime(new DateTime());
        $mockUser->setLastLoginTime(new DateTime());
        $mockUser->setToken('fba6eb31278954ce68feb303cbd34bfe');
        $mockUser->setAllowApiAccess(true);
        $mockUser->setProfilePic('default_pic');

        // create mock AuthManager mock instance
        $authManager = $this->createMock(AuthManager::class);

        // mock isUserLogedin to return true
        $authManager->method('isUserLogedin')->willReturn(true);

        // mock isLoggedInUserAdmin to return true
        $authManager->method('isLoggedInUserAdmin')->willReturn(true);

        // mock getLoggedUserRepository to return test mock user
        $authManager->method('getLoggedUserRepository')->willReturn($mockUser);

        // set mock AuthManager instance to the container
        $client->getContainer()->set(AuthManager::class, $authManager);
    }
}
