<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\MetricRepository;

/**
 * Class Metric
 *
 * Entity object for mapping database table
 *
 * @package App\Entity
 */
#[ORM\Table(name: 'metrics')]
#[ORM\Index(name: 'metrics_name_idx', columns: ['name'])]
#[ORM\Index(name: 'metrics_time_idx', columns: ['time'])]
#[ORM\Entity(repositoryClass: MetricRepository::class)]
class Metric
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $value = null;

    #[ORM\Column(length: 255)]
    private ?string $service_name = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $time = null;

    /**
     * Get database ID of the metric
     *
     * @return int|null The database ID of the metric or null if not found
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get name of the metric
     *
     * @return string|null The name of the metric or null if not found
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set name of the metric
     *
     * @param string $name The name of the metric
     *
     * @return static The metric object
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get value of the metric
     *
     * @return string|null The value of the metric or null if not found
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Set value of the metric
     *
     * @param string $value The value of the metric
     *
     * @return static The metric object
     */
    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get service name associated with the metric
     *
     * @return string|null The service name associated with the metric or null if not found
     */
    public function getServiceName(): ?string
    {
        return $this->service_name;
    }

    /**
     * Set service name associated with the metric
     *
     * @param string $service_name The service name associated with the metric
     *
     * @return static The metric object
     */
    public function setServiceName(string $service_name): static
    {
        $this->service_name = $service_name;

        return $this;
    }

    /**
     * Get time of the metric
     *
     * @return DateTimeInterface|null The time of the metric or null if not found
     */
    public function getTime(): ?DateTimeInterface
    {
        return $this->time;
    }

    /**
     * Set time of the metric
     *
     * @param DateTimeInterface $time The time of the metric
     *
     * @return static The metric object
     */
    public function setTime(DateTimeInterface $time): static
    {
        $this->time = $time;

        return $this;
    }
}
