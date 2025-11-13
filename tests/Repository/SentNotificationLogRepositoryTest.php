<?php

namespace App\Tests\Repository;

use DateTime;
use App\Entity\User;
use App\Tests\TestEntityFactory;
use App\Entity\SentNotificationLog;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use App\Repository\SentNotificationLogRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class SentNotificationLogRepository
 *
 * Test cases for doctrine sent notification log repository
 *
 * @package App\Tests\Repository
 */
#[CoversClass(SentNotificationLogRepository::class)]
class SentNotificationLogRepositoryTest extends KernelTestCase
{
    private User $receiver;
    private EntityManagerInterface $entityManager;
    private SentNotificationLogRepository $sentNotificationLogRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        // @phpstan-ignore-next-line
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->sentNotificationLogRepository = $this->entityManager->getRepository(SentNotificationLog::class);
        $this->receiver = TestEntityFactory::createUser($this->entityManager);

        // create testing data
        $log = new SentNotificationLog();
        $log->setTitle('Test Notification');
        $log->setMessage('This is a test notification.');
        $log->setSentTime(new DateTime());
        $log->setReceiver($this->receiver);

        // save log to database
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\SentNotificationLog')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        parent::tearDown();
    }

    /**
     * Test repository can find logs by title
     *
     * @return void
     */
    public function testFindByTitle(): void
    {
        // call tested method
        $logs = $this->sentNotificationLogRepository->findBy(['title' => 'Test Notification']);

        // assert results
        $this->assertCount(1, $logs);
        $this->assertSame('Test Notification', $logs[0]->getTitle());
    }

    /**
     * Test repository can find logs by receiver
     *
     * @return void
     */
    public function testFindByReceiver(): void
    {
        // call tested method
        $logs = $this->sentNotificationLogRepository->findBy(['receiver' => $this->receiver]);

        // assert results
        $this->assertCount(1, $logs);
        $this->assertSame($this->receiver, $logs[0]->getReceiver());
    }
}
