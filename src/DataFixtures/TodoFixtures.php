<?php

namespace App\DataFixtures;

use DateTime;
use App\Entity\Todo;
use App\Entity\User;
use App\Util\SecurityUtil;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

/**
 * Class TodoFixtures
 *
 * Testing todo data fixtures for fill database with test data
 *
 * @package App\DataFixtures
 */
class TodoFixtures extends Fixture implements DependentFixtureInterface
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
        // get user
        $user = $manager->getRepository(User::class)->findOneBy([]);
        if ($user === null) {
            return;
        }

        for ($i = 1; $i <= 20; $i++) {
            $todo = new Todo();
            $todo->setTodoText($this->securityUtil->encryptAes("Todo item for user 1 - Todo $i"))
                ->setAddedTime(new DateTime())
                ->setStatus('open')
                ->setUser($user);

            // set completed_time for some todos
            if ($i % 3 == 0) {
                $todo->setCompletedTime(new DateTime())
                    ->setStatus('closed');
            }

            $manager->persist($todo);
        }

        // flush data to database
        $manager->flush();
    }

    /**
     * Declare fixture dependencies (ensure that the fixture is loaded after user fixtures)
     *
     * @return array<Class-string> The array of dependencies
     */
    public function getDependencies(): array
    {
        return [
            UserFixtures::class
        ];
    }
}
