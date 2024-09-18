<?php

namespace App\DataFixtures;

use App\Entity\ServiceMonitoring;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

/**
 * Class ServiceMonitoringFixtures
 *
 * Service monitoring fixtures
 *
 * @package App\DataFixtures
 */
class ServiceMonitoringFixtures extends Fixture
{
    /**
     * Load data fixtures with the passed EntityManager
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

        foreach ($data as [$serviceName, $message, $status, $lastUpdateTime]) {
            $serviceMonitoring = new ServiceMonitoring();

            // set data
            $serviceMonitoring->setServiceName($serviceName)
                ->setMessage($message)
                ->setStatus($status)
                ->setLastUpdateTime(new \DateTime($lastUpdateTime));

            // persist data
            $manager->persist($serviceMonitoring);
        }

        // flush data
        $manager->flush();
    }
}
