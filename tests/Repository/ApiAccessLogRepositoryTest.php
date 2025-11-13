<?php

namespace App\Tests\Repository;

use DateTime;
use App\Entity\User;
use App\Entity\ApiAccessLog;
use App\Tests\TestEntityFactory;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ApiAccessLogRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class ApiAccessLogRepositoryTest
 *
 * Test cases for doctrine api access log repository
 *
 * @package App\Tests\Repository
 */
#[CoversClass(ApiAccessLogRepository::class)]
class ApiAccessLogRepositoryTest extends KernelTestCase
{
    private User $user;
    private EntityManagerInterface $entityManager;
    private ApiAccessLogRepository $apiAccessLogRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        // @phpstan-ignore-next-line
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->apiAccessLogRepository = $this->entityManager->getRepository(ApiAccessLog::class);
        $this->user = TestEntityFactory::createUser($this->entityManager);

        // create testing data
        $log = new ApiAccessLog();
        $log->setUrl('/api/test');
        $log->setMethod('GET');
        $log->setTime(new DateTime());
        $log->setUser($this->user);

        // save log to database
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\ApiAccessLog')->execute();
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\User')->execute();
        parent::tearDown();
    }

    /**
     * Test repository can find logs by url
     *
     * @return void
     */
    public function testFindByUrl(): void
    {
        // call tested method
        $logs = $this->apiAccessLogRepository->findBy(['url' => '/api/test']);

        // assert results
        $this->assertCount(1, $logs);
        $this->assertSame('/api/test', $logs[0]->getUrl());
    }

    /**
     * Test repository can find logs by method
     *
     * @return void
     */
    public function testFindByMethod(): void
    {
        // call tested method
        $logs = $this->apiAccessLogRepository->findBy(['method' => 'GET']);

        // assert results
        $this->assertCount(1, $logs);
        $this->assertSame('GET', $logs[0]->getMethod());
    }

    /**
     * Test repository can find logs by user
     *
     * @return void
     */
    public function testFindByUser(): void
    {
        // call tested method
        $logs = $this->apiAccessLogRepository->findBy(['user' => $this->user]);

        // assert results
        $this->assertCount(1, $logs);
        $this->assertSame($this->user, $logs[0]->getUser());
    }
}
