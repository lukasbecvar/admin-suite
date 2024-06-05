<?php

namespace App\Tests\Event\Subscriber;

use App\Manager\LogManager;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use App\Event\Subscriber\ExceptionEventSubscriber;

/**
 * Class ExceptionEventSubscriberTest
 *
 * Test the exception event subscriber
 *
 * @package App\Tests\Event\Subscriber
 */
class ExceptionEventSubscriberTest extends TestCase
{
    /**
     * Test if the event can be logged
     *
     * @return void
     */
    public function testCanBeEventLogged(): void
    {
        $logManager = $this->createMock(LogManager::class);
        $logger = $this->createMock(LoggerInterface::class);
        $subscriber = new ExceptionEventSubscriber($logManager, $logger);

        // test error message without blocked pattern
        $this->assertTrue($subscriber->canBeEventLogged('Normal error message'));

        // test error message with blocked pattern
        $this->assertFalse($subscriber->canBeEventLogged('log-error: Something went wrong'));
    }
}
