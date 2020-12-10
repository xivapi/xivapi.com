<?php

namespace App\Service\Companion;

use App\Common\Entity\Maintenance;
use App\Common\Game\GameServers;
use App\Entity\CompanionToken;
use App\Repository\CompanionTokenRepository;
use App\Common\Service\Redis\Redis;
use Companion\CompanionApi;
use Companion\Config\CompanionSight;
use Companion\Http\Cookies;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

class CompanionTokenManager
{
    /**
     * Current servers that are offline due to character restrictions
     */
    const SERVERS_OFFLINE = [
        // JP Servers
        1,2,3,4,5,6,9,12,14,17,22,23,26,27,29,30,32,38,39,45,48,49,51,54,55,56,57,58,60,61,62,64
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
        'Balmung'       => '',
        'Adamantoise'   => '',
        'Cactuar'       => '',
        'Coeurl'        => '',
        'Faerie'        => '',
        'Gilgamesh'     => '',
        'Goblin'        => '',
        'Jenova'        => '',
        'Mateus'        => '',
        'Midgardsormr'  => '',
        'Sargatanas'    => '',
        'Siren'         => '',
        'Zalera'        => '',
        'Behemoth'      => '',
        'Brynhildr'     => '',
        'Diabolos'      => '',
        'Excalibur'     => '',
        'Exodus'        => '',
        'Famfrit'       => '',
        'Hyperion'      => '',
        'Lamia'         => '',
        'Leviathan'     => '',
        'Malboro'       => '',
        'Ultros'        => '',

        // EU Servers
        'Cerberus'      => '',
        'Lich'          => '',
        'Louisoix'      => '',
        'Moogle'        => '',
        'Odin'          => '',
        'Omega'         => '',
        'Phoenix'       => '',
        'Ragnarok'      => '',
        'Shiva'         => '',
        'Zodiark'       => '',
    ];

    /** @var EntityManagerInterface em */
    private $em;
    /** @var ConsoleOutput */
    private $console;
    /** @var CompanionTokenRepository */
    private $repository;
    /** @var CompanionErrorHandler */
    private $errorHandler;
    /** @var Maintenance */
    private $maintenance;

    public function __construct(EntityManagerInterface $em, CompanionErrorHandler $errorHandler)
    {
        $this->em                    = $em;
        $this->repository            = $em->getRepository(CompanionToken::class);
        $this->errorHandler          = $errorHandler;
        $this->console               = new ConsoleOutput();

        $this->maintenance = $this->em->getRepository(Maintenance::class)->findOneBy(['id' => 1 ]) ?: new Maintenance();
    
        // settings
        CompanionSight::set('CLIENT_TIMEOUT', 5);
        CompanionSight::set('QUERY_LOOP_COUNT', 5);
        CompanionSight::set('QUERY_DELAY_MS', 1000);
    }

    /**
     * Logs into each account and records all character prep tokens
     */
    public function autoPopulateCharacters(string $accounts = null)
    {
        $accounts = $accounts ? explode(',', $accounts) : range(1,50);
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
            $account = "MB" . $account;
            $creds   = getenv($account);

            if (empty($creds)) {
                continue;
            }

            [$username, $password] = explode(',', $creds);

            try {
                $this->console->writeln("- Account: {$account} {$username}");
                $api = new CompanionApi("{$account}_{$username}");
                $api->Account()->login($username, $password);
                
                $tabledata = [];
    
                // Get a list of characters
                echo "Getting a list of characters\n";
                foreach ($api->Login()->getCharacters()->accounts[0]->characters as $i => $character) {
                    $tabledata[] = [
                        ($i + 1), $character->cid, $character->name, $character->world
                    ];
                    
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
                
                $this->console->writeln("Total Servers: ". count($tabledata));
    
                // print table
                $table = new Table($this->console);
                $table
                    ->setHeaders(['#', 'ID', 'Name', 'Server'])
                    ->setRows($tabledata);
                
                $table->render();
                
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
        try {
            /** @var CompanionToken $token */
            $tokens = $this->repository->findExpiringAccounts();
            $token  = $tokens[array_rand($tokens)];

        } catch (\Exception $ex) {
            $this->console->writeln('<error>Error!!</error>');
            throw new $ex;
        }

        if ($token == null) {
            $this->console->writeln("Could not fetch token from db.");
            return;
        }

        $this->login($token->getAccount(), $token->getServer(), $token->getCharacterId());
    }

    public function autoLoginToAllAccounts()
    {
        $tokens = $this->repository->findAll();

        /** @var CompanionToken $token */
        foreach ($tokens as $token) {
            // clear cookies
            Cookies::clear(); sleep(1);

            $this->login($token->getAccount(), $token->getServer(), $token->getCharacterId());

            // clear cookies
            Cookies::clear();
            sleep(mt_rand(15,90));
        }
    }
    
    /**
     * Login to a specific server
     */
    public function login(string $account, string $server, string $characterId)
    {
        if ($this->maintenance->isCompanionMaintenance() || $this->maintenance->isGameMaintenance()) {
            //$this->console->writeln("Maintenance is active, stopping...");
            //return false;
        }

        // check error count
        if ($this->errorHandler->isCriticalExceptionCount()) {
            $this->console->writeln("Currently at critical error rate");
            return false;
        }

        // don't login to same account if it failed recently.
        if (Redis::Cache()->get("companion_server_login_issues2_{$account}_{$server}")) {
            $this->console->writeln("Recently tried: {$account} on {$server} and failed");
            return false;
        }

        $this->console->writeln("<comment>Login: {$account} - {$server}</comment>");
        
        // grab saved token in db
        /** @var CompanionToken $token */
        $token = $this->repository->findOneBy([
            'account' => $account,
            'server' => $server,
        ]);

        // token has not expired
        if ($token->hasExpired() == false) {
            $this->console->writeln("Token has not expired?");
            return false;
        }
        
        if (in_array($token->getId(), [179, 267])) {
            $this->console->writeln("Skipping these");
            return false;
        }
        
        // ensure its marked as offline
        $token->setOnline(false)->setMessage('Offline')->setToken(null)->setExpiring(0);
        $this->em->persist($token);
        $this->em->flush();
        
        // check if server is an "offline" server
        $serverId = GameServers::getServerId($server);
        if (in_array($serverId, self::SERVERS_OFFLINE)) {
            $this->console->writeln('No characters available on this server at this time.');
            return false;
        }

        $steps = [];
        [$username, $password] = explode(',', getenv($account));

        try {
            // settings
            CompanionSight::set('CLIENT_TIMEOUT', 3);
            CompanionSight::set('QUERY_LOOP_COUNT', 8);
            CompanionSight::set('QUERY_DELAY_MS', 1500);

            // initialize API and create a new token
            $api = new CompanionApi("{$account}_{$username}_{$server}");

            // track account logins
            Redis::Cache()->increment("companion_count_logins_{$account}");
            
            // login
            $this->console->writeln("- Account Login: {$account} {$username} {$server}");
            $api->Account()->login($username, $password);
            $steps[] = 'Logged-In';
            
            // login with our character!
            $this->console->writeln("- Logging into character: {$characterId}");
            $api->Login()->loginCharacter($characterId);
            $steps[] = "Character Logged-In";
            
            // get character status
            $api->login()->getCharacterWorlds();
            $this->console->writeln('- Character world status confirmed');
            $steps[] = "Worlds Confirmed";
            
            // wait a bit
            $this->console->writeln('- Testing market in a moment...');
            sleep(mt_rand(5,15));

            // perform a test
            $api->market()->getItemMarketListings(mt_rand(2000,25000));
            $this->console->writeln('- Market fetch confirmed.');
            $steps[] = "Price Checked";

            // set token expiry
            $token
                ->setMessage('Online')
                ->setOnline(true)
                ->setExpiring(time() + mt_rand((3600 * 6), (3600 * 18)))
                ->setToken($api->Token()->get());
        } catch (\Exception $ex) {
            // try again in a bit (5 - 180 minutes)
            $timeout = mt_rand((60 * 5), (60 * 180));

            // prevent logging into same server if it fails for a random amount of time
            Redis::Cache()->set("companion_server_login_issues2_{$account}_{$server}", true, $timeout);

            $token
                ->setMessage('Offline - Failed to login to Companion.')
                ->setExpiring(time() + $timeout)
                ->setOnline(false);

            $steps = implode(', ', $steps);
            $this->errorHandler->exception(
                "SE_Login_Failure",
                "Account: ({$account}) {$username} - Server: {$server} - Message: {$ex->getMessage()} - Stages: {$steps}"
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
            if ($token->hasExpired()) {
                $token->setOnline(false)->setExpiring(0)->setMessage("Detected offline when fetched")->setToken(null);
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
    public function getCompanionTokenForServer(int $server): CompanionToken
    {
        $tokens = $this->getCompanionTokens();
        shuffle($tokens);
        
        foreach ($tokens as $entity) {
            if ($entity->isOnline() == false) {
                continue;
            }
            
            $serverId = GameServers::getServerId($entity->getServer());
            
            if ($serverId == $server) {
                return $entity;
            }
        }
        
        throw new \Exception('No token found for server: '. $server);
    }
}
