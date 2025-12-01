<?php

namespace App\Tests\Formatter;

use Monolog\Level;
use ReflectionObject;
use Monolog\LogRecord;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use App\Formatter\MonologTimeFormatter;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class MonologTimeFormatterTest
 *
 * Test cases for monolog time formatter
 *
 * @package App\Tests\Formatter
 */
#[CoversClass(MonologTimeFormatter::class)]
class MonologTimeFormatterTest extends TestCase
{
    private MonologTimeFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new MonologTimeFormatter();
    }

    /**
     * Test formatter initialization
     *
     * @return void
     */
    public function testFormatterInitialization(): void
    {
        // check if formatter is initialized with the correct date format
        $reflection = new ReflectionObject($this->formatter);
        $parentClass = $reflection->getParentClass();
        $this->assertNotFalse($parentClass);
        $property = $parentClass->getProperty('dateFormat');
        $this->assertEquals('Y-m-d H:i:s', $property->getValue($this->formatter));
    }

    /**
     * Test formatter formats date correctly
     *
     * @return void
     */
    public function testFormatterFormatsDateCorrectly(): void
    {
        // create log record with a known date
        $dateTime = new DateTimeImmutable('2023-01-01 12:34:56');
        $record = new LogRecord($dateTime, 'test-channel', Level::Info, 'Test message', [], []);

        // format record
        $formattedRecord = $this->formatter->format($record);

        // cechk if datetime is formatted correctly (Y-m-d H:i:s)
        $this->assertStringContainsString('2023-01-01 12:34:56', $formattedRecord);
    }

    /**
     * Test formatter formats message correctly
     *
     * @return void
     */
    public function testFormatterFormatsMessageCorrectly(): void
    {
        // create log record with a test message
        $record = new LogRecord(new DateTimeImmutable(), 'test-channel', Level::Info, 'Test message', [], []);

        // format record
        $formattedRecord = $this->formatter->format($record);

        // check if message is included in the formatted record
        $this->assertStringContainsString('Test message', $formattedRecord);
    }

    /**
     * Test formatter formats level correctly
     *
     * @return void
     */
    public function testFormatterFormatsLevelCorrectly(): void
    {
        // create log record with a specific level
        $record = new LogRecord(new DateTimeImmutable(), 'test-channel', Level::Error, 'Test message', [], []);

        // format record
        $formattedRecord = $this->formatter->format($record);

        // check if level is included in the formatted record
        $this->assertStringContainsString('ERROR', $formattedRecord);
    }

    /**
     * Test formatter formats channel correctly
     *
     * @return void
     */
    public function testFormatterFormatsChannelCorrectly(): void
    {
        // create a log record with a specific channel
        $record = new LogRecord(new DateTimeImmutable(), 'test-channel', Level::Info, 'Test message', [], []);

        // format record
        $formattedRecord = $this->formatter->format($record);

        // check if channel is included in the formatted record
        $this->assertStringContainsString('test-channel', $formattedRecord);
    }
}
