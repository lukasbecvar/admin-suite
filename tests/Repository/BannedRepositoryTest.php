<?php

namespace App\Tests\Repository;

use DateTime;
use App\Entity\Banned;
use App\Repository\BannedRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class BannedRepositoryTest
 *
 * Test cases for doctrine banned repository
 *
 * @package App\Tests\Repository
 */
#[CoversClass(BannedRepository::class)]
class BannedRepositoryTest extends KernelTestCase
{
    private BannedRepository $bannedRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        // @phpstan-ignore-next-line
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->bannedRepository = $this->entityManager->getRepository(Banned::class);

        // create testing data
        $banned = new Banned();
        $banned->setBannedUserId(1337);
        $banned->setReason('Violation of rules');
        $banned->setStatus('active');
        $banned->setTime(new DateTime());
        $banned->setBannedById(99);
        $banned1 = new Banned();
        $banned1->setBannedUserId(3);
        $banned1->setReason('Abuse');
        $banned1->setStatus('active');
        $banned1->setTime(new DateTime());
        $banned1->setBannedById(99);
        $this->entityManager->persist($banned);
        $this->entityManager->persist($banned1);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\Banned')->execute();
        parent::tearDown();
    }

    /**
     * Test check if user is banned when banned
     *
     * @return void
     */
    public function testCheckIfUserIsBannedWhenBanned(): void
    {
        // call tested method
        $result = $this->bannedRepository->isBanned(1337);

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test check if user is banned when not banned
     *
     * @return void
     */
    public function testCheckIfUserIsBannedWhenNotBanned(): void
    {
        // call tested method
        $result = $this->bannedRepository->isBanned(13379);

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test get ban reason when banned
     *
     * @return void
     */
    public function testGetBanReason(): void
    {
        // call tested method
        $result = $this->bannedRepository->getBanReason(1337);

        // assert result
        $this->assertSame('Violation of rules', $result);
    }

    /**
     * Test update ban status when banned
     *
     * @return void
     */
    public function testUpdateBanStatus(): void
    {
        // call tested method
        $this->bannedRepository->updateBanStatus(3, 'inactive');

        /** @var \App\Entity\Banned $banned fetch updated ban */
        $banned = $this->bannedRepository->findOneBy(['banned_user_id' => 3]);

        // assert result
        $this->assertSame('inactive', $banned->getStatus());
    }
}
