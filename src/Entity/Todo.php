<?php

namespace App\Entity;

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
    private ?\DateTimeInterface $added_time = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $completed_time = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column]
    private ?int $user_id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTodoText(): ?string
    {
        return $this->todo_text;
    }

    public function setTodoText(string $todo_text): static
    {
        $this->todo_text = $todo_text;

        return $this;
    }

    public function getAddedTime(): ?\DateTimeInterface
    {
        return $this->added_time;
    }

    public function setAddedTime(\DateTimeInterface $added_time): static
    {
        $this->added_time = $added_time;

        return $this;
    }

    public function getCompletedTime(): ?\DateTimeInterface
    {
        return $this->completed_time;
    }

    public function setCompletedTime(?\DateTimeInterface $completed_time): static
    {
        $this->completed_time = $completed_time;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): static
    {
        $this->user_id = $user_id;

        return $this;
    }
}
