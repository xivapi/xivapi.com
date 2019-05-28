<?php

namespace App\WebSockets\BattleBar;

use Ratchet\ConnectionInterface;

/**
 * This class handles any incoming actions and decides what to do, it's
 * effectively a websocket router
 */
class CommandHandler
{
    public static function handle(ConnectionInterface $clientFrom, string $message)
    {
        [$action, $data] = self::getActionFromMessage($message);


        switch ($action) {
            default:
                throw new \Exception("Unknown Action: {$action}");

            // todo - write cases for handling the action
        }
    }

    /**
     *
     */
    private static function getActionFromMessage(string $message)
    {
        $command = explode('::', $message, 2);

        $action  = $command[0] ?? null;
        $data    = $command[1] ?? null;

        if ($action == null || $data == null) {
            throw new \Exception("Invalid action or data");
        }

        return [
            $action,
            $data
        ];
    }
}
