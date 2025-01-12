<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\MonitoringStatusRepository;

/**
 * Class MonitoringStatus
 *
 * The MonitoringStatus entity database table mapping class
 *
 * @package App\Entity
 */
#[ORM\Table(name: 'monitoring_status')]
#[ORM\Index(name: 'monitoring_status_status_idx', columns: ['status'])]
#[ORM\Index(name: 'monitoring_status_service_name_idx', columns: ['service_name'])]
#[ORM\Entity(repositoryClass: MonitoringStatusRepository::class)]
class MonitoringStatus
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $service_name = null;

    #[ORM\Column(length: 255)]
    private ?string $message = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(length: 255)]
    private ?int $down_time = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $last_update_time = null;

    /**
     * Get the id of the service monitoring
     *
     * @return int|null The id of the service monitoring or null if not found
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the name of the service
     *
     * @return string|null The name of the service or null if not found
     */
    public function getServiceName(): ?string
    {
        return $this->service_name;
    }

    /**
     * Set the name of the service
     *
     * @param string $service_name The name of the service
     *
     * @return static The current object
     */
    public function setServiceName(string $service_name): static
    {
        $this->service_name = $service_name;

        return $this;
    }

    /**
     * Get the message of the service monitoring
     *
     * @return string|null The message of the service monitoring or null if not found
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Set the message of the service monitoring
     *
     * @param string $message The message of the service monitoring
     *
     * @return static The current object
     */
    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get the status of the service monitoring
     *
     * @return string|null The status of the service monitoring or null if not found
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Set the status of the service monitoring
     *
     * @param string $status The status of the service monitoring
     *
     * @return static The current object
     */
    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get the down time of the service
     *
     * @return int|null The down time of the service or null if not found
     */
    public function getDownTime(): ?int
    {
        return $this->down_time;
    }

    /**
     * Set the down time of the service
     *
     * @param int $down_time The down time of the service
     *
     * @return static The current object
     */
    public function setDownTime(int $down_time): static
    {
        $this->down_time = $down_time;

        return $this;
    }

    /**
     * Increase the down time of the service
     *
     * @param int $minutes The number of minutes to increase the down time
     *
     * @return void
     */
    public function increaseDownTime(int $minutes): void
    {
        $this->down_time += $minutes;
    }

    /**
     * Get the last update time of the service monitoring
     *
     * @return DateTimeInterface|null The last update time of the service monitoring or null if not found
     */
    public function getLastUpdateTime(): ?DateTimeInterface
    {
        return $this->last_update_time;
    }

    /**
     * Set the last update time of the service monitoring
     *
     * @param DateTimeInterface $last_update_time The last update time of the service monitoring
     *
     * @return static The current object
     */
    public function setLastUpdateTime(DateTimeInterface $last_update_time): static
    {
        $this->last_update_time = $last_update_time;

        return $this;
    }
}
