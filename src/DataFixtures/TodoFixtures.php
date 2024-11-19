<?php

namespace App\DataFixtures;

use DateTime;
use App\Entity\Todo;
use App\Util\SecurityUtil;
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
    private SecurityUtil $securityUtil;

    public function __construct(SecurityUtil $securityUtil)
    {
        $this->securityUtil = $securityUtil;
    }

    /**
     * Load todo fixtures
     *
     * @param ObjectManager $manager
     *
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $todo = new Todo();
            $todo->setTodoText($this->securityUtil->encryptAes("Todo item for user 1 - Todo $i"))
                ->setAddedTime(new DateTime())
                ->setStatus('open')
                ->setUserId(1);

            // set completed_time for some todos
            if ($i % 3 == 0) {
                $todo->setCompletedTime(new DateTime())
                    ->setStatus('closed');
            }

            $manager->persist($todo);
        }

        // flush data to the database
        $manager->flush();
    }
}
