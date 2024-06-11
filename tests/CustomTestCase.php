<?php

namespace App\Tests;

use App\Entity\User;
use App\Manager\AuthManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class CustomTestCase
 *
 * Custom test case class
 *
 * @package App\Tests
 */
class CustomTestCase extends WebTestCase
{
    /**
     * Simulate a user login.
     *
     * @param KernelBrowser $client
     *
     * @return void
     */
    protected function simulateLogin(KernelBrowser $client): void
    {
        // create a mock user
        $mockUser = new User();
        $mockUser->setUsername('test');
        $mockUser->setPassword('$argon2id$v=19$m=16384,t=6,p=4$Q0ZSLlBtVmZMR0JxdThGUg$MRBG4L4FyD853oBxOYs3+W3S9MNecP9kACc0zZuZR5k');
        $mockUser->setRole('OWNER');
        $mockUser->setIpAddress('172.19.0.1');
        $mockUser->setUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36');
        $mockUser->setRegisterTime(new \DateTime());
        $mockUser->setLastLoginTime(new \DateTime());
        $mockUser->setToken('fba6eb31278954ce68feb303cbd34bfe');
        $mockUser->setProfilePic('default_pic');

        // create a mock of AuthManager
        $authManager = $this->createMock(AuthManager::class);

        // configure the mock to return true for isUserLogedin
        $authManager->method('isUserLogedin')->willReturn(true);

        // configure the mock to return the mock user for getLoggedUserRepository
        $authManager->method('getLoggedUserRepository')->willReturn($mockUser);

        // replace the actual AuthManager service with the mock
        $client->getContainer()->set('App\Manager\AuthManager', $authManager);
    }
}
