<?php

namespace App\Service\User;

use App\Entity\User;
use App\Exception\ApiUnknownPrivateKeyException;
use App\Repository\UserRepository;
use App\Service\ThirdParty\Discord\Discord;
use Delight\Cookie\Cookie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Users
{
    const COOKIE_SESSION_NAME = 'session';
    const COOKIE_SESSION_DURATION = (60 * 60 * 24 * 30);

    /** @var EntityManagerInterface */
    private $em;
    /** @var UserRepository */
    private $repository;
    /** @var SignInInterface */
    private $sso;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em         = $em;
        $this->repository = $em->getRepository(User::class);
    }

    /**
     * Set the single sign in provider
     */
    public function setSsoProvider(SignInInterface $sso)
    {
        $this->sso = $sso;
        return $this;
    }

    /**
     * Get user repository
     */
    public function getRepository(): UserRepository
    {
        return $this->repository;
    }

    /**
     * Get the current logged in user
     */
    public function getUser($mustBeOnline = false): ?User
    {
        $session = Cookie::get(self::COOKIE_SESSION_NAME);
        if (!$session || $session === 'x') {
            if ($mustBeOnline) {
                throw new NotFoundHttpException();
            }

            return null;
        }

        /** @var User $user */
        $user = $this->repository->findOneBy([
            'session' => $session,
        ]);

        if ($mustBeOnline && !$user) {
            throw new NotFoundHttpException();
        }

        return $user;
    }

    /**
     * Get a user via their API Key
     */
    public function getUserByApiKey(string $key)
    {
        $user = $this->repository->findOneBy([
            'apiPublicKey' => $key,
        ]);

        if (empty($user)) {
            throw new ApiUnknownPrivateKeyException();
        }

        return $user;
    }

    /**
     * Is the current user online?
     */
    public function isOnline()
    {
        return !empty($this->getUser());
    }

    /**
     * Sign in
     */
    public function login(): string
    {
        return $this->sso->getLoginAuthorizationUrl();
    }

    /**
     * Logout a user
     */
    public function logout(): void
    {
        $cookie = new Cookie(self::COOKIE_SESSION_NAME);
        $cookie->setValue('x')->setMaxAge(-1)->setPath('/')->save();
        $cookie->delete();
    }

    /**
     * Authenticate
     */
    public function authenticate(): User
    {
        // look for their user if they already have an account
        $sso  = $this->sso->setLoginAuthorizationState();
        $user = $this->repository->findOneBy([
            'ssoDiscordId' => $sso->id,
        ]);

        // handle user info during login process
        $user = $this->handleUser($sso, $user);

        // set cookie
        $cookie = new Cookie(self::COOKIE_SESSION_NAME);
        $cookie->setValue($user->getSession())->setMaxAge(self::COOKIE_SESSION_DURATION)->setPath('/')->save();

        return $user;
    }

    /**
     * Set user information
     */
    public function handleUser(\stdClass $sso, User $user = null): User
    {
        $user = $user ?: new User();
        $user
            ->setSso($sso->name)
            ->setUsername($sso->username)
            ->setEmail($sso->email)
            ->generateSession();

        // set discord info
        if ($sso->name === SignInDiscord::NAME) {
            $user
                ->setSsoDiscordId($sso->id)
                ->setSsoDiscordAvatar($sso->avatar)
                ->setSsoDiscordTokenAccess($sso->tokenAccess)
                ->setSsoDiscordTokenExpires($sso->tokenExpires)
                ->setSsoDiscordTokenRefresh($sso->tokenRefresh);
        }

        $this->save($user);
        return $user;
    }

    /**
     * Update a user
     */
    public function save(User $user): void
    {
        $this->em->persist($user);
        $this->em->flush();
    }
    
    /**
     * Checks the patreon status of all users against the discord channel.
     */
    public function checkPatreonTiers()
    {
        $users   = $this->repository->findAll();
        $total   = count($users);
        $console = new ConsoleOutput();
        $start   = microtime(true);
        
        /** @var User $user */
        foreach ($users as $i => $user) {
            $i = ($i + 1);
            $console->writeln("({$i}/{$total}) Checking: {$user->getSsoDiscordId()} {$user->getUsername()}");
            $this->checkPatreonTierForUser($user);
            usleep(100000);
            
            if ($i % 50 == 0) {
                $this->em->flush();
            }
        }
    
        $this->em->flush();
        $this->em->clear();
        $duration = round(microtime(true) - $start, 2);
        $console->writeln("Finished, duration: {$duration}");
    }
    
    /**
     * @param User $user
     */
    public function checkPatreonTierForUser(User $user)
    {
        try {
            $response = Discord::mog()->getUserRole($user->getSsoDiscordId());
            $tier = $response->data;
        } catch (\Exception $ex) {
            return;
        }
    
        // don't do anything if the response was not a 200
        if ($response->code != 200) {
            return;
        }
    
        $user->setPatron($tier ?: 0);
        $this->em->persist($user);
    }
}
