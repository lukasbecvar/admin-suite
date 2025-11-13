<?php

namespace App\Tests\Repository;

use DateTime;
use App\Entity\User;
use App\Tests\TestEntityFactory;
use App\Entity\NotificationSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use App\Repository\NotificationSubscriberRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class NotificationSubscriberRepositoryTest
 *
 * Test cases for doctrine notification subscriber repository
 *
 * @package App\Tests\Repository
 */
#[CoversClass(NotificationSubscriberRepository::class)]
class NotificationSubscriberRepositoryTest extends KernelTestCase
{
    private User $userOne;
    private User $userTwo;
    private EntityManagerInterface $entityManager;
    private NotificationSubscriberRepository $notificationSubscriberRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        // @phpstan-ignore-next-line
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->notificationSubscriberRepository = $this->entityManager->getRepository(NotificationSubscriber::class);

        // mock test users
        $this->userOne = TestEntityFactory::createUser($this->entityManager, ['username' => 'subscriber-user-1']);
        $this->userTwo = TestEntityFactory::createUser($this->entityManager, ['username' => 'subscriber-user-2']);

        // create testing data
        $subscriber = new NotificationSubscriber();
        $subscriber->setEndpoint('https://fcm.googleapis.com/fcm/send/test-endpoint');
        $subscriber->setPublicKey('test-public-key');
        $subscriber->setAuthToken('test-auth-token');
        $subscriber->setSubscribedTime(new DateTime());
        $subscriber->setStatus('active');
        $subscriber->setUser($this->userOne);
        $inactiveSubscriber = new NotificationSubscriber();
        $inactiveSubscriber->setEndpoint('https://fcm.googleapis.com/fcm/send/inactive-endpoint');
        $inactiveSubscriber->setPublicKey('inactive-public-key');
        $inactiveSubscriber->setAuthToken('inactive-auth-token');
        $inactiveSubscriber->setSubscribedTime(new DateTime());
        $inactiveSubscriber->setStatus('inactive');
        $inactiveSubscriber->setUser($this->userTwo);

        // save subscriber to database
        $this->entityManager->persist($subscriber);
        $this->entityManager->persist($inactiveSubscriber);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\NotificationSubscriber')->execute();
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\User')->execute();
        parent::tearDown();
    }

    /**
     * Test repository can find subscribers by status
     *
     * @return void
     */
    public function testFindByStatus(): void
    {
        // call tested method
        $activeSubscribers = $this->notificationSubscriberRepository->findBy(['status' => 'active']);
        $inactiveSubscribers = $this->notificationSubscriberRepository->findBy(['status' => 'inactive']);

        // assert results
        $this->assertNotEmpty($activeSubscribers);
        $this->assertNotEmpty($inactiveSubscribers);
        $this->assertSame('active', $activeSubscribers[0]->getStatus());
        $this->assertSame('inactive', $inactiveSubscribers[0]->getStatus());
    }

    /**
     * Test repository can find subscribers by user id
     *
     * @return void
     */
    public function testFindByUserId(): void
    {
        // call tested method
        $user1Subscribers = $this->notificationSubscriberRepository->findBy(['user' => $this->userOne]);
        $user2Subscribers = $this->notificationSubscriberRepository->findBy(['user' => $this->userTwo]);

        // assert results
        $this->assertNotEmpty($user1Subscribers);
        $this->assertNotEmpty($user2Subscribers);
        $this->assertSame($this->userOne->getId(), $user1Subscribers[0]->getUser()?->getId());
        $this->assertSame($this->userTwo->getId(), $user2Subscribers[0]->getUser()?->getId());
    }

    /**
     * Test repository can find subscribers by endpoint
     *
     * @return void
     */
    public function testFindByEndpoint(): void
    {
        // call tested method
        $subscribers = $this->notificationSubscriberRepository->findBy(['endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint']);

        // assert results
        $this->assertNotEmpty($subscribers);
        $this->assertSame('https://fcm.googleapis.com/fcm/send/test-endpoint', $subscribers[0]->getEndpoint());
    }
}
