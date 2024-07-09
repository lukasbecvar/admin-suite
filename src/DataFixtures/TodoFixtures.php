<?php

namespace App\DataFixtures;

use App\Entity\Todo;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

/**
 * Class TodoFixtures
 *
 * The testing todo data fixtures
 *
 * @package App\DataFixtures
 */
class TodoFixtures extends Fixture
{
    /**
     * Load the todo fixtures
     *
     * @param ObjectManager $manager
     *
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        // user ID 1 todos
        for ($i = 1; $i <= 80; $i++) {
            $todo = new Todo();
            $todo->setTodoText("Todo item for user 1 - Todo $i");
            $todo->setAddedTime(new \DateTime());
            $todo->setStatus('pending');
            $todo->setUserId(1);

            // set completed_time for some todos
            if ($i % 3 == 0) {
                $todo->setCompletedTime(new \DateTime());
            }

            $manager->persist($todo);
        }

        // user ID 2 todos
        for ($i = 1; $i <= 20; $i++) {
            $todo = new Todo();
            $todo->setTodoText("Todo item for user 2 - Todo $i");
            $todo->setAddedTime(new \DateTime());
            $todo->setStatus('pending');
            $todo->setUserId(2);

            // set completed_time for some todos
            if ($i % 4 == 0) {
                $todo->setCompletedTime(new \DateTime());
            }

            $manager->persist($todo);
        }

        // flush the data to the database
        $manager->flush();
    }
}
