<?php

namespace App\Tests\Repository;

use DateTime;
use App\Entity\User;
use App\Entity\Banned;
use App\Tests\TestEntityFactory;
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
    private User $issuer;
    private User $bannedUser;
    private User $secondBannedUser;
    private BannedRepository $bannedRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        // @phpstan-ignore-next-line
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->bannedRepository = $this->entityManager->getRepository(Banned::class);

        // create test users
        $this->issuer = TestEntityFactory::createUser($this->entityManager, ['username' => 'issuer']);
        $this->bannedUser = TestEntityFactory::createUser($this->entityManager, ['username' => 'banned-user-1']);
        $this->secondBannedUser = TestEntityFactory::createUser($this->entityManager, ['username' => 'banned-user-2']);

        // create test banned entities
        $primaryBan = new Banned();
        $primaryBan->setBannedUser($this->bannedUser);
        $primaryBan->setReason('Violation of rules');
        $primaryBan->setStatus('active');
        $primaryBan->setTime(new DateTime());
        $primaryBan->setBannedBy($this->issuer);
        $secondaryBan = new Banned();
        $secondaryBan->setBannedUser($this->secondBannedUser);
        $secondaryBan->setReason('Abuse');
        $secondaryBan->setStatus('active');
        $secondaryBan->setTime(new DateTime());
        $secondaryBan->setBannedBy($this->issuer);

        // persist test entities
        $this->entityManager->persist($primaryBan);
        $this->entityManager->persist($secondaryBan);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\Banned')->execute();
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\User')->execute();
        parent::tearDown();
    }

    /**
     * Test check if user is banned when user is banned
     *
     * @return void
     */
    public function testCheckIfUserIsBannedWhenBanned(): void
    {
        // call tested method
        $result = $this->bannedRepository->isBanned($this->bannedUser->getId() ?? 0);

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test check if user is banned when user is not banned
     *
     * @return void
     */
    public function testCheckIfUserIsBannedWhenNotBanned(): void
    {
        // call tested method
        $result = $this->bannedRepository->isBanned(999999);

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test get ban reason
     *
     * @return void
     */
    public function testGetBanReason(): void
    {
        // call tested method
        $result = $this->bannedRepository->getBanReason($this->bannedUser->getId() ?? 0);

        // assert result
        $this->assertSame('Violation of rules', $result);
    }

    /**
     * Test update ban status
     *
     * @return void
     */
    public function testUpdateBanStatus(): void
    {
        // call tested method
        $this->bannedRepository->updateBanStatus($this->secondBannedUser->getId() ?? 0, 'inactive');

        /** @var Banned $updated */
        $updated = $this->bannedRepository->findOneBy(['bannedUser' => $this->secondBannedUser]);

        // assert result
        $this->assertSame('inactive', $updated->getStatus());
    }
}
