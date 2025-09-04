<?php

namespace App\DataFixtures;

use App\Entity\SLAHistory;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

/**
 * Class SLAHistoryFixtures
 *
 * Testing SLA history data fixtures for fill database with test data
 *
 * @package App\DataFixtures
 */
class SLAHistoryFixtures extends Fixture
{
    /**
     * Load test SLA history fixtures
     *
     * @param ObjectManager $manager The entity manager
     *
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        // testing services
        $services = [
            'becvar.xyz',
            'code-paste',
            'admin-suite',
            'nonlizard.xyz'
        ];

        $currentYear = (int) date('Y');

        // generate data for the past 12 months
        for ($i = 1; $i <= 12; $i++) {
            $year = $currentYear - 1;
            $month = $i;

            foreach ($services as $service) {
                $slaHistory = new SLAHistory();
                $slaHistory->setServiceName($service);
                $slaHistory->setSlaTimeframe(sprintf('%04d-%02d', $year, $month));
                $slaHistory->setSlaValue(mt_rand(8000, 10000) / 100);

                // persist sla history entity
                $manager->persist($slaHistory);
            }
        }

        // flush data to database
        $manager->flush();
    }
}
