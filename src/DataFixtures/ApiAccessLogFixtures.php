<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\User;
use App\Entity\ApiAccessLog;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

/**
 * Class ApiAccessLogFixtures
 *
 * Testing api access log data fixtures for fill database with test data
 *
 * @package App\DataFixtures
 */
class ApiAccessLogFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * Load api access log fixtures
     *
     * @param ObjectManager $manager The entity manager
     *
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // get 5 users
        $users = $manager->getRepository(User::class)->findBy([], null, 5);
        if (count($users) < 5) {
            return;
        }

        // testing data
        $methods = ['GET', 'POST', 'PUT'];
        $endpoints = [
            'api_terminal',
            'app_api_system_logs',
            'app_api_external_log',
            'api_system_resources'
        ];

        // create api access logs
        for ($i = 0; $i < 100; $i++) {
            $log = new ApiAccessLog();
            $log->setUrl(str_replace('{id}', (string) $faker->numberBetween(1, 100), $faker->randomElement($endpoints)))
                ->setMethod($faker->randomElement($methods))
                ->setTime($faker->dateTimeBetween('-30 days', 'now'))
                ->setUser($users[$i % count($users)]);

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
