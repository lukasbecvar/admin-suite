<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Util\SecurityUtil;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

/**
 * Class UserFixtures
 *
 * Fixtures for the User entity
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
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     *
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        // create the owner user
        $user = new User();

        // generate a hash for the password
        $hash = $this->securityUtil->generateHash('test');

        // set owner user data
        $user->setUsername('test')
            ->setPassword($hash)
            ->setRoles(['ROLE_OWNER'])
            ->setIpAddress('127.0.0.1')
            ->setRegisterTime(new \DateTime())
            ->setLastLoginTime(new \DateTime())
            ->setToken(md5(random_bytes(32)))
            ->setProfilePic('default_pic');

        // persist the owner user
        $manager->persist($user);

        // testing roles
        $roles = ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_DEVELOPER', 'ROLE_OWNER'];

        // create 100 random users
        for ($i = 1; $i <= 10; $i++) {
            // get current time
            $time = new \DateTime();

            // create the test user
            $user = new User();

            // set user data
            $user->setUsername('user' . $i)
                ->setPassword($hash)
                ->setRoles([$roles[array_rand($roles)]])
                ->setIpAddress('127.0.0.1')
                ->setRegisterTime($time)
                ->setLastLoginTime($time)
                ->setToken(md5(random_bytes(32)))
                ->setProfilePic('default_pic');

            // persist the user
            $manager->persist($user);
        }

        // flush the data to the database
        $manager->flush();
    }
}
