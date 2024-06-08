<?php

namespace App\Tests\Manager;

use PHPUnit\Framework\TestCase;
use App\Manager\UserManager;
use App\Entity\User;
use App\Manager\ErrorManager;
use App\Manager\LogManager;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\MakerBundle\Doctrine\EntityRelation;
use Symfony\Component\HttpFoundation\Response;

class UserManagerTest extends TestCase
{
    /** @var EntityManagerInterface|MockObject */
    private EntityManagerInterface|MockObject $entityManagerMock;

    /** @var LogManager|MockObject */
    private LogManager|MockObject $logManagerMock;

    /** @var ErrorManager */
    private ErrorManager $errorManagerMock;

    /** @var UserManager */
    private UserManager $userManager;

    /** @var UserRepository|MockObject */
    private UserRepository|MockObject $userRepositoryMock;

    protected function setUp(): void
    {
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->logManagerMock = $this->createMock(LogManager::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->userRepositoryMock = $this->createMock(UserRepository::class); // Use EntityRepository mock instead

        $this->entityManagerMock->method('getRepository')->willReturn($this->userRepositoryMock);

        $this->userManager = new UserManager($this->logManagerMock, $this->errorManagerMock, $this->entityManagerMock);
    }


    public function testGetUserRepo(): void
    {
        $user = new User();
        $this->userRepositoryMock->method('findOneBy')->willReturn($user);

        $result = $this->userManager->getUserRepo(['username' => 'test']);
        $this->assertInstanceOf(User::class, $result);
    }

    public function testCheckIfUserExist(): void
    {
        $this->userRepositoryMock->method('findOneBy')->willReturn(new User());

        $result = $this->userManager->checkIfUserExist('test');
        $this->assertTrue($result);
    }

    public function testGetUsernameById(): void
    {
        $user = new User();
        $user->setUsername('test');
        $this->userRepositoryMock->method('findOneBy')->willReturn($user);

        $result = $this->userManager->getUsernameById(1);
        $this->assertEquals('test', $result);
    }

    public function testGetUserRoleById(): void
    {
        $user = new User();
        $user->setRole('ROLE_USER');
        $this->userRepositoryMock->method('findOneBy')->willReturn($user);

        $result = $this->userManager->getUserRoleById(1);
        $this->assertEquals('ROLE_USER', $result);
    }

    public function testIsUserAdmin(): void
    {
        $user = new User();
        $user->setRole('ADMIN');
        $this->userRepositoryMock->method('findOneBy')->willReturn($user);

        $result = $this->userManager->isUserAdmin(1);
        $this->assertTrue($result);
    }

    public function testUpdateUserRole(): void
    {
        $user = new User();
        $this->userRepositoryMock->method('findOneBy')->willReturn($user);

        $this->logManagerMock->expects($this->once())->method('log');
        $this->entityManagerMock->expects($this->once())->method('flush');

        $this->userManager->updateUserRole(1, 'admin');
        $this->assertEquals('ADMIN', $user->getRole());
    }

    public function testIsUsersEmpty(): void
    {
        $result = $this->userManager->isUsersEmpty();
        $this->assertIsBool($result);
    }
}
