<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Log;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

/**
 * Class LogFixtures
 *
 * The fixture for the log entity
 *
 * @package App\DataFixtures
 */
class LogFixtures extends Fixture
{
    /**
     * Load the log fixtures
     *
     * @param ObjectManager $manager The entity manager
     *
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // create 100 logs
        for ($i = 0; $i < 100; $i++) {
            $log = new Log();

            // set the log properties
            $log->setName($faker->word)
                ->setMessage($faker->sentence)
                ->setTime($faker->dateTimeThisYear)
                ->setUserAgent($faker->userAgent)
                ->setIpAdderss($faker->ipv4)
                ->setStatus('UNREADED');

            // save the log
            $manager->persist($log);
        }

        // save the logs
        $manager->flush();
    }
}
