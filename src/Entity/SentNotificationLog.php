<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\SentNotificationLogRepository;

/**
 * Class SentNotificationLog
 *
 * Entity object for mapping sent notifications log table
 *
 * @package App\Entity
 */
#[ORM\Table(name: 'sent_notifications_logs')]
#[ORM\Index(name: 'sent_notifications_logs_sent_time_idx', columns: ['sent_time'])]
#[ORM\Index(name: 'sent_notifications_logs_receiver_id_idx', columns: ['receiver_id'])]
#[ORM\Entity(repositoryClass: SentNotificationLogRepository::class)]
class SentNotificationLog
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $sent_time = null;

    #[ORM\Column]
    private ?int $receiver_id = null;

    /**
     * Get database ID of the sent notification log
     *
     * @return int|null The database ID of the sent notification log or null if not found
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get title of the sent notification
     *
     * @return string|null The title of the sent notification or null if not found
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Set title of the sent notification
     *
     * @param string $title The title of the sent notification
     *
     * @return static The sent notification log object
     */
    public function setTitle(string $title): static
    {
        // prevent maximal length
        if (strlen($title) > 255) {
            $title = substr($title, 0, 250) . "...";
        }

        $this->title = $title;

        return $this;
    }

    /**
     * Get message of the sent notification
     *
     * @return string|null The message of the sent notification or null if not found
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Set message of the sent notification
     *
     * @param string $message The message of the sent notification
     *
     * @return static The sent notification log object
     */
    public function setMessage(string $message): static
    {
        // prevent maximal length
        if (strlen($message) > 255) {
            $message = substr($message, 0, 250) . "...";
        }

        $this->message = $message;

        return $this;
    }

    /**
     * Get sent time of the notification
     *
     * @return DateTimeInterface|null The sent time of the notification or null if not found
     */
    public function getSentTime(): ?DateTimeInterface
    {
        return $this->sent_time;
    }

    /**
     * Set sent time of the notification
     *
     * @param DateTimeInterface $sent_time The sent time of the notification
     *
     * @return static The sent notification log object
     */
    public function setSentTime(DateTimeInterface $sent_time): static
    {
        $this->sent_time = $sent_time;

        return $this;
    }

    /**
     * Get receiver id associated with the notification
     *
     * @return int|null The receiver id associated with the notification or null if not found
     */
    public function getReceiverId(): ?int
    {
        return $this->receiver_id;
    }

    /**
     * Set receiver id associated with the notification
     *
     * @param int $receiver_id The receiver id associated with the notification
     *
     * @return static The sent notification log object
     */
    public function setReceiverId(int $receiver_id): static
    {
        $this->receiver_id = $receiver_id;

        return $this;
    }
}
