<?php

namespace App\Service\User;

use App\Entity\User;
use App\Exception\AccountNotLoggedInException;
use App\Exception\ApiUnauthorizedAccessException;
use App\Repository\UserRepository;
use App\Service\User\SSO\CsrfInvalidException;
use App\Service\User\SSO\DiscordSignIn;
use App\Service\User\SSO\SignInInterface;
use App\Service\User\SSO\SSOAccess;
use Delight\Cookie\Cookie;
use Doctrine\ORM\EntityManagerInterface;

class Users
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var SignInInterface */
    private $provider;
    /** @var  UserRepository */
    private $repository;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->repository = $em->getRepository(User::class);
    }
    
    /**
     * Return all users
     */
    public function getUsers()
    {
        return $this->repository->findAll();
    }
    
    /**
     * Alias for twig
     */
    public function user()
    {
        return $this->getCurrentUser();
    }
    
    /**
     * Get the current logged in user
     */
    public function getCurrentUser(bool $enforce = false): ?User
    {
        $session = Cookie::get('session');
        
        if (!$session || $session === 'x') {
            if ($enforce) {
                throw new AccountNotLoggedInException();
            }
            
            return null;
        }
        
        $repo = $this->em->getRepository(User::class);
        
        /** @var User $user */
        $user = $repo->findOneBy([
            'session' => $session
        ]);
        
        if ($user == null && $enforce) {
            throw new AccountNotLoggedInException();
        }
        
        return $user;
    }

    /**
     * Obtain a user account via their API key.
     */
    public function getUserByApiKey(string $key)
    {
        $user = $this->repository->findOneBy([ 'apiPublicKey' => $key ]);

        if (empty($user)) {
            throw new ApiUnauthorizedAccessException();
        }

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
            ->setSsoId($ssoAccess->id)
            ->setToken(json_encode($ssoAccess))
            ->setUsername($ssoAccess->username)
            ->setEmail($ssoAccess->email);
        
        // save user
        $this->save($user);
        return $user;
    }
    
    /**
     * Save a user
     */
    public function save(User $user)
    {
        $this->em->persist($user);
        $this->em->flush();
    }
    
    /**
     * Sign in
     */
    public function login()
    {
        return $this->provider->getLoginAuthorizationUrl()->getUrl();
    }
    
    /**
     * Set the single sign in provider
     */
    public function setLoginProvider(SignInInterface $provider)
    {
        $this->provider = $provider;
        return $this;
    }
    
    /**
     * Logout a user
     */
    public function logout()
    {
        $this->deleteCookie();
    }
    
    /**
     * Authenticate
     * @throws CsrfInvalidException
     */
    public function authenticate(): User
    {
        // todo - debug this, sometimes CSRF fails, maybe implement Symfony CSRF.
        // todo - migrate mogboard authenticate over, it has been fixed there.
        /** @var DiscordSignIn $sso */
        if (!$this->provider->isCsrfValid()) {
            //throw new CsrfInvalidException();
        }
        
        $ssoAccess = $this->provider->setLoginAuthorizationState();
        
        // get user or create a new one
        $user = $this->repository->findOneBy([ 'ssoId' => $ssoAccess->id ])
            ?: $this->create($this->provider->getName(), $ssoAccess);
        
        // update user
        $user
            ->setSso($this->provider->getName())
            ->setSsoId($ssoAccess->id)
            ->setToken(json_encode($ssoAccess))
            ->setUsername($ssoAccess->username)
            ->setEmail($ssoAccess->email);
        
        $this->save($user);
        $this->setCookie($user->getSession());
        return $user;
    }
    
    /**
     * Set a cookie
     */
    public function setCookie($sid)
    {
        $cookie = new Cookie('session');
        $cookie->setValue($sid)->setMaxAge(60 * 60 * 24 * 30)->setDomain(getenv('COOKIE_DOMAIN'))->save();
    }
    
    /**
     * Delete a cookie
     */
    public function deleteCookie()
    {
        //$request->get
        $cookie = new Cookie('session');
        $cookie->setValue('x')->setMaxAge(-1)->setDomain(getenv('COOKIE_DOMAIN'))->save();
        $cookie->delete();
    }
}
