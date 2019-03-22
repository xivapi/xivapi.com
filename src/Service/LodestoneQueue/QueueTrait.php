<?php

namespace App\Service\LodestoneQueue;

use App\Entity\Character;
use App\Service\API\ApiPermissions;
use App\Service\API\ApiRequest;
use App\Service\Redis\Redis;
use Doctrine\ORM\EntityManagerInterface;
use Lodestone\Exceptions\AchievementsPrivateException;
use Lodestone\Exceptions\ForbiddenException;
use Lodestone\Exceptions\GenericException;
use Lodestone\Exceptions\NotFoundException;

trait QueueTrait
{
    /**
     * Immediately save an entity
     *
     * @param EntityManagerInterface $em
     * @param $entity
     */
    protected static function save(EntityManagerInterface $em, $entity)
    {
        $em->persist($entity);
        $em->flush();
    }

    /**
     * Queue multiple existing entries
     *
     * @param array $entries
     * @param string $queue
     */
    public static function queue(array $entries, string $queue)
    {
        if (empty($entries)) {
            return;
        }

        $ids = [];
        foreach ($entries as $obj) {
            $ids[] = $obj['id'];
        }

        self::request($ids, $queue);
    }

    /**
     * Request an id to be parsed
     */
    public static function request($ids, string $queue)
    {
        // a single "access point" (ip/account) can add 8 characters per day. This is what SE limit to.
        $limit = 8;

        $count = Redis::Cache()->get('lodestone_queue_count_'. ApiRequest::$id);
        $count++;
        Redis::Cache()->set('lodestone_queue_count_'. ApiRequest::$id, $count);

        // if the individual user has reached the limit and doesn't have special permissions, block them
        if ($count > $limit && ApiPermissions::has(ApiPermissions::PERMISSION_ADD_LODESTONE_CONTENT) === false) {
            return;
        }

        $ids = is_array($ids) ? $ids : [ $ids ];
        
        $rabbit = new RabbitMQ();
        $rabbit->connect($queue .'_request');
        $rabbit->sendMessage([
            'requestId' => QueueId::get(),
            'queue'     => $queue,
            'added'     => time(),
            'method'    => self::METHOD,
            'ids'       => $ids,
        ]);
        
        $rabbit->close();
    }

    /**
     * Handle a response from rabbitmq
     */
    public static function response(EntityManagerInterface $em, $lodestoneId, $data): void
    {
        /** @var Character $entity */
        $entity = self::getEntity($em, $lodestoneId);

        // handle response state
        // if there was an error
        if (is_string($data)) {
            switch($data) {
                // unknown error
                default: break;

                // todo - not sure what to do here
                case \Exception::class:
                case GenericException::class:
                    break;

                // register as not found
                case NotFoundException::class:
                    $entity->setStateNotFound()->incrementNotFoundChecks();
                    self::save($em, $entity);
                    break;

                // register as private
                case AchievementsPrivateException::class:
                case ForbiddenException::class:
                    $entity->setStatePrivate()->incrementAchievementsPrivateChecks();
                    self::save($em, $entity);
                    break;
            }
            return;
        }

        // send response to be handled
        self::handle($em, $entity, $lodestoneId, $data);
    }
}
