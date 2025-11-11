<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\User;
use App\Entity\SentNotificationLog;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

/**
 * Class SentNotificationLogFixtures
 *
 * Testing sent notification log data fixtures for fill database with test data
 *
 * @package App\DataFixtures
 */
class SentNotificationLogFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * Load sent notification log fixtures
     *
     * @param ObjectManager $manager The entity manager
     *
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // get users
        $userRepository = $manager->getRepository(User::class);
        $users = $userRepository->findBy([], null, 20);
        if (count($users) === 0) {
            return;
        }

        // build receivers list
        $receiverIds = [];
        foreach ($users as $user) {
            if ($user->getId() !== null) {
                $receiverIds[] = $user->getId();
            }
        }

        // create 100 sent notification logs
        for ($i = 0; $i < 100; $i++) {
            $receiverId = count($receiverIds) > 0 ? $receiverIds[$i % count($receiverIds)] : null;
            if ($receiverId === null) {
                $fallbackUser = $users[$i % count($users)];
                $receiverId = $fallbackUser->getId();
            }

            // create sent notification log
            $log = new SentNotificationLog();
            $log->setTitle($faker->sentence(4))
                ->setMessage($faker->sentence(12))
                ->setSentTime($faker->dateTimeBetween('-30 days', 'now'))
                ->setReceiverId($receiverId ?? 1);

            // persist sent notification log
            $manager->persist($log);
        }

        // flush data to database
        $manager->flush();
    }

    /**
     * Declare fixture dependencies (ensure that the fixture is loaded after user fixtures)
     *
     * @return array<Class-string>
     */
    public function getDependencies(): array
    {
        return [
            UserFixtures::class
        ];
    }
}
