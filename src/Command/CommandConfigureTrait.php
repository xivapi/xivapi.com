<?php

namespace App\Command;

trait CommandConfigureTrait
{
    /**
     * Handle command configuration
     */
    protected function configure()
    {
        $cmd = (object)self::COMMAND;
        
        $this->setName($cmd->name);
        $this->setDescription($cmd->desc ?? 'No Description');

        // add any arguments
        if (isset($cmd->args) && $cmd->args) {
            foreach ($cmd->args as $arg) {
                [ $name, $type, $desc ] = $arg;
                
                $this->addArgument($name, $type, $desc);
            }
        }
    }
}
