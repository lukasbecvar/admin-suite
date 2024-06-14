<?php

namespace App\DataFixtures;

use App\Entity\Banned;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

/**
 * Banned fixtures class
 *
 * The Banned fixtures class is used to populate the banned table in the database
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
            $banned->setBannedUserId($userId);
            $banned->setReason($reasons[array_rand($reasons)]);
            $banned->setStatus('active');
            $banned->setTime(new \DateTime());
            $banned->setBannedById(1);

            // persist the banned user
            $manager->persist($banned);
        }

        // flush data to database
        $manager->flush();
    }
}
