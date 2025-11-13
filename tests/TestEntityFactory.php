<?php

namespace App\Tests;

use DateTime;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Utility factory for creating and persisting entities needed in tests
 *
 * @package App\Tests
 */
class TestEntityFactory
{
    /**
     * Create and persist user entity
     *
     * @param EntityManagerInterface $entityManager The entity manager used for persistence
     * @param array<string, mixed> $overrides Optional field overrides (e.g. ['username' => 'my-user'])
     *
     * @return User The persisted user
     */
    public static function createUser(EntityManagerInterface $entityManager, array $overrides = []): User
    {
        $user = new User();
        $user->setUsername($overrides['username'] ?? ('test-user-' . uniqid('_', true)));
        $user->setPassword($overrides['password'] ?? 'password');
        $user->setRole($overrides['role'] ?? 'ADMIN');
        $user->setIpAddress($overrides['ip_address'] ?? '127.0.0.1');
        $user->setUserAgent($overrides['user_agent'] ?? 'PHPUnit');
        $user->setRegisterTime($overrides['register_time'] ?? new DateTime());
        $user->setLastLoginTime($overrides['last_login_time'] ?? new DateTime());
        $user->setToken($overrides['token'] ?? uniqid('token_', true));
        $user->setAllowApiAccess($overrides['allow_api_access'] ?? true);
        $user->setProfilePic($overrides['profile_pic'] ?? 'pic');

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }
}
