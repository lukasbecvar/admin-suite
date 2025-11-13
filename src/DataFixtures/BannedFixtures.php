<?php

namespace App\DataFixtures;

use DateTime;
use App\Entity\User;
use App\Entity\Banned;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

/**
 * Class BannedFixtures
 *
 * Testing banned data fixtures for fill database with test data
 *
 * @package App\DataFixtures
 */
class BannedFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * Load banned fixtures
     *
     * @param ObjectManager $manager The entity manager
     *
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        // testing banned users ids
        $bannedUserIds = [3, 5, 6];

        // random reasons for banned users
        $reasons = [
            'Violation of community guidelines',
            'Spamming other users',
            'Inappropriate behavior',
            'Suspicious activity',
            'Terms of service violation'
        ];

        // get issuer
        $issuer = $manager->getRepository(User::class)->find(1);
        if ($issuer === null) {
            return;
        }

        // create banned users
        foreach ($bannedUserIds as $userId) {
            $bannedUser = $manager->getRepository(User::class)->find($userId);
            if ($bannedUser === null) {
                continue;
            }

            $banned = new Banned();
            $banned->setBannedUser($bannedUser)
                ->setReason($reasons[array_rand($reasons)])
                ->setStatus('active')
                ->setTime(new DateTime())
                ->setBannedBy($issuer);

            // persist banned user
            $manager->persist($banned);
        }

        // flush data to database
        $manager->flush();
    }

    /**
     * Declare fixture dependencies (ensure that the fixture is loaded after user fixtures)
     *
     * @return array<Class-string> The array of dependencies
     */
    public function getDependencies(): array
    {
        return [
            UserFixtures::class
        ];
    }
}
