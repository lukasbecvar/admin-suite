<?php

namespace App\Tests\Repository;

use DateTime;
use App\Entity\Metric;
use App\Repository\MetricRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class MetricRepositoryTest
 *
 * Test cases for doctrine metric repository
 *
 * @package App\Tests\Repository
 */
class MetricRepositoryTest extends KernelTestCase
{
    private MetricRepository $metricRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        // @phpstan-ignore-next-line
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->metricRepository = $this->entityManager->getRepository(Metric::class);

        // create testing data
        $metric1 = new Metric();
        $metric1->setName('cpu-usage');
        $metric1->setValue('50');
        $metric1->setServiceName('service-1');
        $metric1->setTime(new DateTime('-1 day'));
        $metric2 = new Metric();
        $metric2->setName('cpu-usage');
        $metric2->setValue('70');
        $metric2->setServiceName('service-1');
        $metric2->setTime(new DateTime('-2 days'));
        $metric3 = new Metric();
        $metric3->setName('ram-usage');
        $metric3->setValue('80');
        $metric3->setServiceName('service-2');
        $metric3->setTime(new DateTime('-1 day'));
        $this->entityManager->persist($metric1);
        $this->entityManager->persist($metric2);
        $this->entityManager->persist($metric3);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\Metric')->execute();
        parent::tearDown();
    }

    /**
     * Test find metrics by name and service method
     *
     * @return void
     */
    public function testFindMetricsByNameAndService(): void
    {
        // call tested method
        $metrics = $this->metricRepository->findMetricsByNameAndService('cpu-usage', 'service-1');

        // assert result
        $this->assertCount(2, $metrics);
        $this->assertEquals('cpu-usage', $metrics[0]->getName());
        $this->assertEquals('service-1', $metrics[0]->getServiceName());
        $this->assertEquals('cpu-usage', $metrics[1]->getName());
        $this->assertEquals('service-1', $metrics[1]->getServiceName());
        $this->assertEquals('50', $metrics[0]->getValue());
        $this->assertEquals('70', $metrics[1]->getValue());
    }

    /**
     * Test get metrics by name and time period method
     *
     * @return void
     */
    public function testGetMetricsByNameAndTimePeriod(): void
    {
        // call tested method
        $result = $this->metricRepository->getMetricsByNameAndTimePeriod('cpu-usage', 'service-1', 'last_week');

        // assert result
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('date', $result[0]);
        $this->assertArrayHasKey('average', $result[0]);
        $this->assertIsString($result[0]['date']);
        $this->assertIsFloat($result[0]['average']);
    }

    /**
     * Test get metrics by service name
     *
     * @return void
     */
    public function testGetMetricsByServiceName(): void
    {
        // call tested method
        $result = $this->metricRepository->getMetricsByServiceName('service-1', 'last_week');

        // assert result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('cpu-usage', $result);
        $this->assertCount(2, $result['cpu-usage']);
        $this->assertArrayHasKey('value', $result['cpu-usage'][0]);
        $this->assertArrayHasKey('time', $result['cpu-usage'][0]);
    }
}
