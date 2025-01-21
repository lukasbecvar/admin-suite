<?php

namespace App\Tests\Repository;

use DateTime;
use App\Entity\MonitoringStatus;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\MonitoringStatusRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class MonitoringStatusRepositoryTest
 *
 * Test cases for doctrine monitoring status repository
 *
 * @package App\Tests\Repository
 */
class MonitoringStatusRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private MonitoringStatusRepository $monitoringStatusRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        // @phpstan-ignore-next-line
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->monitoringStatusRepository = $this->entityManager->getRepository(MonitoringStatus::class);

        // create testing data
        $monitoringStatus1 = new MonitoringStatus();
        $monitoringStatus1->setServiceName('Service A');
        $monitoringStatus1->setMessage('Service is running');
        $monitoringStatus1->setStatus('ok');
        $monitoringStatus1->setDownTime(0);
        $monitoringStatus1->setSlaTimeframe('2023-01');
        $monitoringStatus1->setLastUpdateTime(new DateTime());
        $monitoringStatus2 = new MonitoringStatus();
        $monitoringStatus2->setServiceName('Service B');
        $monitoringStatus2->setMessage('Service is down');
        $monitoringStatus2->setStatus('error');
        $monitoringStatus2->setDownTime(120);
        $monitoringStatus2->setSlaTimeframe('2023-02');
        $monitoringStatus2->setLastUpdateTime(new DateTime());
        $this->entityManager->persist($monitoringStatus1);
        $this->entityManager->persist($monitoringStatus2);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\MonitoringStatus')->execute();
        parent::tearDown();
    }

    /**
     * Test find by non current timeframe method with non matching timeframe
     *
     * @return void
     */
    public function testFindByNonCurrentTimeframeWithNonMatchingTimeframe(): void
    {
        // call tested method
        $result = $this->monitoringStatusRepository->findByNonCurrentTimeframe('2023-03');

        // assert result
        $this->assertIsArray($result);
        $this->assertInstanceOf(MonitoringStatus::class, $result[0]);
    }

    /**
     * Test find by non current timeframe method with matching timeframe
     *
     * @return void
     */
    public function testFindByNonCurrentTimeframeWithMatchingTimeframe(): void
    {
        // call tested method
        $result = $this->monitoringStatusRepository->findByNonCurrentTimeframe('2023-01');

        // assert result
        $this->assertIsArray($result);
        $this->assertInstanceOf(MonitoringStatus::class, $result[0]);
    }
}
