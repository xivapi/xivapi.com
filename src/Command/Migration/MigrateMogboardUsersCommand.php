<?php

namespace App\Command\Migration;

use App\Common\Command\CommandConfigureTrait;
use App\Common\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateMogboardUsersCommand extends Command
{
    use CommandConfigureTrait;
    
    const COMMAND = [
        'name' => 'MigrateMogboardUsersCommand',
        'desc' => '',
    ];
    
    private $em;
    
    public function __construct(EntityManagerInterface $em, ?string $name = null)
    {
        parent::__construct($name);
        
        $this->em = $em;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Migrating MOGBOARD DB to XIVAPI");
        
        $db   = $this->em->getConnection();

        //
        // Migrate users
        //
        $sql = "SELECT id, username, patron, sso_discord_id FROM mogboard.users";
        $sql = $db->prepare($sql);
        $sql->execute();
        
        foreach ($sql->fetchAll() as $row) {
            // find XIVAPI user with the same discord id
            $sql = "SELECT id, username FROM dalamud.users WHERE sso_discord_id = '%s'";
            $sql = sprintf($sql, $row['sso_discord_id']);
            $sql = $db->prepare($sql);
            $sql->execute();
            
            // get xivapi result
            $result = $sql->fetch();
            
            //
            // if they have an account, update the ID and Patron
            //
            if ($result) {
                $sql = "UPDATE dalamud.users SET id = '%s', patron = '%s' WHERE id = '%s'";
                $sql = sprintf($sql, $row['id'], $row['patron'], $result['id']);
                $sql = $db->prepare($sql);
                $sql->execute();
                
                $output->writeln('Merged: '. $row['username']);
                continue;
            }
            
            //
            // User does not have an XIVAPI Account, make one,
            // 1st - select all their mogboard details
            //
            $sql = "SELECT * FROM mogboard.users WHERE sso_discord_id = '%s'";
            $sql = sprintf($sql, $row['sso_discord_id']);
            $sql = $db->prepare($sql);
            $sql->execute();
            
            $mb = $sql->fetch();
    
            $user = new User();
            $user
                ->setId($mb['id'])
                ->setUsername($mb['username'])
                ->setEmail($mb['email'])
                ->setPatron($mb['patron'])
                ->setSso($mb['sso'])
                ->setSsoDiscordId($mb['sso_discord_id'])
                ->setSsoDiscordAvatar($mb['sso_discord_avatar'])
                ->setSsoDiscordTokenExpires($mb['sso_discord_token_expires'])
                ->setSsoDiscordTokenRefresh($mb['sso_discord_token_refresh'])
                ->setSsoDiscordTokenAccess($mb['sso_discord_token_access'])
                ->setAlertsMax($mb['alerts_max'])
                ->setAlertsExpiry($mb['alerts_expiry'])
                ->setAlertsUpdate($mb['patron'] == 4);
            
            $this->em->persist($user);
            $this->em->flush();
            $this->em->clear();
            $output->writeln("Created user: {$user->getUsername()}");
        }
    }
}
