<?php

namespace App\DataFixtures;

use DateTime;
use DateInterval;
use App\Entity\Metric;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

/**
 * Class MetricFixtures
 *
 * The testing metrics data fixtures
 *
 * @package App\DataFixtures
 */
class MetricFixtures extends Fixture
{
    /**
     * Load metrics fixtures
     *
     * @param ObjectManager $manager The entity manager
     *
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        $metrics = ['cpu_usage', 'ram_usage', 'storage_usage'];
        $serviceNames = ['host-system', 'becvar.xyz', 'nonlizard.xyz', 'paste.becvar.xyz'];
        $interval = new DateInterval('PT1H'); // metrics interval
        $startDate = new DateTime('-1 week'); // history limit
        $endDate = new DateTime(); // end with current time
        $currentDate = clone $startDate;

        // create the metrics history
        while ($currentDate <= $endDate) {
            foreach ($serviceNames as $serviceName) {
                foreach ($metrics as $name) {
                    $metric = new Metric();
                    $metric->setName($name)
                        ->setValue((string) random_int(10, 100))
                        ->setServiceName($serviceName)
                        ->setTime(clone $currentDate);

                    // persist metric
                    $manager->persist($metric);
                }
            }

            // increase time interval
            $currentDate->add($interval);
        }

        // flush data to database
        $manager->flush();
    }
}
