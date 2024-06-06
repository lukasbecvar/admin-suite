<?php

namespace App\Manager;

use App\Entity\User;
use App\Util\SecurityUtil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\ByteString;
use Symfony\Component\HttpFoundation\Response;

class UserManager
{
    private LogManager $logManager;
    private ErrorManager $errorManager;
    private SecurityUtil $securityUtil;
    private EntityManagerInterface $entityManager;

    public function __construct(LogManager $logManager, ErrorManager $errorManager, SecurityUtil $securityUtil, EntityManagerInterface $entityManager)
    {
        $this->logManager = $logManager;
        $this->errorManager = $errorManager;
        $this->securityUtil = $securityUtil;
        $this->entityManager = $entityManager;
    }

    /**
     * Retrieve a user from the repository based on search criteria.
     *
     * @param array<mixed> $search The search criteria.
     *
     * @return User|null The user object if found, null otherwise.
     */
    public function getUserRepo(array $search): ?User
    {
        // get user repo
        return $this->entityManager->getRepository(User::class)->findOneBy($search);
    }

    /**
     * Register a new user.
     *
     * @param string $username The username of the new user.
     * @param string $password The password of the new user.
     *
     * @return void
     */
    public function registerUser(string $username, string $password): void
    {
        // generate entity token
        $token = ByteString::fromRandom(32)->toString();

        // check if token not exist
        if ($this->getUserRepo(['token' => $token]) != null) {
            $this->registerUser($username, $password);
        }

        // hash password
        $password = $this->securityUtil->generateHash($password);

        // get current time
        $time = new \DateTime();

        // check if user exist
        if ($this->getUserRepo(['username' => $username]) == null) {
            try {
                // init user entity
                $user = new User();

                $user->setUsername($username)
                    ->setPassword($password)
                    ->setRoles(['ROLE_USER'])
                    ->setIpAddress('127.0.0.1')
                    ->setToken(md5(random_bytes(32)))
                    ->setProfilePic('default_pic')
                    ->setRegisterTime($time)
                    ->setLastLoginTime($time);

                // flush user to database
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                // log action
                $this->logManager->log('authenticator', 'new registration user: ' . $username);
            } catch (\Exception $e) {
                $this->errorManager->handleError('error to register new user: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }
}
