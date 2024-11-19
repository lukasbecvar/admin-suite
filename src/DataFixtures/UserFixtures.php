<?php

namespace App\DataFixtures;

use DateTime;
use App\Entity\User;
use App\Util\SecurityUtil;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

/**
 * Class UserFixtures
 *
 * The testing user data fixtures
 *
 * @package App\DataFixtures
 */
class UserFixtures extends Fixture
{
    private SecurityUtil $securityUtil;

    public function __construct(SecurityUtil $securityUtil)
    {
        $this->securityUtil = $securityUtil;
    }

    /**
     * Load user fixtures
     *
     * @param ObjectManager $manager
     *
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        // create owner user
        $user = new User();

        // generate hash for password
        $hash = $this->securityUtil->generateHash('test');

        // set owner user data
        $user->setUsername('test')
            ->setPassword($hash)
            ->setRole('OWNER')
            ->setIpAddress('127.0.0.1')
            ->setUserAgent('DataFixtures-CLI')
            ->setRegisterTime(new DateTime())
            ->setLastLoginTime(new DateTime())
            ->setToken(md5(random_bytes(32)))
            ->setProfilePic('default_pic');

        // persist owner user
        $manager->persist($user);

        // testing roles
        $roles = ['USER', 'ADMIN', 'DEVELOPER', 'OWNER'];

        // create 100 random users
        for ($i = 1; $i <= 100; $i++) {
            // get current time
            $time = new DateTime();

            // create test user
            $user = new User();

            // set user data
            $user->setUsername('user' . $i)
                ->setPassword($hash)
                ->setRole($roles[array_rand($roles)])
                ->setIpAddress('127.0.0.1')
                ->setUserAgent('DataFixtures-CLI')
                ->setRegisterTime($time)
                ->setLastLoginTime($time)
                ->setToken(md5(random_bytes(32)))
                ->setProfilePic('default_pic');

            // persist user
            $manager->persist($user);
        }

        // flush data to the database
        $manager->flush();
    }
}
