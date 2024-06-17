<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;

/**
 * Class User
 *
 * The User entity database table mapping class
 *
 * @package App\Entity
 */
#[ORM\Table(name: 'users')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $role = null;

    #[ORM\Column(length: 255)]
    private ?string $ip_address = null;

    #[ORM\Column(length: 255)]
    private ?string $user_agent = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $register_time = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $last_login_time = null;

    #[ORM\Column(length: 255)]
    private ?string $token = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $profile_pic = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }

    public function setIpAddress(string $ip_address): static
    {
        $this->ip_address = $ip_address;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->user_agent;
    }

    public function setUserAgent(string $user_agent): static
    {
        // prevent maximal user agent length
        if (strlen($user_agent) > 255) {
            $user_agent = substr($user_agent, 0, 250) . "...";
        }

        $this->user_agent = $user_agent;

        return $this;
    }

    public function getRegisterTime(): ?\DateTimeInterface
    {
        return $this->register_time;
    }

    public function setRegisterTime(\DateTimeInterface $register_time): static
    {
        $this->register_time = $register_time;

        return $this;
    }

    public function getLastLoginTime(): ?\DateTimeInterface
    {
        return $this->last_login_time;
    }

    public function setLastLoginTime(\DateTimeInterface $last_login_time): static
    {
        $this->last_login_time = $last_login_time;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getProfilePic(): ?string
    {
        return $this->profile_pic;
    }

    public function setProfilePic(string $profile_pic): static
    {
        $this->profile_pic = $profile_pic;

        return $this;
    }
}
