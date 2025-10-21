<?php

namespace App\Tests\Repository;

use DateTime;
use App\Entity\Metric;
use App\Repository\MetricRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class MetricRepositoryTest
 *
 * Test cases for doctrine metric repository
 *
 * @package App\Tests\Repository
 */
#[CoversClass(MetricRepository::class)]
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

    /**
     * Test get old metrics method
     *
     * @return void
     */
    public function testGetOldMetrics(): void
    {
        // create cutoff date (should return metrics older than this date)
        $cutoffDate = new DateTime('-1.5 days');

        // call tested method
        $result = $this->metricRepository->getOldMetrics($cutoffDate);

        // assert result (should return metric2 which is 2 days old)
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));

        // find the metric that is 2 days old
        $oldMetric = null;
        foreach ($result as $metric) {
            if ($metric->getName() === 'cpu-usage' && $metric->getValue() === '70') {
                $oldMetric = $metric;
                break;
            }
        }

        $this->assertNotNull($oldMetric, 'Should find the 2-day old metric');
        $this->assertInstanceOf(Metric::class, $oldMetric);
        $this->assertEquals('cpu-usage', $oldMetric->getName());
        $this->assertEquals('70', $oldMetric->getValue());
        $this->assertEquals('service-1', $oldMetric->getServiceName());
    }

    /**
     * Test get old metrics with no results
     *
     * @return void
     */
    public function testGetOldMetricsWithNoResults(): void
    {
        // create cutoff date that should return no results (all metrics are newer)
        $cutoffDate = new DateTime('-3 days');

        // call tested method
        $result = $this->metricRepository->getOldMetrics($cutoffDate);

        // assert result is empty
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test get recent metrics method
     *
     * @return void
     */
    public function testGetRecentMetrics(): void
    {
        // create cutoff date (should return metrics newer than or equal to this date)
        // Use -3 days to ensure we get all test metrics
        $cutoffDate = new DateTime('-3 days');

        // call tested method
        $result = $this->metricRepository->getRecentMetrics($cutoffDate);

        // assert result (should return all test metrics since they are all newer than -3 days)
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));

        if (!empty($result)) {
            // check first result structure
            $this->assertArrayHasKey('name', $result[0]);
            $this->assertArrayHasKey('value', $result[0]);
            $this->assertArrayHasKey('service_name', $result[0]);
            $this->assertArrayHasKey('time', $result[0]);

            // check that we get some expected metrics
            $names = array_column($result, 'name');
            $this->assertTrue(
                in_array('cpu-usage', $names) || in_array('ram-usage', $names),
                'Should contain at least one of the expected recent metrics'
            );
        }
    }

    /**
     * Test get recent metrics with no results
     *
     * @return void
     */
    public function testGetRecentMetricsWithNoResults(): void
    {
        // create cutoff date that should return no results (all metrics are older)
        $cutoffDate = new DateTime('+1 day');

        // call tested method
        $result = $this->metricRepository->getRecentMetrics($cutoffDate);

        // assert result is empty
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test get recent metrics returns array result
     *
     * @return void
     */
    public function testGetRecentMetricsReturnsArrayResult(): void
    {
        // create cutoff date
        $cutoffDate = new DateTime('-1.5 days');

        // call tested method
        $result = $this->metricRepository->getRecentMetrics($cutoffDate);

        // assert result structure (should be array of arrays, not entities)
        $this->assertIsArray($result);
        if (!empty($result)) {
            $this->assertIsArray($result[0]);
            $this->assertArrayHasKey('name', $result[0]);
            $this->assertArrayHasKey('value', $result[0]);
            $this->assertArrayHasKey('service_name', $result[0]);
            $this->assertArrayHasKey('time', $result[0]);
        }
    }
}
