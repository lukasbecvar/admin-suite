<?php

namespace App\DataFixtures;

use DateTime;
use App\Entity\MonitoringStatus;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

/**
 * Class MonitoringStatusFixtures
 *
 * Service monitoring fixtures
 *
 * @package App\DataFixtures
 */
class MonitoringStatusFixtures extends Fixture
{
    /**
     * Load monioring status fixtures
     *
     * @param ObjectManager $manager
     *
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        // testing service monitoring data
        $data = [
            ['system-cpu-usage', 'cpu usage is ok', 'ok', '2024-07-08 15:17:38'],
            ['system-ram-usage', 'ram usage is ok', 'ok', '2024-07-08 15:17:38'],
            ['system-storage-usage', 'storage usage is ok', 'ok', '2024-07-08 15:17:38'],
            ['sshd', 'SSHD is not running', 'not-running', '2024-07-08 15:18:57'],
            ['docker', 'Docker is running', 'running', '2024-07-08 15:18:57'],
            ['becvar-site', 'Becvar site is online', 'online', '2024-07-08 16:12:28'],
        ];

        // create monitoring status
        foreach ($data as [$serviceName, $message, $status, $lastUpdateTime]) {
            $MonitoringStatus = new MonitoringStatus();

            // set data
            $MonitoringStatus->setServiceName($serviceName)
                ->setMessage($message)
                ->setStatus($status)
                ->setDownTime(0)
                ->setSlaTimeframe('2025-01')
                ->setLastUpdateTime(new DateTime($lastUpdateTime));

            // persist data
            $manager->persist($MonitoringStatus);
        }

        // flush data to database
        $manager->flush();
    }
}
