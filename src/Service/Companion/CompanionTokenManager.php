<?php

namespace App\Service\Companion;

use App\Entity\CompanionToken;
use App\Repository\CompanionTokenRepository;
use App\Service\Content\GameServers;
use App\Service\Redis\Redis;
use App\Service\ThirdParty\GoogleAnalytics;
use Companion\CompanionApi;
use Companion\Http\Cookies;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class CompanionTokenManager
{
    /**
     * Current servers that are offline due to character restrictions
     */
    const SERVERS_OFFLINE = [
        1,2,3,4,5,6,9,12,14,17,22,23,26,27,29,30,32,38,39,45,48,49,51,54,55,56,57,58,60,61,62,64,
    ];
    
    /**
     * Current servers and their associated login account
     */
    const SERVERS_ACCOUNTS = [
        /* - NO JP ATM
        'Aegis'         => 'COMPANION_APP_ACCOUNT_B',
        'Atomos'        => 'COMPANION_APP_ACCOUNT_B',
        'Carbuncle'     => 'COMPANION_APP_ACCOUNT_B',
        'Garuda'        => 'COMPANION_APP_ACCOUNT_B',
        'Gungnir'       => 'COMPANION_APP_ACCOUNT_B',
        'Kujata'        => 'COMPANION_APP_ACCOUNT_B',
        'Ramuh'         => 'COMPANION_APP_ACCOUNT_B',
        'Tonberry'      => 'COMPANION_APP_ACCOUNT_B',
        'Typhon'        => 'COMPANION_APP_ACCOUNT_B',
        'Unicorn'       => 'COMPANION_APP_ACCOUNT_B',
        'Alexander'     => 'COMPANION_APP_ACCOUNT_B',
        'Bahamut'       => 'COMPANION_APP_ACCOUNT_B',
        'Durandal'      => 'COMPANION_APP_ACCOUNT_B',
        'Fenrir'        => 'COMPANION_APP_ACCOUNT_B',
        'Ifrit'         => 'COMPANION_APP_ACCOUNT_B',
        'Ridill'        => 'COMPANION_APP_ACCOUNT_B',
        'Tiamat'        => 'COMPANION_APP_ACCOUNT_B',
        'Ultima'        => 'COMPANION_APP_ACCOUNT_B',
        'Valefor'       => 'COMPANION_APP_ACCOUNT_B',
        'Yojimbo'       => 'COMPANION_APP_ACCOUNT_B',
        'Zeromus'       => 'COMPANION_APP_ACCOUNT_B',
        'Anima'         => 'COMPANION_APP_ACCOUNT_B',
        'Asura'         => 'COMPANION_APP_ACCOUNT_B',
        'Belias'        => 'COMPANION_APP_ACCOUNT_B',
        'Chocobo'       => 'COMPANION_APP_ACCOUNT_B',
        'Hades'         => 'COMPANION_APP_ACCOUNT_B',
        'Ixion'         => 'COMPANION_APP_ACCOUNT_B',
        'Masamune'      => 'COMPANION_APP_ACCOUNT_B',
        'Pandaemonium'  => 'COMPANION_APP_ACCOUNT_B',
        'Shinryu'       => 'COMPANION_APP_ACCOUNT_B',
        'Titan'         => 'COMPANION_APP_ACCOUNT_B',
        'Mandragora'    => 'COMPANION_APP_ACCOUNT_B',
        */

        // US Servers
        'Balmung'       => 'MB1',
        'Adamantoise'   => 'MB1',
        'Cactuar'       => 'MB1',
        'Coeurl'        => 'MB2',
        'Faerie'        => 'MB1',
        'Gilgamesh'     => 'MB1',
        'Goblin'        => 'MB2',
        'Jenova'        => 'MB1',
        'Mateus'        => '',      # congested
        'Midgardsormr'  => 'MB1',
        'Sargatanas'    => 'MB1',
        'Siren'         => 'MB1',
        'Zalera'        => 'MB2',
        'Behemoth'      => 'MB1',
        'Brynhildr'     => 'MB2',
        'Diabolos'      => 'MB2',
        'Excalibur'     => 'MB1',
        'Exodus'        => 'MB1',
        'Famfrit'       => 'MB1',
        'Hyperion'      => 'MB1',
        'Lamia'         => 'MB1',
        'Leviathan'     => 'MB1',
        'Malboro'       => 'MB2',
        'Ultros'        => 'MB1',

        // EU Servers
        'Cerberus'      => '',
        'Lich'          => 'MB3',
        'Louisoix'      => 'MB3',
        'Moogle'        => '',
        'Odin'          => '',
        'Omega'         => 'MB3',
        'Phoenix'       => '',
        'Ragnarok'      => '',
        'Shiva'         => '',
        'Zodiark'       => 'MB3',
    ];

    /** @var EntityManagerInterface em */
    private $em;
    /** @var ConsoleOutput */
    private $console;
    /** @var CompanionTokenRepository */
    private $repository;
    /** @var CompanionErrorHandler */
    private $errorHandler;

    public function __construct(EntityManagerInterface $em, CompanionErrorHandler $errorHandler)
    {
        $this->em                    = $em;
        $this->repository            = $em->getRepository(CompanionToken::class);
        $this->errorHandler = $errorHandler;
        $this->console               = new ConsoleOutput();
    }

    /**
     * Logs into each account and records all character prep tokens
     */
    public function autoPopulateCharacters()
    {
        $accounts = ['MB1','MB2','MB3','MB4','MB5','MB6'];
        $repo     = $this->em->getRepository(CompanionToken::class);
    
        // clear cookies
        Cookies::clear(); sleep(1);
        
        // shuffle accounts
        shuffle($accounts);
    
        /**
         * Login to each account and record characters
         */
        $this->console->writeln("Logging into accounts");
        foreach ($accounts as $account) {
            [$username, $password] = explode(',', getenv($account));

            try {
                $this->console->writeln("- Account: {$account} {$username}");
                $api = new CompanionApi("{$account}_{$username}");
                $api->Account()->login($username, $password);
    
                // Get a list of characters
                echo "Getting a list of characters\n";
                foreach ($api->Login()->getCharacters()->accounts[0]->characters as $character) {
                    $this->console->writeln("Detected Character: {$character->name} {$character->world}");
                    
                    /** @var CompanionToken $token */
                    $token = $repo->findOneBy([ 'characterId' => $character->cid ]);
                    
                    // if a token exists for this character, skip
                    if ($token) {
                        continue;
                    }
                    
                    $token = new CompanionToken();
                    $token
                        ->setCharacterId($character->cid)
                        ->setServer($character->world)
                        ->setAccount($account);
                    
                    $this->em->persist($token);
                    $this->em->flush();
                }
            } catch (\Exception $ex) {
                $this->console->writeln('-- EXCEPTION --');
                $this->console->writeln($ex->getMessage());
                die;
            }
    
            // ensure cookie file is deleted
            Cookies::clear(); sleep(mt_rand(0,30));
        }
    }

    /**
     * Finds the next expiring account and logs into it.
     */
    public function autoLoginToExpiringAccount()
    {
        /** @var CompanionToken $token */
        $token = $this->repository->findExpiringAccount();

        if ($token && $token->getExpiring() < time()) {
            $this->login($token->getAccount(), $token->getServer());
        }
    }

    public function autoLoginToAllAccounts()
    {
        $tokens = $this->repository->findAll();

        /** @var CompanionToken $token */
        foreach ($tokens as $token) {
            // clear cookies
            Cookies::clear(); sleep(1);

            $this->login($token->getAccount(), $token->getServer());

            // clear cookies
            Cookies::clear();
            sleep(mt_rand(15,90));
        }
    }
    
    /**
     * Login to a specific server
     */
    public function login(string $account, string $server)
    {
        // check error count
        if ($this->errorHandler->getCriticalExceptionCount() >= CompanionConfiguration::ERROR_COUNT_THRESHOLD) {
            return false;
        }

        $failedRecently = Redis::Cache()->get("companion_server_login_issues_{$server}");
        if ($failedRecently) {
            return false;
        }

        $this->console->writeln("<comment>Login: {$account} - {$server}</comment>");
        
        // grab saved token in db
        /** @var CompanionToken $token */
        $token = $this->repository->findOneBy([
            'account' => $account,
            'server' => $server,
        ]);

        if ($token == null) {
            throw new \Exception("Token not found...");
        }
        
        // ensure its marked as offline
        $token->setOnline(false)->setMessage('Offline');
        $this->em->persist($token);
        $this->em->flush();
        
        // check if server is an "offline" server
        $serverId = GameServers::getServerId($server);
        if (in_array($serverId, self::SERVERS_OFFLINE)) {
            $this->console->writeln('No characters available on this server at this time.');
            return false;
        }
        
        [$username, $password] = explode(',', getenv($account));
        
        try {
            // initialize API and create a new token
            $api = new CompanionApi("{$account}_{$username}_{$server}");
            
            // login
            $this->console->writeln("- Account Login: {$account} {$username} {$server}");
            $api->Account()->login($username, $password);
            GoogleAnalytics::companionTrackItemAsUrl("/account/login");
            
            // find character for this server
            $this->console->writeln('- Finding active character ...');
            $cid = null;
            foreach ($api->Login()->getCharacters()->accounts[0]->characters as $character) {
                if ($character->world == $server) {
                    $cid = $character->cid;
                    break;
                }
            }
            GoogleAnalytics::companionTrackItemAsUrl("/account/get-characters");
            
            // couldn't find a valid character
            if ($cid === null) {
                $this->console->writeln('- Error: No character on this server...');
                return false;
            }
            
            // login with our chosen character!
            $this->console->writeln("- Logging into character: {$cid}");
            $api->Login()->loginCharacter($cid);
            GoogleAnalytics::companionTrackItemAsUrl("/account/login-character");
            
            // confirm
            $character = $api->login()->getCharacter()->character;
            $this->console->writeln("- Character logged into: {$character->name} ({$character->world})");
            GoogleAnalytics::companionTrackItemAsUrl("/account/login-character-confirm");
            
            // get character status
            $api->login()->getCharacterWorlds();
            $this->console->writeln('- Character world status confirmed');
            GoogleAnalytics::companionTrackItemAsUrl("/account/worlds");
            
            // wait a bit
            $this->console->writeln('- Testing market in a moment...');
            sleep(mt_rand(3,8));
            
            // perform a test
            $api->market()->getItemMarketListings(5);
            GoogleAnalytics::companionTrackItemAsUrl("/account/market-5");
            $this->console->writeln('- Market fetch confirmed.');
            
            // confirm success
            $token
                ->setMessage('Online')
                ->setOnline(true)
                ->setExpiring(time() + (60 * 60 * mt_rand(10, 15))) // expires in 10-15 hours
                ->setToken($api->Token()->get());
            
        } catch (\Exception $ex) {
            // prevent logging into same server if it fails for a random amount of time
            Redis::Cache()->set("companion_server_login_issues_{$server}", 1, mt_rand(2000, 6000));

            $token
                ->setMessage('Offline - Failed to login to Companion.')
                ->setExpiring(time() + (60 * 60 * mt_rand(2, 5))) // expires in 2 to 5 hours
                ->setOnline(false);
            
            $this->errorHandler->exception(
                "SE_Login_Failure",
                "Account: ({$account}) {$username} - Server: {$server} - Message: {$ex->getMessage()}"
            );
            
            $this->console->writeln('- Character failed to login: '. $ex->getMessage());
        }
        
        $this->em->persist($token);
        $this->em->flush();
        
        return true;
    }

    /**
     * @return CompanionToken[]
     */
    public function getCompanionTokens(): array
    {
        $tokens = $this->em->getRepository(CompanionToken::class)->findAll();

        // check they're all online, if any expired, ignore
        /** @var CompanionToken $token */
        foreach ($tokens as $token) {
            if ($token->getExpiring() < time()) {
                $token->setOnline(false);
                $this->em->persist($token);
            }
        }

        $this->em->flush();

        return $this->em->getRepository(CompanionToken::class)->findAll();
    }
    
    /**
     * @return CompanionToken[]
     */
    public function getOnlineServers(): array
    {
        $list = [];
    
        foreach ($this->getCompanionTokens() as $token) {
            if ($token->isOnline()) {
                $list[] = GameServers::getServerId($token->getServer());
            }
        }

        return $list;
    }
    
    /**
     * @param string $server
     * @return CompanionToken
     * @throws \Exception
     */
    public function getCompanionTokenForServer(string $server): CompanionToken
    {
        $tokens = $this->getCompanionTokens();
        shuffle($tokens);
        
        foreach ($tokens as $entity) {
            if ($entity->getServer() === $server) {
                return $entity;
            }
        }
        
        throw new \Exception('No token found for server: '. $server);
    }
}
