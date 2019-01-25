<?php

namespace App\Service\Companion;

use App\Entity\CompanionToken;
use App\Service\Common\Mog;
use Companion\CompanionApi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This class will log into each character on both accounts
 * and register a 24 usable token. Stick it on a cronjob
 * daily under the command: CompanionAppLoginCommand
 */
class CompanionTokenManager
{
    const SERVERS_OFFLINE = [
        'Gungnir',
        'Bahamut',
        'Chocobo',
        'Mandragora',
        'Shinryu',
    ];

    const SERVERS = [
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

    const ACCOUNTS = [
        'A' => 'COMPANION_APP_ACCOUNT_A',
        'B' => 'COMPANION_APP_ACCOUNT_B',
        'C' => 'COMPANION_APP_ACCOUNT_C',
    ];

    /** @var EntityManagerInterface em */
    private $em;
    /** @var SymfonyStyle */
    private $io;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function setSymfonyStyle(SymfonyStyle $io): void
    {
        $this->io = $io;
    }
    
    /**
     * This will login to each character on each server, it will
     * first attempt to login using a `xivapi_[server]_temp` profile,
     * if this succeeds it will be copied to the main login `xivapi_[server]
     * otherwise it wil lbe marked with an error.
     */
    public function login(string $account, string $debugServer = null): void
    {
        $this->io->title('Companion App API Token Manager');

        $account = self::ACCOUNTS[$account];
        [$username, $password] = explode(',', getenv($account));
        $failed = [];

        //
        // Login to each server
        //
        foreach (self::SERVERS as $server => $accountRegistered) {
            //
            // if debugging, skip all but the debug server
            //
            if ($debugServer && $server != $debugServer) {
                continue;
            }

            //
            // Skip servers who are not part of this account
            //
            if ($account != $accountRegistered) {
                continue;
            }

            //
            // Grab token
            //
            $token = $this->em->getRepository(CompanionToken::class)->findOneBy(['server' => $server]);
            if (!$token) {
                $token = new CompanionToken();
                $token->setServer($server);
            }

            // default state
            $token->setOnline(false);

            //
            // if server not supported, skip it
            //
            if (in_array($server, self::SERVERS_OFFLINE)) {
                $token->setMessage('Server does not yet have a character due to world congestion.');
                continue;
            }

            //
            // Login to Companion App
            //
            try {
                $this->io->text("Server: {$server}");

                // initialize API
                $api = new CompanionApi("xivapi_{$server}_temp", Companion::PROFILE_FILENAME);

                // login
                $api->Account()->login($username, $password);
                $this->io->text('- Account logged in.');

                // get character list
                $characterId = null;
                foreach ($api->login()->getCharacters()->accounts[0]->characters as $character) {
                    if ($character->world == $server) {
                        $characterId = $character->cid;
                        break;
                    }
                }

                // if not found, error
                if ($characterId === null) {
                    throw new \Exception("Could not find a character for server.");
                }

                // login to the found character
                $api->login()->loginCharacter($characterId);
                $this->io->text('- Character logged in.');

                // confirm
                $character = $api->login()->getCharacter()->character;
                if ($characterId !== $character->cid) {
                    throw new \Exception("Could not login to specified character for the server.");
                }
                
                // confirm character status
                $status = $api->login()->getCharacterStatus();
                $this->io->text('- Character status confirmed.');
                if (empty($status)) {
                    throw new \Exception("Could not confirm character status");
                }

                // perform a test
                $api->market()->getItemMarketListings(5);
                $this->io->text('- Market price fetch confirmed.');

                // confirm success
                $token
                    ->setMessage('Online as of: '. date('Y-m-d H:i:s'))
                    ->setOnline(true);

                // update companion record
                $this->setAccountSessionFromTemp($server);
            } catch (\Exception $ex) {
                $token->setMessage('Could not login to account: '. $ex->getMessage());
                $failed[] = $server;
                continue;
            }

            $this->em->persist($token);
            $this->em->flush();
        }

        // inform if any offline
        if ($failed) {
            $this->postCompanionStatusOnDiscord($account, $username, $failed);
        }
    }

    /**
     * @return CompanionToken[]
     */
    public function getCompanionLoginStatus(): array
    {
        return $this->em->getRepository(CompanionToken::class)->findAll();
    }

    /**
     * Post companion login status on discord (if any failed)
     */
    private function postCompanionStatusOnDiscord($account, $username, $failed)
    {
        $failedCount = count($failed);
        $message     = "<@42667995159330816> [Companion Login Status] Account: **{$account}** - **{$username}** - Failed: *{$failedCount}*";

        if ($failed) {
            $message .= " -- The following servers were affected: ". implode(", ", $failed);
        }

        Mog::send("<:status:474543481377783810> [XIVAPI] ". $message);
    }
    
    /**
     * Set the account session from temp
     */
    private function setAccountSessionFromTemp($server)
    {
        $json = file_get_contents(Companion::PROFILE_FILENAME);
        $json = json_decode($json);

        // copy temp login to main login
        $json->{"xivapi_{$server}"} = $json->{"xivapi_{$server}_temp"};
        
        file_put_contents(Companion::PROFILE_FILENAME, json_encode($json, JSON_PRETTY_PRINT));
    }
}
