<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\ServiceVisitor;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

/**
 * Class ServiceVisitorFixtures
 *
 * Testing service visitors data fixtures for fill database with test data
 *
 * @package App\DataFixtures
 */
class ServiceVisitorFixtures extends Fixture
{
    /**
     * Load services visitors fixtures
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // testing services
        $services = [
            'becvar.xyz',
            'pied-piper.xyz',
            'paste.becvar.xyz',
            'passgen.becvar.xyz',
            'speed-test.becvar.xyz'
        ];

        foreach ($services as $service) {
            for ($i = 0; $i < 100; $i++) {
                // create visitor object
                $visitor = new ServiceVisitor();
                $visitor->setServiceName($service);
                $visitor->setIpAddress($faker->ipv6());
                $visitor->setLocation($faker->countryCode() . '/' . $faker->city());
                $visitor->setReferer($faker->url());
                $visitor->setUserAgent($faker->userAgent());
                $visitor->setLastVisitTime($faker->dateTimeBetween('-30 days', 'now'));

                // persist visitor object
                $manager->persist($visitor);
            }
        }

        // flush data to database
        $manager->flush();
    }
}
