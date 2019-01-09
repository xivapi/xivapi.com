<?php

namespace App\Service\Companion;

use Companion\CompanionApi;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This class will log into each character on both accounts
 * and register a 24 usable token. Stick it on a cronjob
 * daily under the command: CompanionAppLoginCommand
 */
class CompanionTokenManager
{
    const SERVERS = [
        // test
        //'Phoenix'       => 'COMPANION_APP_ACCOUNT_D',
        
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
    
    /** @var SymfonyStyle */
    private $io;
    /** @var array */
    private $table = [];
    /** @var string  */
    private $date = null;
    
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
    public function go(string $account, bool $debug = false): void
    {
        $this->date = date('Y-m-d H:i:s') . ' (UTC)';
        $this->io->title('Companion App API Token Manager');
        $this->io->text("Date: {$this->date}");
    
        [$username, $password] = explode(',', getenv($account));

        foreach (self::SERVERS as $server => $accountRegistered) {
            // skip all but phoenix if in debug mode
            if ($debug && $server !== 'Phoenix') {
                continue;
            }
            
            try {
                // skip characters not for this account
                if ($account != $accountRegistered) {
                    continue;
                }

                $this->io->text("Server: {$server}");

                // initialize API
                $api = new CompanionApi("xivapi_{$server}_temp", Companion::PROFILE_FILENAME);

                try {
                    $api->Account()->login($username, $password);
                } catch (\Exception $ex) {
                    $this->addToTable($server, 'Could not login to account, reason: ' . $ex->getMessage());
                    $this->setAccountError($server, 'Login failure');
                    continue;
                }

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
                    $this->addToTable($server, 'Could not find a character for this server.');
                    $this->setAccountError($server, "Could not find a character on this server.");
                    continue;
                }

                // login to the found character
                $api->login()->loginCharacter($characterId);

                // confirm
                $character = $api->login()->getCharacter()->character;
                if ($characterId !== $character->cid) {
                    $this->addToTable($server, 'Could not login to this character.');
                    $this->setAccountError($server, 'Could not login to the character for this server.');
                    continue;
                }
                
                // confirm character status
                $status = $api->login()->getCharacterStatus();
                if (empty($status)) {
                    $this->addToTable($server, 'Could not confirm character status.');
                    $this->setAccountError($server, 'Could not confirm character status.');
                    continue;
                }

                // validate login
                try {
                    $api->market()->getItemMarketListings(5);
                } catch (\Exception $ex) {
                    $this->addToTable($server, "Could not validate Earth Shard sale count, reason: {$ex->getMessage()}");
                    $this->setAccountError($server, "Could not obtain market board prices.");
                    continue;
                }

                // confirm success
                $this->addToTable($server, "✔ Token: {$api->Profile()->getToken()}");
                $this->setAccountSuccess($server, "Character login token generated.");
            } catch (\Exception $ex) {
                $this->addToTable($server, "Could not validate Earth Shard sale count, reason: {$ex->getMessage()}");
                $this->setAccountError($server, "Could not obtain market board prices.");
            }
        }
        
        $this->io->text([
            '', '- Copying temp login to main production sessions ...', ''
        ]);
        
        // copy all temps to mains
        foreach (self::SERVERS as $server => $accountRegistered) {
            // skip all but phoenix if in debug mode
            if ($debug && $server !== 'Phoenix') {
                continue;
            }
            
            // skip characters not for this account
            if ($account != $accountRegistered) {
                continue;
            }
            
            $this->setAccountSessionFromTemp($server);
        }

        // print results
        $this->io->text("Date: {$this->date}");
        $this->io->table(
            [ 'Server', 'Information' ],
            $this->getTable()
        );
        
    }

    /**
     * Log some information to the table
     */
    private function addToTable($server, $information): void
    {
        $this->table[$server] = $information;
    }

    /**
     * Get proper table information
     */
    private function getTable(): array
    {
        $arr = [];
        foreach ($this->table as $server => $info) {
            $arr[] = [ $server, $info ];
        }

        return $arr;

    }
    
    /**
     * Return account login status information
     */
    public static function getAccountsLoginStatusInformation()
    {
        $json = file_get_contents(Companion::PROFILE_FILENAME);
        $json = json_decode($json);
        
        $data = [];
        $headers = [
            'Server',
            'Status',
            'Information'
        ];
        
        foreach (self::SERVERS as $server => $account) {
            $main = $json->{"xivapi_{$server}"} ?? null;
            $information = (isset($main->status) && $main->status ? $main->status : 'No logged in session information for this server.');

            $data[] = [
                "**{$server}**",

                // we only care about the status of the main session
                ($main ? (isset($main->ok) && $main->ok ? '✅ LIVE!' : '❌ Offline') : '❌ Offline'),
                $information
            ];
        }
        
        return [ $headers, $data ];
    }

    /**
     * Set an error on the token
     */
    private function setAccountError($server, $message)
    {
        $this->setAccountValue($server, 'status', "{$this->date} - $message");
        $this->setAccountValue($server, 'ok', false);
        $this->setAccountValue($server, 'time', time());
    }

    /**
     * Set a success on the token
     */
    private function setAccountSuccess($server, $message)
    {
        $this->setAccountValue($server, 'status', "{$this->date} - $message");
        $this->setAccountValue($server, 'ok', true);
        $this->setAccountValue($server, 'time', time());
    }

    /**
     * Set an account value on the session
     */
    private function setAccountValue($server, $field, $message)
    {
        $json = file_get_contents(Companion::PROFILE_FILENAME);
        $json = json_decode($json);
        $json->{"xivapi_{$server}_temp"}->{$field} = $message;
    
        file_put_contents(Companion::PROFILE_FILENAME, json_encode($json, JSON_PRETTY_PRINT));
    }
    
    /**
     * Set the account session from temp
     */
    private function setAccountSessionFromTemp($server)
    {
        $json = file_get_contents(Companion::PROFILE_FILENAME);
        $json = json_decode($json);
        
        // only copy if its OK
        if ($json->{"xivapi_{$server}_temp"}->ok) {
            $json->{"xivapi_{$server}"} = $json->{"xivapi_{$server}_temp"};
        }
        
        file_put_contents(Companion::PROFILE_FILENAME, json_encode($json, JSON_PRETTY_PRINT));
    }
}
