<?php

namespace App\DataFixtures;

use App\Entity\Banned;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

/**
 * Class BannedFixtures
 *
 * The testing banned data fixtures
 *
 * @package App\DataFixtures
 */
class BannedFixtures extends Fixture
{
    /**
     * Load the banned fixtures
     *
     * @param ObjectManager $manager The entity manager
     *
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        // testing banned users
        $bannedUserIds = [3, 5, 6];

        // random reasons for banned users
        $reasons = [
            'Violation of community guidelines',
            'Spamming other users',
            'Inappropriate behavior',
            'Suspicious activity',
            'Terms of service violation'
        ];

        // create the banned users
        foreach ($bannedUserIds as $userId) {
            $banned = new Banned();
            $banned->setBannedUserId($userId)
                ->setReason($reasons[array_rand($reasons)])
                ->setStatus('active')
                ->setTime(new \DateTime())
                ->setBannedById(1);

            // persist the banned user
            $manager->persist($banned);
        }

        // flush data to database
        $manager->flush();
    }
}
