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
        $user->setUsername('test');
        $user->setEmail('lukas@becvar.xyz');

        // generate a hash for the password
        $user->setPassword($this->securityUtil->generateHash('test'));
        $user->setRoles(['ROLE_OWNER']);
        $user->setIpAddress('127.0.0.1');
        $user->setRegisterTime(new \DateTime());
        $user->setLastLoginTime(new \DateTime());
        $user->setToken(md5(random_bytes(30)));
        $user->setProfilePic('default_pic');

        // persist the owner user
        $manager->persist($user);

        // testing roles
        $roles = ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_DEVELOPER', 'ROLE_OWNER'];

        // create 100 random users
        for ($i = 1; $i <= 10; $i++) {
            $user = new User();

            // set user data
            $user->setUsername('user' . $i);
            $user->setEmail('user' . $i . '@becvar.xyz');

            // generate a hash for the password
            $hash = $this->securityUtil->generateHash('test');

            // set user password
            $user->setPassword($hash);

            // set random role
            $user->setRoles([$roles[array_rand($roles)]]);
            $user->setIpAddress('127.0.0.1');
            $user->setRegisterTime(new \DateTime());
            $user->setLastLoginTime(new \DateTime());
            $user->setToken(md5(random_bytes(30)));
            $user->setProfilePic('default_pic');

            // persist the user
            $manager->persist($user);
        }

        // flush the data to the database
        $manager->flush();
    }
}
