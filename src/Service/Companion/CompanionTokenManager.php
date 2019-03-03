<?php

namespace App\Service\Companion;

use App\Entity\CompanionToken;
use App\Repository\CompanionTokenRepository;
use App\Service\Common\Mog;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use Companion\CompanionApi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class CompanionTokenManager
{
    /**
     * Current servers that are offline due to character restrictions
     */
    const SERVERS_OFFLINE = [
        'Gungnir',
        'Bahamut',
        'Chocobo',
        'Mandragora',
        'Shinryu',
    ];
    
    /**
     * Current servers and their associated login account
     */
    const SERVERS_ACCOUNTS = [
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

        // US Servers (Balmung has its own account
        'Balmung'       => 'COMPANION_APP_ACCOUNT_C',

        'Adamantoise'   => 'COMPANION_APP_ACCOUNT_A',
        'Cactuar'       => 'COMPANION_APP_ACCOUNT_A',
        'Coeurl'        => 'COMPANION_APP_ACCOUNT_A',
        'Faerie'        => 'COMPANION_APP_ACCOUNT_A',
        'Gilgamesh'     => 'COMPANION_APP_ACCOUNT_A',
        'Goblin'        => 'COMPANION_APP_ACCOUNT_A',
        'Jenova'        => 'COMPANION_APP_ACCOUNT_A',
        'Mateus'        => 'COMPANION_APP_ACCOUNT_A',
        'Midgardsormr'  => 'COMPANION_APP_ACCOUNT_A',
        'Sargatanas'    => 'COMPANION_APP_ACCOUNT_A',
        'Siren'         => 'COMPANION_APP_ACCOUNT_A',
        'Zalera'        => 'COMPANION_APP_ACCOUNT_A',
        'Behemoth'      => 'COMPANION_APP_ACCOUNT_A',
        'Brynhildr'     => 'COMPANION_APP_ACCOUNT_A',
        'Diabolos'      => 'COMPANION_APP_ACCOUNT_A',
        'Excalibur'     => 'COMPANION_APP_ACCOUNT_A',
        'Exodus'        => 'COMPANION_APP_ACCOUNT_A',
        'Famfrit'       => 'COMPANION_APP_ACCOUNT_A',
        'Hyperion'      => 'COMPANION_APP_ACCOUNT_A',
        'Lamia'         => 'COMPANION_APP_ACCOUNT_A',
        'Leviathan'     => 'COMPANION_APP_ACCOUNT_A',
        'Malboro'       => 'COMPANION_APP_ACCOUNT_A',
        'Ultros'        => 'COMPANION_APP_ACCOUNT_A',

        // EU Servers
        'Cerberus'      => 'COMPANION_APP_ACCOUNT_A',
        'Lich'          => 'COMPANION_APP_ACCOUNT_A',
        'Louisoix'      => 'COMPANION_APP_ACCOUNT_A',
        'Moogle'        => 'COMPANION_APP_ACCOUNT_A',
        'Odin'          => 'COMPANION_APP_ACCOUNT_A',
        'Omega'         => 'COMPANION_APP_ACCOUNT_A',
        'Phoenix'       => 'COMPANION_APP_ACCOUNT_A',
        'Ragnarok'      => 'COMPANION_APP_ACCOUNT_A',
        'Shiva'         => 'COMPANION_APP_ACCOUNT_A',
        'Zodiark'       => 'COMPANION_APP_ACCOUNT_A',
    ];

    /** @var EntityManagerInterface em */
    private $em;
    /** @var ConsoleOutput */
    private $console;
    /** @var CompanionTokenRepository */
    private $repository;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->repository = $em->getRepository(CompanionToken::class);
        $this->console = new ConsoleOutput();
    }
    
    public function account(string $accountId, bool $force = false)
    {
        foreach (self::SERVERS_ACCOUNTS as $server => $account) {
            if ($account == $accountId) {
                $ok = $this->login($server, $force);
                
                // sleep for a random amount, because SE ?
                sleep($ok ? mt_rand(5, 30) : 0);
            }
        }
    }
    
    /**
     * Login to a specific server
     */
    public function login(string $server, bool $force = false): bool
    {
        $this->console->writeln("<comment>Server: {$server}</comment>");

        if (in_array($server, self::SERVERS_OFFLINE)) {
            $this->console->writeln('No characters available on this server at this time.');
            return false;
        }

        // grab account
        $account = self::SERVERS_ACCOUNTS[$server];
        [$username, $password] = explode(',', getenv($account));
        
        if (empty($username) || empty($password)) {
            throw new \Exception('SE Account Username OR Password was empty.');
        }
        
        // grab saved token in db
        $entity = $this->repository->findOneBy([ 'server' => $server ]);
        $entity = $entity ?: new CompanionToken();
       
        
        try {
            // initialize API and create a new token
            $api = new CompanionApi("{$username}_{$server}");

            if ($force === false && $api->Token()->hasExpired($entity->getToken()) === false) {
                $this->console->writeln('Token has not yet expired, skipping.');
                return false;
            }
            
            // ensure some entity stuff is set
            $entity
                ->setServer($server)
                ->setOnline(false)
                ->setLastOnline(1);
            
            // login
            $this->console->writeln("- Account Login: {$username}");
            $api->Account()->login($username, $password);
    
            // find character for this server
            $this->console->writeln('- Finding active character ...');
            $cid = null;
            foreach ($api->Login()->getCharacters()->accounts[0]->characters as $character) {
                if ($character->world == $server) {
                    $cid = $character->cid;
                    break;
                }
            }

            // couldn't find a valid character
            if ($cid === null) {
                throw new \Exception("Could not find a character for server.");
            }
    
            // login with our chosen character!
            $this->console->writeln("- Logging into character: {$cid}");
            $api->Login()->loginCharacter($cid);
    
            // confirm
            $character = $api->login()->getCharacter()->character;
            $this->console->writeln("- Character logged into: {$character->name} ({$character->world})");
    
            // get character status
            $api->login()->getCharacterStatus();
            $this->console->writeln('- Character world status confirmed');
    
            // perform a test
            $api->market()->getItemMarketListings(5);
            $this->console->writeln('- Market fetch confirmed.');
    
            // confirm success
            $entity
                ->setLastOnline(time())
                ->setMessage('Online')
                ->setOnline(true)
                ->setToken($api->Token()->get()->toArray());
            
            $this->console->writeln('- Complete');
        } catch (\Exception $ex) {
            $entity
                ->setLastOnline(time())
                ->setMessage('Failed to login: '. $ex->getMessage())
                ->setOnline(false);
    
            $this->postCompanionStatusOnDiscord($ex, $server);
            $this->console->writeln('- Character failed to login: '. $ex->getMessage());
        }
    
        $this->em->persist($entity);
        $this->em->flush();
        
        return true;
    }

    /**
     * @return CompanionToken[]
     */
    public function getCompanionTokens(): array
    {
        return $this->em->getRepository(CompanionToken::class)->findAll();
    }
    
    /**
     * @return CompanionToken[]
     */
    public function getCompanionTokensPerServer(): array
    {
        $list = [];
        foreach ($this->getCompanionTokens() as $entity) {
            $list[$entity->getServer()] = $entity;
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
        foreach ($this->getCompanionTokens() as $entity) {
            if ($entity->getServer() === $server) {
                return $entity;
            }
        }
        
        throw new \Exception('No token found for server: '. $server);
    }

    /**
     * Post companion login status on discord (if any failed)
     */
    private function postCompanionStatusOnDiscord(\Exception $ex, $server)
    {
        if (getenv('APP_ENV') == 'dev') {
            print_r([ $ex->getMessage(), $server ]);
            return;
        }
        
        $time = new Carbon();
        $time->setTimezone(new CarbonTimeZone('Europe/London'));
        
        // Ignore between 1am and 9am
        if ($time->hour > 1 && $time->hour < 9) {
            return;
        }
        
        $message = "<@42667995159330816> [Companion Login Status] Failed to login to: **{$server}** - Will try again in 10 minutes. Reason: `{$ex->getMessage()}`";
        Mog::send("<:status:474543481377783810> [XIVAPI] ". $message);
    }
}
