<?php

namespace App\Tests\Repository;

use DateTime;
use App\Entity\Todo;
use App\Entity\User;
use App\Tests\TestEntityFactory;
use App\Repository\TodoRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class TodoRepositoryTest
 *
 * Test cases for doctrine todo repository
 *
 * @package App\Tests\Repository
 */
#[CoversClass(TodoRepository::class)]
class TodoRepositoryTest extends KernelTestCase
{
    private User $userOne;
    private User $userTwo;
    private TodoRepository $todoRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        // @phpstan-ignore-next-line
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->todoRepository = $this->entityManager->getRepository(Todo::class);

        // mock testing users
        $this->userOne = TestEntityFactory::createUser($this->entityManager, ['username' => 'todo-user-1']);
        $this->userTwo = TestEntityFactory::createUser($this->entityManager, ['username' => 'todo-user-2']);

        // create testing data
        $todo1 = new Todo();
        $todo1->setTodoText('Test todo 1');
        $todo1->setAddedTime(new DateTime('2025-01-01 12:00:00'));
        $todo1->setCompletedTime(null);
        $todo1->setStatus('pending');
        $todo1->setUser($this->userOne);
        $todo2 = new Todo();
        $todo2->setTodoText('Test todo 2');
        $todo2->setAddedTime(new DateTime('2025-01-02 14:00:00'));
        $todo2->setCompletedTime(null);
        $todo2->setStatus('pending');
        $todo2->setUser($this->userOne);
        $todo3 = new Todo();
        $todo3->setTodoText('Test todo 3');
        $todo3->setAddedTime(new DateTime('2025-01-03 16:00:00'));
        $todo3->setCompletedTime(new DateTime('2025-01-04 18:00:00'));
        $todo3->setStatus('completed');
        $todo3->setUser($this->userTwo);
        $this->entityManager->persist($todo1);
        $this->entityManager->persist($todo2);
        $this->entityManager->persist($todo3);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\Todo')->execute();
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\User')->execute();
        parent::tearDown();
    }

    /**
     * Test find todos by user ID and status
     *
     * @return void
     */
    public function testFindByUserIdAndStatus(): void
    {
        // call tested method
        $result = $this->todoRepository->findByUserIdAndStatus($this->userOne->getId() ?? 0, 'pending');

        // assert result
        $this->assertCount(2, $result);
        $this->assertSame('Test todo 1', $result[0]->getTodoText());
        $this->assertSame('Test todo 2', $result[1]->getTodoText());
        $this->assertSame('pending', $result[1]->getStatus());
        $this->assertSame('pending', $result[0]->getStatus());
        $this->assertSame($this->userOne->getId(), $result[0]->getUser()?->getId());
        $this->assertSame($this->userOne->getId(), $result[1]->getUser()?->getId());
    }

    /**
     * Test find todos by user ID and status with no results
     *
     * @return void
     */
    public function testFindByUserIdAndStatusNoResults(): void
    {
        // call tested method
        $result = $this->todoRepository->findByUserIdAndStatus(($this->userTwo->getId() ?? 0) + 1000, 'pending');

        // assert result
        $this->assertEmpty($result);
    }
}
