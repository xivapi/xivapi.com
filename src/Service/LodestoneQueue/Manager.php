<?php

namespace App\Service\LodestoneQueue;

use App\Entity\LodestoneStatistic;
use App\Service\Common\Mog;
use Doctrine\ORM\EntityManagerInterface;
use Lodestone\Api;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;

class Manager
{
    /** @var SymfonyStyle */
    private $io;
    /** @var EntityManagerInterface */
    private $em;
    /** @var string */
    private $now;

    public function __construct(SymfonyStyle $io, EntityManagerInterface $em)
    {
        $this->io  = $io;
        $this->em  = $em;
        $this->now = date('Y-m-d H:i:s');
    }

    /**
     * Process incoming requests FROM xivapi, these will be requests
     * to the sync server asking it to parse various pages, these
     * will be in the queue: [$queue]_requests and be saved back to: [$queue]_response
     * once they have been fulfilled.
     */
    public function processRequests(string $queue): void
    {
        $this->io->title("processRequests: {$queue} - Time: {$this->now}");

        try {
            $requestRabbit  = new RabbitMQ();
            $responseRabbit = new RabbitMQ();

            // connect to the request and response queue
            $requestRabbit->connect("{$queue}_request");
            $responseRabbit->connect("{$queue}_response");

            // read requests
            $requestRabbit->readMessageAsync(function($request) use ($responseRabbit) {
                // update times
                $request->responses = [];
                $startTime = microtime(true);
                $startDate = date('H:i:s');
                $this->io->text("REQUESTS START : ". str_pad($request->queue, 50) ." - ". $startDate);
                
                // loop through request ids
                foreach ($request->ids as $id) {
                    $this->now = date('Y-m-d H:i:s');
    
                    // call the API class dynamically and record any exceptions
                    try {
                        $request->responses[$id] = call_user_func_array([new Api(), $request->method], [ $id ]);
                        #$this->io->text("> ". time() ." {$request->method}  ". str_pad($id, 15) ."  (OK)");
                    } catch (\Exception $ex) {
                        $request->responses[$id] = get_class($ex);
                        #$this->io->text("> ". time() ." {$request->method}  ". str_pad($id, 15) ."  (". get_class($ex) .")");
                        
                        // if it's not a valid lodestone exception, report it
                        if (strpos(get_class($ex), 'Lodestone\Exceptions') === false) {
                            $this->io->error("[10] REQUEST :: ". get_class($ex) ." at: {$this->now} -- {$ex->getMessage()} #{$ex->getLine()} {$ex->getFile()}");
                            $this->io->error(json_encode($request, JSON_PRETTY_PRINT));
                            $this->io->error($ex->getTraceAsString());
                            break;
                        }
                    }
                }
                
                // send the request back with the response
                $responseRabbit->sendMessage($request);
                
                // report duration
                $duration = round(microtime(true) - $startTime, 3);
                $this->io->text("REQUESTS END   : ". str_pad($request->queue, 50) ." - ". $startDate ." > ". date('H:i:s') ." = {$duration}");
            });

            // close connections
            $this->io->text('Closing RabbitMQ Connections...');
            $requestRabbit->close();
            $responseRabbit->close();
        } catch (\Exception $ex) {
            // can trigger due to socket closure, fine to just let hypervisor restart
            if (get_class($ex) == AMQPRuntimeException::class) {
                $this->io->text('-- (AMQPRuntimeException) SOCKET CLOSED :: RESTARTING PROCESS --');
                $requestRabbit->close();
                $responseRabbit->close();
                exit(1337);
            }
    
            $this->io->error("[35] REQUEST :: ". get_class($ex) ." at: {$this->now} -- {$ex->getMessage()} #{$ex->getLine()} {$ex->getFile()}");
            $this->io->error($ex->getTraceAsString());
        }
    }
    
    /**
     * Process response messages back from RabbitMQ
     */
    public function processResponse(string $queue): void
    {
        $this->io->title("processResponse: {$queue} - Time: {$this->now}");

        try {
            $responseRabbit = new RabbitMQ();
            $responseRabbit->connect("{$queue}_response");
            
            // read responses
            $responseRabbit->readMessageAsync(function($response) use ($queue) {
                $startTime = microtime(true);
                $startDate = date('H:i:s');
                $duration = round(time() - $response->added, 4);
                
                if ($duration > 100) {
                    Mog::send("<:disconnecting:539860340251426816> [XIVAPI] Lodestone queue duration exceeded 100 seconds: {$duration} for queue: {$queue}");
                }
                
                // connect to db
                // todo - possible cpu leak here
                $this->em->getConnection()->connect();
    
                // Record stats
                $stat = new LodestoneStatistic();
                $stat
                    ->setQueue($response->queue)
                    ->setMethod($response->method)
                    ->setDuration($duration)
                    ->setCount(count($response->ids))
                    ->setRequestId($response->requestId ?: 'none_set');
    
                $this->em->persist($stat);
                $this->em->flush();
    
                try {
                    foreach ($response->responses as $id => $data) {
                        // handle response based on queue
                        switch($response->queue) {
                            default:
                                $this->io->error("Unknown response queue: {$response->queue}");
                                return;
    
                            case 'character_add':
                            case 'character_update':
                            case 'character_update_0_normal':
                            case 'character_update_1_normal':
                            case 'character_update_2_normal':
                            case 'character_update_3_normal':
                            case 'character_update_4_normal':
                            case 'character_update_5_normal':
                            case 'character_update_0_patreon':
                            case 'character_update_1_patreon':
                            case 'character_update_0_low':
                            case 'character_update_1_low':
                                CharacterQueue::response($this->em, $id, $data);
                                break;
        
                            case 'character_friends_add':
                            case 'character_friends_update':
                            case 'character_friends_update_0_normal':
                            case 'character_friends_update_1_normal':
                            case 'character_friends_update_0_patreon':
                            case 'character_friends_update_1_patreon':
                                CharacterFriendQueue::response($this->em, $id, $data);
                                break;
        
                            case 'character_achievements_add':
                            case 'character_achievements_update':
                            case 'character_achievements_update_0_normal':
                            case 'character_achievements_update_1_normal':
                            case 'character_achievements_update_2_normal':
                            case 'character_achievements_update_3_normal':
                            case 'character_achievements_update_4_normal':
                            case 'character_achievements_update_5_normal':
                            case 'character_achievements_update_0_patreon':
                            case 'character_achievements_update_1_patreon':
                                CharacterAchievementQueue::response($this->em, $id, $data);
                                break;
        
                            case 'free_company_add':
                            case 'free_company_update':
                            case 'free_company_update_0_normal':
                            case 'free_company_update_1_normal':
                            case 'free_company_update_0_patron':
                            case 'free_company_update_1_patron':
                                FreeCompanyQueue::response($this->em, $id, $data);
                                break;
        
                            case 'linkshell_add':
                            case 'linkshell_update':
                            case 'linkshell_update_0_normal':
                            case 'linkshell_update_1_normal':
                            case 'linkshell_update_0_patron':
                            case 'linkshell_update_1_patron':
                                LinkshellQueue::response($this->em, $id, $data);
                                break;
        
                            case 'pvp_team_add':
                            case 'pvp_team_update':
                            case 'pvp_team_update_0_normal':
                            case 'pvp_team_update_1_normal':
                            case 'pvp_team_update_0_patron':
                            case 'pvp_team_update_1_patron':
                                PvPTeamQueue::response($this->em, $id, $data);
                                break;
                        }
                    }
                } catch (\Exception $ex) {
                    $this->io->error("[40] RESPONSE :: Exception ". get_class($ex) ." at: {$this->now} = {$ex->getMessage()} #{$ex->getLine()} {$ex->getFile()}");
                    $this->io->error($ex->getTraceAsString());
                }
    
                // report duration
                $duration = round(microtime(true) - $startTime, 3);
                $this->io->text("RESPONSE COMPLETE : ". str_pad($response->queue, 50) ." - ". $startDate ." > ". date('H:i:s') ." = {$duration}");
                $this->em->getConnection()->close();
            });
    
            $responseRabbit->close();
        } catch (\Exception $ex) {
            $this->io->error("[80] RESPONSE :: ". get_class($ex) ." at: {$this->now} -- {$ex->getMessage()} {$ex->getLine()} #{$ex->getFile()}");
            $this->io->error($ex->getTraceAsString());
        }
    }
}
