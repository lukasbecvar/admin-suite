<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Log;
use App\Entity\User;
use App\Manager\LogManager;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

/**
 * Class LogFixtures
 *
 * Testing log data fixtures for fill database with test data
 *
 * @package App\DataFixtures
 */
class LogFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * Load log fixtures
     *
     * @param ObjectManager $manager The entity manager
     *
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // get user
        $user = $manager->getRepository(User::class)->findOneBy([]);
        if ($user === null) {
            return;
        }

        // create 100 logs
        for ($i = 0; $i < 100; $i++) {
            $log = new Log();

            // set log properties
            $log->setName($faker->word)
                ->setMessage($faker->sentence)
                ->setTime($faker->dateTimeThisYear)
                ->setUserAgent($faker->userAgent)
                ->setIpAddress($faker->ipv4)
                ->setStatus('UNREADED')
                ->setLevel(LogManager::LEVEL_CRITICAL)
                ->setUser($user);

            // persist log
            $manager->persist($log);
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
