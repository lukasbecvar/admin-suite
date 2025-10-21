<?php

namespace App\Tests\Repository;

use App\Entity\SLAHistory;
use App\Repository\SLAHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class SLAHistoryRepositoryTest
 *
 * Test cases for doctrine SLA history repository
 *
 * @package App\Tests\Repository
 */
#[CoversClass(SLAHistoryRepository::class)]
class SLAHistoryRepositoryTest extends KernelTestCase
{
    private SLAHistoryRepository $slaHistoryRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        // @phpstan-ignore-next-line
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->slaHistoryRepository = $this->entityManager->getRepository(SLAHistory::class);

        // create testing data
        $dailySLA = new SLAHistory();
        $dailySLA->setServiceName('nginx');
        $dailySLA->setSlaTimeframe('daily');
        $dailySLA->setSlaValue(99.95);
        $weeklySLA = new SLAHistory();
        $weeklySLA->setServiceName('nginx');
        $weeklySLA->setSlaTimeframe('weekly');
        $weeklySLA->setSlaValue(99.85);
        $monthlySLA = new SLAHistory();
        $monthlySLA->setServiceName('mysql');
        $monthlySLA->setSlaTimeframe('monthly');
        $monthlySLA->setSlaValue(99.75);

        // save SLA history to database
        $this->entityManager->persist($dailySLA);
        $this->entityManager->persist($weeklySLA);
        $this->entityManager->persist($monthlySLA);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\SLAHistory')->execute();
        parent::tearDown();
    }

    /**
     * Test repository can find SLA history by service name
     *
     * @return void
     */
    public function testFindByServiceName(): void
    {
        // call tested method
        $nginxSLAs = $this->slaHistoryRepository->findBy(['service_name' => 'nginx']);
        $mysqlSLAs = $this->slaHistoryRepository->findBy(['service_name' => 'mysql']);

        // assert results
        $this->assertCount(2, $nginxSLAs);
        $this->assertCount(1, $mysqlSLAs);
        $this->assertSame('nginx', $nginxSLAs[0]->getServiceName());
        $this->assertSame('mysql', $mysqlSLAs[0]->getServiceName());
    }

    /**
     * Test repository can find SLA history by timeframe
     *
     * @return void
     */
    public function testFindByTimeframe(): void
    {
        // call tested method
        $dailySLAs = $this->slaHistoryRepository->findBy(['sla_timeframe' => 'daily']);
        $weeklySLAs = $this->slaHistoryRepository->findBy(['sla_timeframe' => 'weekly']);
        $monthlySLAs = $this->slaHistoryRepository->findBy(['sla_timeframe' => 'monthly']);

        // assert results
        $this->assertCount(1, $dailySLAs);
        $this->assertCount(1, $weeklySLAs);
        $this->assertCount(1, $monthlySLAs);
        $this->assertSame('daily', $dailySLAs[0]->getSlaTimeframe());
        $this->assertSame('weekly', $weeklySLAs[0]->getSlaTimeframe());
        $this->assertSame('monthly', $monthlySLAs[0]->getSlaTimeframe());
    }

    /**
     * Test repository can find SLA history by multiple criteria
     *
     * @return void
     */
    public function testFindByMultipleCriteria(): void
    {
        // call tested method
        $nginxDailySLAs = $this->slaHistoryRepository->findBy([
            'service_name' => 'nginx',
            'sla_timeframe' => 'daily'
        ]);

        // assert results
        $this->assertCount(1, $nginxDailySLAs);
        $this->assertSame('nginx', $nginxDailySLAs[0]->getServiceName());
        $this->assertSame('daily', $nginxDailySLAs[0]->getSlaTimeframe());
        $this->assertSame(99.95, $nginxDailySLAs[0]->getSlaValue());
    }
}
