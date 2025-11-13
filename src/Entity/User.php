<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class User
 *
 * Entity object for mapping database table
 *
 * @package App\Entity
 */
#[ORM\Table(name: 'users')]
#[ORM\Index(name: 'users_role_idx', columns: ['role'])]
#[ORM\Index(name: 'users_token_idx', columns: ['token'])]
#[ORM\Index(name: 'users_username_idx', columns: ['username'])]
#[ORM\Index(name: 'users_ip_address_idx', columns: ['ip_address'])]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
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
    private ?DateTimeInterface $register_time = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $last_login_time = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $token = null;

    #[ORM\Column(type: 'boolean')]
    private bool $allow_api_access = false;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $profile_pic = null;

    /**
     * @var Collection<int, Todo>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Todo::class)]
    private Collection $todos;

    /**
     * @var Collection<int, Log>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Log::class)]
    private Collection $logs;

    /**
     * @var Collection<int, ApiAccessLog>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ApiAccessLog::class)]
    private Collection $apiAccessLogs;

    /**
     * @var Collection<int, NotificationSubscriber>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: NotificationSubscriber::class)]
    private Collection $notificationSubscribers;

    /**
     * @var Collection<int, SentNotificationLog>
     */
    #[ORM\OneToMany(mappedBy: 'receiver', targetEntity: SentNotificationLog::class)]
    private Collection $receivedNotifications;

    /**
     * @var Collection<int, Banned>
     */
    #[ORM\OneToMany(mappedBy: 'bannedUser', targetEntity: Banned::class)]
    private Collection $bans;

    /**
     * @var Collection<int, Banned>
     */
    #[ORM\OneToMany(mappedBy: 'bannedBy', targetEntity: Banned::class)]
    private Collection $issuedBans;

    public function __construct()
    {
        $this->bans = new ArrayCollection();
        $this->logs = new ArrayCollection();
        $this->todos = new ArrayCollection();
        $this->issuedBans = new ArrayCollection();
        $this->apiAccessLogs = new ArrayCollection();
        $this->receivedNotifications = new ArrayCollection();
        $this->notificationSubscribers = new ArrayCollection();
    }

    /**
     * Get database ID of the user
     *
     * @return int|null The database ID of the user or null if not found
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get username of the user
     *
     * @return string|null The username of the user or null if not found
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * Set username of the user
     *
     * @param string $username The username of the user
     *
     * @return static The user object
     */
    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get password of the user
     *
     * @return string|null The password of the user or null if not found
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Set password of the user
     *
     * @param string $password The password of the user
     *
     * @return static The user object
     */
    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get role of the user
     *
     * @return string|null The role of the user or null if not found
     */
    public function getRole(): ?string
    {
        return $this->role;
    }

    /**
     * Set role of the user
     *
     * @param string $role The role of the user
     *
     * @return static The user object
     */
    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get ip address of the user
     *
     * @return string|null The ip address of the user or null if not found
     */
    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }

    /**
     * Set ip address of the user
     *
     * @param string $ip_address The ip address of the user
     *
     * @return static The user object
     */
    public function setIpAddress(string $ip_address): static
    {
        $this->ip_address = $ip_address;

        return $this;
    }

    /**
     * Get user agent of the user
     *
     * @return string|null The user agent of the user or null if not found
     */
    public function getUserAgent(): ?string
    {
        return $this->user_agent;
    }

    /**
     * Set user agent of the user
     *
     * @param string $user_agent The user agent of the user
     *
     * @return static The user object
     */
    public function setUserAgent(string $user_agent): static
    {
        // prevent maximal user agent length
        if (strlen($user_agent) > 255) {
            $user_agent = substr($user_agent, 0, 250) . "...";
        }

        $this->user_agent = $user_agent;

        return $this;
    }

    /**
     * Get register time of the user
     *
     * @return DateTimeInterface|null The register time of the user or null if not found
     */
    public function getRegisterTime(): ?DateTimeInterface
    {
        return $this->register_time;
    }

    /**
     * Set register time of the user
     *
     * @param DateTimeInterface $register_time The register time of the user
     *
     * @return static The user object
     */
    public function setRegisterTime(DateTimeInterface $register_time): static
    {
        $this->register_time = $register_time;

        return $this;
    }

    /**
     * Get last login time of the user
     *
     * @return DateTimeInterface|null The last login time of the user or null if not found
     */
    public function getLastLoginTime(): ?DateTimeInterface
    {
        return $this->last_login_time;
    }

    /**
     * Set last login time of the user
     *
     * @param DateTimeInterface $last_login_time The last login time of the user
     *
     * @return static The user object
     */
    public function setLastLoginTime(DateTimeInterface $last_login_time): static
    {
        $this->last_login_time = $last_login_time;

        return $this;
    }

    /**
     * Get token of the user (security user idenfier)
     *
     * @return string|null The token of the user or null if not found
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * Set token of the user (security user idenfier)
     *
     * @param string $token The token of the user
     *
     * @return static The user object
     */
    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get api access status
     *
     * @return bool The api access status
     */
    public function getAllowApiAccess(): bool
    {
        return $this->allow_api_access;
    }

    /**
     * Set api access status
     *
     * @param bool $allow_api_access The api access status
     *
     * @return static The user object
     */
    public function setAllowApiAccess(bool $allow_api_access): self
    {
        $this->allow_api_access = $allow_api_access;

        return $this;
    }

    /**
     * Get user profile picture in base64 format
     *
     * @return string|null The user profile picture in base64 format or null if not found
     */
    public function getProfilePic(): ?string
    {
        return $this->profile_pic;
    }

    /**
     * Set user profile picture in base64 format
     *
     * @param string $profile_pic The user profile picture in base64 format
     *
     * @return static The user object
     */
    public function setProfilePic(string $profile_pic): static
    {
        $this->profile_pic = $profile_pic;

        return $this;
    }

    /**
     * Get todos owned by the user
     *
     * @return Collection<int, Todo> The todos owned by the user
     */
    public function getTodos(): Collection
    {
        return $this->todos;
    }

    /**
     * Get logs owned by the user
     *
     * @return Collection<int, Log> The logs owned by the user
     */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    /**
     * Get api access logs associated with the user
     *
     * @return Collection<int, ApiAccessLog> The api access logs associated with the user
     */
    public function getApiAccessLogs(): Collection
    {
        return $this->apiAccessLogs;
    }

    /**
     * Get notification subscribers associated with the user
     *
     * @return Collection<int, NotificationSubscriber> The notification subscribers associated with the user
     */
    public function getNotificationSubscribers(): Collection
    {
        return $this->notificationSubscribers;
    }

    /**
     * Get sent notification logs associated with the user
     *
     * @return Collection<int, SentNotificationLog> The sent notification logs associated with the user
     */
    public function getReceivedNotifications(): Collection
    {
        return $this->receivedNotifications;
    }

    /**
     * Get bans associated with the user (this user banned status)
     *
     * @return Collection<int, Banned> The bans associated with the user
     */
    public function getBans(): Collection
    {
        return $this->bans;
    }

    /**
     * Get bans issued by the user
     *
     * @return Collection<int, Banned> The bans issued by the user
     */
    public function getIssuedBans(): Collection
    {
        return $this->issuedBans;
    }
}
