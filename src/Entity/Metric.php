<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\MetricRepository;

/**
 * Class Metric
 *
 * The Metric entity database table mapping class
 *
 * @package App\Entity
 */
#[ORM\Table(name: 'metrics')]
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

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $time = null;

    /**
     * Get the id of the metric
     *
     * @return int|null The id of the metric or null if not found
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the name of the metric
     *
     * @return string|null The name of the metric or null if not found
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the name of the metric
     *
     * @param string $name The name of the metric
     *
     * @return static The current object
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of the metric
     *
     * @return string|null The value of the metric or null if not found
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Set the value of the metric
     *
     * @param string $value The value of the metric
     *
     * @return static The current object
     */
    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get the time of the metric
     *
     * @return \DateTimeInterface|null The time of the metric or null if not found
     */
    public function getTime(): ?\DateTimeInterface
    {
        return $this->time;
    }

    /**
     * Set the time of the metric
     *
     * @param \DateTimeInterface $time The time of the metric
     *
     * @return static The current object
     */
    public function setTime(\DateTimeInterface $time): static
    {
        $this->time = $time;

        return $this;
    }
}
