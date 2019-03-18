<?php

namespace App\Service\User;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\User\Discord\CsrfInvalidException;
use App\Service\User\Discord\DiscordSignIn;
use App\Service\User\SSO\SSOAccess;
use Delight\Cookie\Cookie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Users
{
    const COOKIE_SESSION_NAME     = 'session';
    const COOKIE_SESSION_DURATION = (60 * 60 * 24 * 30);
    
    /** @var EntityManagerInterface */
    private $em;
    /** @var UserRepository */
    private $repository;
    /** @var DiscordSignIn */
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
        $user = $this->em->getRepository(User::class)->findOneBy([
            'session' => $session
        ]);
    
        if ($mustBeOnline && !$user) {
            throw new NotFoundHttpException();
        }
        
        return $user;
    }

    /**
     * Get all users
     * @return User[]
     */
    public function findAll(): array
    {
        return $this->repository->findAll();
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
     * @throws CsrfInvalidException
     */
    public function authenticate(): User
    {
        // look for their user if they already have an account
        $ssoAccess = $this->sso->setLoginAuthorizationState();
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $ssoAccess->email
        ]);
        
        // if they don't have an account, create one!
        if (!$user) {
            $user = $this->create($this->sso::NAME, $ssoAccess);
            // todo - send email?
        }
    
        $cookie = new Cookie(self::COOKIE_SESSION_NAME);
        $cookie->setValue($user->getSession())->setMaxAge(self::COOKIE_SESSION_DURATION)->setPath('/')->save();
        
        return $user;
    }

    /**
     * Create a new user
     */
    public function create(string $sso, SSOAccess $ssoAccess): User
    {
        $user = new User();
        $user
            ->setSso($sso)
            ->setToken(json_encode($ssoAccess))
            ->setUsername($ssoAccess->username)
            ->setEmail($ssoAccess->email)
            ->setAvatar($ssoAccess->avatar ?: 'http://xivapi.com/img-misc/chat_messengericon_goldsaucer.png');
        
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
     * Is the current user online?
     */
    public function isOnline()
    {
        return !empty($this->getUser());
    }
}
