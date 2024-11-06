<?php

namespace App\Tests\Manager;

use Exception;
use App\Entity\Metric;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Manager\MetricsManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MetricsManagerTest
 *
 * Test for metrics manager class
 *
 * @package App\Tests\Manager
 */
class MetricsManagerTest extends TestCase
{
    private MetricsManager $metricsManager;
    private ErrorManager & MockObject $errorManagerMock;
    private EntityManagerInterface & MockObject $entityManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);

        // init metrics manager
        $this->metricsManager = new MetricsManager($this->errorManagerMock, $this->entityManagerMock);
    }

    /**
     * Test save metrics
     *
     * @return void
     */
    public function testSaveMetrics(): void
    {
        // usage metrics
        $cpuUsage = 50;
        $ramUsage = 70;
        $storageUsage = 90;

        // mock entity manager
        $this->entityManagerMock->expects($this->exactly(3))->method('persist')
            ->with($this->isInstanceOf(Metric::class));
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
        $this->metricsManager->saveMetrics($cpuUsage, $ramUsage, $storageUsage);
    }

    /**
     * Test save metrics handles flush exception
     *
     * @return void
     */
    public function testSaveMetricsHandlesFlushException(): void
    {
        // usage metrics
        $cpuUsage = 50;
        $ramUsage = 70;
        $storageUsage = 90;

        // mock entity manager
        $this->entityManagerMock->expects($this->exactly(3))->method('persist')
            ->with($this->isInstanceOf(Metric::class));
        $this->entityManagerMock->expects($this->once())->method('flush')
            ->will($this->throwException(new Exception('Database error')));

        // expect error
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            $this->stringContains('error to flush metrics: Database error'),
            $this->equalTo(Response::HTTP_INTERNAL_SERVER_ERROR)
        );

        // call tested method
        $this->metricsManager->saveMetrics($cpuUsage, $ramUsage, $storageUsage);
    }
}
