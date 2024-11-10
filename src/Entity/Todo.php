<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\TodoRepository;

/**
 * Class Todo
 *
 * The Todo entity database table mapping class
 *
 * @package App\Entity
 */
#[ORM\Table(name: 'todos')]
#[ORM\Index(name: 'todos_status_idx', columns: ['status'])]
#[ORM\Index(name: 'todos_user_id_idx', columns: ['user_id'])]
#[ORM\Entity(repositoryClass: TodoRepository::class)]
class Todo
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $todo_text = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $added_time = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $completed_time = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column]
    private ?int $user_id = null;

    /**
     * Get the id of the todo
     *
     * @return int|null The id of the todo or null if not found
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the text of the todo
     *
     * @return string|null The text of the todo or null if not found
     */
    public function getTodoText(): ?string
    {
        return $this->todo_text;
    }

    /**
     * Set the text of the todo
     *
     * @param string $todo_text The text of the todo
     *
     * @return static The current object
     */
    public function setTodoText(string $todo_text): static
    {
        $this->todo_text = $todo_text;

        return $this;
    }

    /**
     * Get the added time of the todo
     *
     * @return DateTimeInterface|null The added time of the todo or null if not found
     */
    public function getAddedTime(): ?DateTimeInterface
    {
        return $this->added_time;
    }

    /**
     * Set the added time of the todo
     *
     * @param DateTimeInterface $added_time The added time of the todo
     *
     * @return static The current object
     */
    public function setAddedTime(DateTimeInterface $added_time): static
    {
        $this->added_time = $added_time;

        return $this;
    }

    /**
     * Get the completed time of the todo
     *
     * @return DateTimeInterface|null The completed time of the todo or null if not found
     */
    public function getCompletedTime(): ?DateTimeInterface
    {
        return $this->completed_time;
    }

    /**
     * Set the completed time of the todo
     *
     * @param DateTimeInterface|null $completed_time The completed time of the todo
     *
     * @return static The current object
     */
    public function setCompletedTime(?DateTimeInterface $completed_time): static
    {
        $this->completed_time = $completed_time;

        return $this;
    }

    /**
     * Get the status of the todo
     *
     * @return string|null The status of the todo or null if not found
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Set the status of the todo
     *
     * @param string $status The status of the todo
     *
     * @return static The current object
     */
    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get the id of the user who created the todo
     *
     * @return int|null The id of the user who created the todo or null if not found
     */
    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    /**
     * Set the id of the user who created the todo
     *
     * @param int $user_id The id of the user who created the todo
     *
     * @return static The current object
     */
    public function setUserId(int $user_id): static
    {
        $this->user_id = $user_id;

        return $this;
    }
}
