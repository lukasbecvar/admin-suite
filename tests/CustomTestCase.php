<?php

namespace App\Tests;

use DateTime;
use App\Entity\User;
use ReflectionProperty;
use App\Manager\AuthManager;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;

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

        // mock logged user id getter
        $authManager->method('getLoggedUserId')->willReturn(1);

        // mock token regeneration to succeed by default
        $authManager->method('regenerateSpecificUserToken')->willReturn(true);

        // mock getLoggedUserRepository to return test mock user
        $authManager->method('getLoggedUserRepository')->willReturn($mockUser);

        // set mock AuthManager instance to the container
        $client->getContainer()->set(AuthManager::class, $authManager);
    }

    /**
     * Create a test user entity
     *
     * @param int $id The user ID
     *
     * @return User The test user entity
     */
    public function createUserEntity(int $id): User
    {
        $user = new User();
        $user->setUsername('test-user-' . $id);
        $user->setPassword('password');
        $user->setRole('ADMIN');
        $user->setIpAddress('127.0.0.1');
        $user->setUserAgent('PHPUnit');
        $user->setRegisterTime(new DateTime());
        $user->setLastLoginTime(new DateTime());
        $user->setToken('token-' . $id . uniqid());
        $user->setAllowApiAccess(true);
        $user->setProfilePic('pic');

        $reflection = new ReflectionProperty(User::class, 'id');
        $reflection->setValue($user, $id);

        return $user;
    }

    /**
     * Generate CSRF token identical to the one rendered in views
     *
     * @param string $tokenId The token identifier
     *
     * @return string The token value
     */
    protected function getCsrfToken(KernelBrowser $client, string $tokenId = 'internal-csrf-token'): string
    {
        /** @var SessionFactoryInterface $sessionFactory */
        $sessionFactory = $client->getContainer()->get('session.factory');
        $session = $sessionFactory->createSession();
        $session->start();

        // share session with BrowserKit client
        $client->getCookieJar()->set(new Cookie($session->getName(), $session->getId()));

        /** @var RequestStack $requestStack */
        $requestStack = $client->getContainer()->get(RequestStack::class);
        $request = Request::create('/');
        $request->setSession($session);
        $requestStack->push($request);

        /** @var CsrfTokenManagerInterface $tokenManager */
        $tokenManager = $client->getContainer()->get(CsrfTokenManagerInterface::class);
        $token = $tokenManager->getToken($tokenId)->getValue();

        $session->save();
        $requestStack->pop();

        // return token string
        return $token;
    }
}
