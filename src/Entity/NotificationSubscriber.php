<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\NotificationSubscriberRepository;

/**
 * Class NotificationSubscriber
 *
 * The NotificationSubscriber entity database table mapping class
 *
 * @package App\Entity
 */
#[ORM\Table(name: 'notifications_subscribers')]
#[ORM\Index(name: 'status_idx', columns: ['status'])]
#[ORM\Index(name: 'user_id_idx', columns: ['user_id'])]
#[ORM\Entity(repositoryClass: NotificationSubscriberRepository::class)]
class NotificationSubscriber
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $endpoint = null;

    #[ORM\Column(length: 255)]
    private ?string $publicKey = null;

    #[ORM\Column(length: 255)]
    private ?string $authToken = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $subscribed_time = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column]
    private ?int $user_id = null;

    /**
     * Get the id of the notification subscriber
     *
     * @return int|null The id of the notification subscriber or null if not found
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the endpoint of the notification subscriber
     *
     * @return string|null The endpoint of the notification subscriber or null if not found
     */
    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    /**
     * Set the endpoint of the notification subscriber
     *
     * @param string $endpoint The endpoint of the notification subscriber
     *
     * @return static The current object
     */
    public function setEndpoint(string $endpoint): static
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    /**
     * Get the public key of the notification subscriber
     *
     * @return string|null The public key of the notification subscriber or null if not found
     */
    public function getPublicKey(): ?string
    {
        return $this->publicKey;
    }

    /**
     * Set the public key of the notification subscriber
     *
     * @param string $publicKey The public key of the notification subscriber
     */
    public function setPublicKey(string $publicKey): static
    {
        $this->publicKey = $publicKey;

        return $this;
    }

    /**
     * Get the auth token of the notification subscriber
     *
     * @return string|null The auth token of the notification subscriber or null if not found
     */
    public function getAuthToken(): ?string
    {
        return $this->authToken;
    }

    /**
     * Set the auth token of the notification subscriber
     *
     * @param string $authToken The auth token of the notification subscriber
     *
     * @return static The current object
     */
    public function setAuthToken(string $authToken): static
    {
        $this->authToken = $authToken;

        return $this;
    }

    /**
     * Get the subscribed time of the notification subscriber
     *
     * @return DateTimeInterface|null The subscribed time of the notification subscriber or null if not found
     */
    public function getSubscribedTime(): ?DateTimeInterface
    {
        return $this->subscribed_time;
    }

    /**
     * Set the subscribed time of the notification subscriber
     *
     * @param DateTimeInterface|null $subscribed_time The subscribed time of the notification subscriber
     *
     * @return static The current object
     */
    public function setSubscribedTime(?DateTimeInterface $subscribed_time): static
    {
        $this->subscribed_time = $subscribed_time;

        return $this;
    }

    /**
     * Get the status of the notification subscriber
     *
     * @return string|null The status of the notification subscriber or null if not found
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Set the status of the notification subscriber
     *
     * @param string $status The status of the notification subscriber
     *
     * @return static The current object
     */
    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get the user id of the notification subscriber
     *
     * @return int|null The user id of the notification subscriber or null if not found
     */
    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    /**
     * Set the user id of the notification subscriber
     *
     * @param int $user_id The user id of the notification subscriber
     *
     * @return static The current object
     */
    public function setUserId(int $user_id): static
    {
        $this->user_id = $user_id;

        return $this;
    }
}
