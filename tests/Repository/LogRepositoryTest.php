<?php

namespace App\Tests\Repository;

use DateTime;
use App\Entity\Log;
use App\Repository\LogRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class LogRepositoryTest
 *
 * Test cases for doctrine log repository
 *
 * @package App\Tests\Repository
 */
#[CoversClass(LogRepository::class)]
class LogRepositoryTest extends KernelTestCase
{
    private LogRepository $logRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        // @phpstan-ignore-next-line
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->logRepository = $this->entityManager->getRepository(Log::class);

        // create testing data
        $log = new Log();
        $log->setName('error');
        $log->setMessage('Test error message');
        $log->setTime(new DateTime());
        $log->setIpAddress('127.0.0.1');
        $log->setUserAgent('PHPUnit Test');
        $log->setUserId(1);
        $log->setLevel(400);
        $log->setStatus('new');

        // save log to database
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\Log')->execute();
        parent::tearDown();
    }

    /**
     * Test repository can find logs by name
     *
     * @return void
     */
    public function testFindByName(): void
    {
        // call tested method
        $logs = $this->logRepository->findBy(['name' => 'error']);

        // assert result
        $this->assertNotEmpty($logs);
        $this->assertSame('error', $logs[0]->getName());
    }

    /**
     * Test repository can find logs by user id
     *
     * @return void
     */
    public function testFindByUserId(): void
    {
        // call tested method
        $logs = $this->logRepository->findBy(['user_id' => 1]);

        // assert result
        $this->assertNotEmpty($logs);
        $this->assertSame(1, $logs[0]->getUserId());
    }

    /**
     * Test repository can find logs by message
     *
     * @return void
     */
    public function testFindByMessage(): void
    {
        // call tested method
        $logs = $this->logRepository->findBy(['message' => 'Test error message']);

        // assert result
        $this->assertNotEmpty($logs);
        $this->assertSame('Test error message', $logs[0]->getMessage());
    }
}
