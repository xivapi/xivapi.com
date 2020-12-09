<?php

namespace App\Common\User;

use App\Common\Constants\PatreonConstants;
use App\Common\Entity\User;
use App\Common\Entity\UserAlert;
use App\Common\Entity\UserSession;
use App\Common\Repository\UserRepository;
use App\Common\ServicesThirdParty\Discord\Discord;
use App\Common\Exceptions\ApiUnknownPrivateKeyException;
use App\Service\API\ApiPermissions;
use Delight\Cookie\Cookie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use XIVAPI\XIVAPI;

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
                throw new NotFoundHttpException('You must be online to view this page.');
            }
            
            return null;
        }
    
        /** @var UserSession $session */
        $session = $this->em->getRepository(UserSession::class)->findOneBy([
            'session' => $session,
        ]);
        
        $user = $session ? $session->getUser() : null;
        
        if ($mustBeOnline && !$user) {
            throw new NotFoundHttpException('You must be online to view this page.');
        }
        
        if ($session) {
            // update the "last active" time if it's been an hour.
            $timeout = time() - (60 * 60);
            if ($session->getLastActive() < $timeout) {
                $session->setLastActive(time());
                $this->save($user, $session);
            }
        }
        
        return $user;
    }
    
    public function hasPermission($code): bool
    {
        ApiPermissions::set($this->getUser()->getPermissions());
        return ApiPermissions::has($code);
    }
    
    public function hasAccess(): bool
    {
        return $this->hasPermission(ApiPermissions::PERMISSION_ACCESS);
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
        [$user, $session] = $this->handleUser($sso, $user);
        
        // set cookie
        $cookie = new Cookie(self::COOKIE_SESSION_NAME);
        $cookie->setValue($session->getSession())->setMaxAge(self::COOKIE_SESSION_DURATION)->setPath('/')->save();
        
        return $user;
    }
    
    /**
     * Set user information
     */
    public function handleUser(\stdClass $sso, User $user = null): array
    {
        $user = $user ?: new User();
        $user
            ->setSso($sso->name)
            ->setUsername($sso->username)
            ->setEmail($sso->email);
    
        $session = new UserSession($user);
    
        // set discord info
        if ($sso->name === SignInDiscord::NAME) {
            $user
                ->setSsoDiscordId($sso->id)
                ->setSsoDiscordAvatar($sso->avatar)
                ->setSsoDiscordTokenAccess($sso->tokenAccess)
                ->setSsoDiscordTokenExpires($sso->tokenExpires)
                ->setSsoDiscordTokenRefresh($sso->tokenRefresh);
        }
        
        $this->save($user, $session);
        return [
            $user,
            $session,
        ];
    }
    
    /**
     * Update a user
     */
    public function save(User $user, UserSession $userSession = null): void
    {
        if ($userSession) {
            $this->em->persist($userSession);
        }
        
        $this->em->persist($user);
        $this->em->flush();
    }
    
    /**
     * Set the last url the user was on
     */
    public function setLastUrl(Request $request)
    {
        $request->getSession()->set('last_url', $request->getUri());
    }
    
    /**
     * Get the last url
     */
    public function getLastUrl(Request $request)
    {
        return $request->getSession()->get('last_url');
    }
    
    /**
     * @param User $user
     */
    public function checkPatreonTierForUser(User $user)
    {
        try {
            $response = Discord::mog()->getUserRole($user->getSsoDiscordId());
        } catch (\Exception $ex) {
            return;
        }
        
        // don't do anything if the response was not a 200
        if ($response->code != 200) {
            return;
        }
        
        $tier = $response->data;
        $tier = $tier ?: $user->getPatron();
        
        // set patreon tier
        $user->setPatron($tier);
        
        if ($user->getPatron() != PatreonConstants::PATREON_BENEFIT) {
            $user->setPatronBenefitUser(null);
        }
        
        /**
         * Alerts!
         */
        
        // Get Alert Limits
        $benefits = PatreonConstants::ALERT_LIMITS[$tier];
        
        // update user
        $user
            ->setAlertsMax($benefits['MAX'])
            ->setAlertsExpiry($benefits['EXPIRY_TIMEOUT'])
            ->setAlertsUpdate($benefits['UPDATE_TIMEOUT']);
        
        $this->em->persist($user);
        $this->em->flush();
        
        /**
         * Remove any benefit handouts
         */
        $users = $this->getRepository()->findBy([ 'patronBenefitUser' => $user->getId() ]);
        
        /** @var User $user */
        foreach ($users as $user) {
            if ($user->isPatron(PatreonConstants::PATREON_BENEFIT)) {
                $this->removeBenefits($user);
                continue;
            }
        }
    }
    
    /**
     * Sets users last online time, within 1 hour
     */
    public function setLastOnline()
    {
        $user = $this->getUser(false);
    
        if ($user === null) {
            return;
        }
    
        // 1 hour timeout so we are not constantly updating this user
        $timeout = time() - (60 * 60);
        
        if ($user->getLastOnline() < $timeout) {
            $user->setLastOnline(time());
            $this->em->persist($user);
            $this->em->flush();
        }
    }

    /**
     * Extends the expiry time of the users alerts.
     */
    public function refreshUsersAlerts()
    {
        $user = $this->getUser(false);
        
        if ($user === null) {
            return;
        }
        
        // 1 hour timeout so we are not constantly updating this users alerts.
        $timeout = time() - (60 * 60);
        
        /** @var UserAlert $alert */
        foreach ($user->getAlerts() as $alert) {
            // ignore if expiry is above timeout
            if ($alert->getExpiry() > $timeout) {
                continue;
            }
            
            $alert->setExpiry(time() + $user->getAlertsExpiry());
            $this->em->persist($alert);
        }
        
        $this->em->flush();
    }
    
    /**
     * Get all patreons
     */
    public function getPatrons()
    {
        return [
            4 => $this->repository->findBy([ 'patron' => 4 ]),
            3 => $this->repository->findBy([ 'patron' => 3 ]),
            2 => $this->repository->findBy([ 'patron' => 2 ]),
            1 => $this->repository->findBy([ 'patron' => 1 ]),
            9 => $this->repository->findBy([ 'patron' => 9 ]),
        ];
    }
    
    /**
     * Get the number of benefit patrons this user has issued.
     */
    public function getBenefitCount(string $userId)
    {
        $members = $this->repository->findBy([ 'patronBenefitUser' => $userId ]);
        return count($members);
    }
    
    /**
     * Check the handouts!
     */
    public function checkBenefitHandouts()
    {
        $console = new ConsoleOutput();
        $console->writeln("Checking benefit handouts");
        
        // grab all users who have granted a benefit
        $sql = "SELECT DISTINCT(patron_benefit_user) FROM users WHERE patron = 9";
        $sql = $this->em->getConnection()->prepare($sql);
        $sql->execute();
        
        $results = $sql->fetchAll();
    
        /**
         * Grab all characters for those who have provided benefits
         */
        $userToCharacters = [];
        foreach ($results as $row) {
            $userId = $row['patron_benefit_user'];
            
            $sql = "SELECT lodestone_id FROM users_characters WHERE user_id = '{$userId}' AND main = 1 LIMIT 1";
            $sql = $this->em->getConnection()->prepare($sql);
            $sql->execute();
            
            $character = $sql->fetch();
            $userToCharacters[$userId] = empty($character) ? null : $character['lodestone_id'];
        }

        $alertDefaults = PatreonConstants::ALERT_DEFAULTS;
        
        // process user handsouts
        foreach ($userToCharacters as $userId => $lodestoneId) {
            /**
             * Grab friends
             */
            $friends = $lodestoneId ? (new XIVAPI())->queries([ 'data' => 'FR' ])->character->get($lodestoneId)->Friends : [];
            
            /**
             * If the lodestone id is null, we will remove benefit status from all users who
             * benefited from this character. We also do the same if this users friends
             * list is empty (either private or they deleted all their friends)
             */
            if ($lodestoneId === null || empty($friends)) {
                $console->writeln("Removing patron status for all members benefited from: {$userId}");
                
                $sql = "
                    UPDATE users
                    SET
                      patron = 0,
                      patron_benefit_user = NULL,
                      alerts_max = {$alertDefaults['MAX']},
                      alerts_expiry = {$alertDefaults['EXPIRY_TIMEOUT']},
                      alerts_update = {$alertDefaults['UPDATE_TIMEOUT']}
                    WHERE
                      patron_benefit_user = '{$userId}';
                ";
    
                $sql = $this->em->getConnection()->prepare($sql);
                $sql->execute();
                
                continue;
            }
    
            /**
             * build a mini friends list
             */
            $miniFriendsList = [];
            foreach ($friends as $friend) {
                $miniFriendsList[] = $friend->ID;
            }
    
            /**
             * Now we need to get all the characters this user has provided benefits to
             */
            $users = $this->getRepository()->findBy([ 'patronBenefitUser' => $userId ]);
            
            /** @var User $user */
            foreach ($users as $user) {
                $userLodestoneId = $user->getMainCharacter()->getLodestoneId();
                
                /**
                 * If this user is not a benefit user.... remove the benefit id and skip
                 */
                if (!$user->isPatron(PatreonConstants::PATREON_BENEFIT)) {
                    $user->setPatronBenefitUser(null);
                    $this->save($user);
                    continue;
                }
    
                /**
                 * User is not in the friends list of the benefit provider
                 */
                if (!in_array($userLodestoneId, $miniFriendsList)) {
                    $console->writeln("{$userId} is no longer friends with: {$user->getId()}");
                    $this->removeBenefits($user);
                    continue;
                }
            }
        }
        
        $console->writeln("Done");
    }
    
    /**
     * Remove benefits from a specific user
     */
    private function removeBenefits(User $user)
    {
        // Double check, we only want to modify benefit users.
        if (!$user->isPatron(PatreonConstants::PATREON_BENEFIT)) {
            return;
        }
        
        $benefits = PatreonConstants::ALERT_DEFAULTS;

        $user
            ->setPatron(0)
            ->setPatronBenefitUser(null)
            ->setAlertsMax($benefits['MAX'])
            ->setAlertsExpiry($benefits['EXPIRY_TIMEOUT'])
            ->setAlertsUpdate($benefits['UPDATE_TIMEOUT']);
        
        $this->save($user);
    }
}
