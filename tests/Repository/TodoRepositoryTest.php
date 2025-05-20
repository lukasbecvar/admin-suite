<?php

namespace App\Tests\Repository;

use DateTime;
use App\Entity\Todo;
use App\Repository\TodoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class TodoRepositoryTest
 *
 * Test cases for doctrine todo repository
 *
 * @package App\Tests\Repository
 */
class TodoRepositoryTest extends KernelTestCase
{
    private TodoRepository $todoRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        // @phpstan-ignore-next-line
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->todoRepository = $this->entityManager->getRepository(Todo::class);

        // create testing data
        $todo1 = new Todo();
        $todo1->setTodoText('Test todo 1');
        $todo1->setAddedTime(new DateTime('2025-01-01 12:00:00'));
        $todo1->setCompletedTime(null);
        $todo1->setStatus('pending');
        $todo1->setUserId(1);
        $todo2 = new Todo();
        $todo2->setTodoText('Test todo 2');
        $todo2->setAddedTime(new DateTime('2025-01-02 14:00:00'));
        $todo2->setCompletedTime(null);
        $todo2->setStatus('pending');
        $todo2->setUserId(1);
        $todo3 = new Todo();
        $todo3->setTodoText('Test todo 3');
        $todo3->setAddedTime(new DateTime('2025-01-03 16:00:00'));
        $todo3->setCompletedTime(new DateTime('2025-01-04 18:00:00'));
        $todo3->setStatus('completed');
        $todo3->setUserId(2);
        $this->entityManager->persist($todo1);
        $this->entityManager->persist($todo2);
        $this->entityManager->persist($todo3);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\Todo')->execute();
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
        $result = $this->todoRepository->findByUserIdAndStatus(1, 'pending');

        // assert result
        $this->assertCount(2, $result);
        $this->assertSame('Test todo 1', $result[0]->getTodoText());
        $this->assertSame('Test todo 2', $result[1]->getTodoText());
        $this->assertSame('pending', $result[1]->getStatus());
        $this->assertSame('pending', $result[0]->getStatus());
        $this->assertSame(1, $result[0]->getUserId());
        $this->assertSame(1, $result[1]->getUserId());
    }

    /**
     * Test find todos by user ID and status with no results
     *
     * @return void
     */
    public function testFindByUserIdAndStatusNoResults(): void
    {
        // call tested method
        $result = $this->todoRepository->findByUserIdAndStatus(3, 'pending');

        // assert result
        $this->assertEmpty($result);
    }
}
