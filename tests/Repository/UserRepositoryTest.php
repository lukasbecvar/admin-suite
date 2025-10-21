<?php

namespace App\Tests\Repository;

use DateTime;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class UserRepositoryTest
 *
 * Test cases for doctrine user repository
 *
 * @package App\Tests\Repository
 */
#[CoversClass(UserRepository::class)]
class UserRepositoryTest extends KernelTestCase
{
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        // @phpstan-ignore-next-line
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->userRepository = $this->entityManager->getRepository(User::class);

        // create testing data
        $adminUser = new User();
        $adminUser->setUsername('admin-test');
        $adminUser->setPassword('$argon2id$v=19$m=16384,t=6,p=4$Q0ZSLlBtVmZMR0JxdThGUg$MRBG4L4FyD853oBxOYs3+W3S9MNecP9kACc0zZuZR5k');
        $adminUser->setRole('ADMIN');
        $adminUser->setIpAddress('192.168.1.1');
        $adminUser->setUserAgent('PHPUnit Test');
        $adminUser->setRegisterTime(new DateTime());
        $adminUser->setLastLoginTime(new DateTime());
        $adminUser->setToken('admin-token-123');
        $adminUser->setProfilePic('default_pic');
        $regularUser = new User();
        $regularUser->setUsername('user-test');
        $regularUser->setPassword('$argon2id$v=19$m=16384,t=6,p=4$Q0ZSLlBtVmZMR0JxdThGUg$MRBG4L4FyD853oBxOYs3+W3S9MNecP9kACc0zZuZR5k');
        $regularUser->setRole('USER');
        $regularUser->setIpAddress('192.168.1.2');
        $regularUser->setUserAgent('PHPUnit Test');
        $regularUser->setRegisterTime(new DateTime());
        $regularUser->setLastLoginTime(new DateTime());
        $regularUser->setToken('user-token-456');
        $regularUser->setProfilePic('default_pic');

        // save user to database
        $this->entityManager->persist($adminUser);
        $this->entityManager->persist($regularUser);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\User')->execute();
        parent::tearDown();
    }

    /**
     * Test repository can find users by username
     *
     * @return void
     */
    public function testFindByUsername(): void
    {
        // call tested method
        $adminUsers = $this->userRepository->findBy(['username' => 'admin-test']);
        $regularUsers = $this->userRepository->findBy(['username' => 'user-test']);

        // assert results
        $this->assertCount(1, $adminUsers);
        $this->assertCount(1, $regularUsers);
        $this->assertSame('admin-test', $adminUsers[0]->getUsername());
        $this->assertSame('user-test', $regularUsers[0]->getUsername());
    }

    /**
     * Test repository can find users by role
     *
     * @return void
     */
    public function testFindByRole(): void
    {
        // call tested method
        $adminUsers = $this->userRepository->findBy(['role' => 'ADMIN']);
        $regularUsers = $this->userRepository->findBy(['role' => 'USER']);

        // assert results
        $this->assertCount(1, $adminUsers);
        $this->assertCount(1, $regularUsers);
        $this->assertSame('ADMIN', $adminUsers[0]->getRole());
        $this->assertSame('USER', $regularUsers[0]->getRole());
    }

    /**
     * Test repository can find users by token
     *
     * @return void
     */
    public function testFindByToken(): void
    {
        // call tested method
        $adminUsers = $this->userRepository->findBy(['token' => 'admin-token-123']);
        $regularUsers = $this->userRepository->findBy(['token' => 'user-token-456']);

        // assert results
        $this->assertCount(1, $adminUsers);
        $this->assertCount(1, $regularUsers);
        $this->assertSame('admin-token-123', $adminUsers[0]->getToken());
        $this->assertSame('user-token-456', $regularUsers[0]->getToken());
    }

    /**
     * Test repository can find users by IP address
     *
     * @return void
     */
    public function testFindByIpAddress(): void
    {
        // call tested method
        $adminUsers = $this->userRepository->findBy(['ip_address' => '192.168.1.1']);
        $regularUsers = $this->userRepository->findBy(['ip_address' => '192.168.1.2']);

        // assert results
        $this->assertCount(1, $adminUsers);
        $this->assertCount(1, $regularUsers);
        $this->assertSame('192.168.1.1', $adminUsers[0]->getIpAddress());
        $this->assertSame('192.168.1.2', $regularUsers[0]->getIpAddress());
    }
}
