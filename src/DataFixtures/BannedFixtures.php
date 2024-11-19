<?php

namespace App\DataFixtures;

use DateTime;
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

        // create banned users
        foreach ($bannedUserIds as $userId) {
            $banned = new Banned();
            $banned->setBannedUserId($userId)
                ->setReason($reasons[array_rand($reasons)])
                ->setStatus('active')
                ->setTime(new DateTime())
                ->setBannedById(1);

            // persist banned user
            $manager->persist($banned);
        }

        // flush data to database
        $manager->flush();
    }
}
