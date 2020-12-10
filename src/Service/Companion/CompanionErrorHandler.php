<?php

namespace App\Service\Companion;

use App\Common\Constants\DiscordConstants;
use App\Common\ServicesThirdParty\Discord\Discord;
use App\Common\Service\Redis\Redis;
use App\Entity\CompanionError;
use App\Repository\CompanionErrorRepository;
use Doctrine\ORM\EntityManagerInterface;

class CompanionErrorHandler
{
    const CRITICAL_EXCEPTIONS         = 'companion_critical_exception_count_v2';
    const CRITICAL_EXCEPTIONS_COUNT   = 'companion_critical_exception_count_v2_COUNT';
    const CRITICAL_EXCEPTIONS_STOPPED = 'companion_critical_exception_count_v2_STOPPED';

    const ERRORS = [
        'cURL error 28'     => 'Sight Timed-out (CURL 28)',
        'SE_Login_Failure'  => 'Failed to login to Server',
        'rejected'          => 'Request Rejected',
        
        '111001' => 'SE Account Token Expired',
        '210010' => 'Companion Server Down/Having Issues',
        '311004' => 'Unknown',
        '311007' => 'Invalid Cookie',
        '311009' => 'Character Unconfirmed',
        '319201' => 'Emergency Server Maintenance',
        '340000' => 'Sight API 500 Internal Server Error',
    ];

    /** @var EntityManagerInterface */
    private $em;
    /** @var CompanionErrorRepository */
    private $repository;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->repository = $em->getRepository(CompanionError::class);
    }

    /**
     * Record an exception
     */
    public function exception(string $companionError, string $customMessage)
    {

        // Get the error exception type
        [$errorCode, $errorException] = $this->getExceptionCodeAndType($companionError);

        // ignore 500 errors and cURL errors.
        if ($errorCode == '340000' || $errorCode == 'cURL error 28') {
            return;
        }

        // Increase critical exception count
        $this->incrementCriticalExceptionCount();

        $error = new CompanionError();
        $error
            ->setMessage($customMessage)
            ->setException($errorException)
            ->setCode($errorCode);

        $this->em->persist($error);
        $this->em->flush();
        

        
        $date = date('Y-m-d H:i:s', $error->getAdded());
        Discord::mog()->sendMessage(
            DiscordConstants::ROOM_COMPANION_ERRORS,
            "[{$date} UTC] **Companion Error:** Code: {$error->getCode()} Ex: {$error->getException()} -- {$error->getMessage()}"
        );
    }

    /**
     * Get exceptions thrown
     */
    public function getExceptions($limit = 10)
    {
        $exceptions = [];

        /** @var CompanionError $ex */
        foreach($this->repository->findBy([], ['added' => 'desc'], $limit) as $ex) {
            $exceptions[] = [
                'Added'     => $ex->getAdded(),
                'Exception' => $ex->getException(),
                'Message'   => $ex->getMessage(),
                'Code'      => $ex->getCode(),
            ];
        }

        return $exceptions;
    }

    /**
     * Get the critical exception count
     */
    public function isCriticalExceptionCount()
    {
        return Redis::Cache()->get(self::CRITICAL_EXCEPTIONS_STOPPED) != null;
    }

    public function getCriticalExceptionCount()
    {
        return [
            'now' => Redis::Cache()->get(self::CRITICAL_EXCEPTIONS),
            'max' => CompanionConfiguration::ERROR_COUNT_THRESHOLD
        ];
    }

    /**
     * Record the total number of critical exceptions
     */
    private function incrementCriticalExceptionCount()
    {
        if (Redis::Cache()->get(self::CRITICAL_EXCEPTIONS_STOPPED)) {
            return;
        }
        
        // increment critical exceptions
        $count = Redis::Cache()->get(self::CRITICAL_EXCEPTIONS) ?: 0;
        $count = (int)$count;
        $count++;

        // only record exceptions for the next 10 minutes before resetting
        Redis::Cache()->set(self::CRITICAL_EXCEPTIONS, $count, 600);
        
        // if we exceed error threshold, stop for a bit
        if ($count > CompanionConfiguration::ERROR_COUNT_THRESHOLD) {
            // increment stop count
            $count2 = Redis::Cache()->get(self::CRITICAL_EXCEPTIONS_COUNT) ?: 0;
            $count2 = (int)$count2;
            $count2++;
            Redis::Cache()->set(self::CRITICAL_EXCEPTIONS, $count, 1800);

            // pause for a random amount of time.
            $time = mt_rand(30,120);
            Redis::Cache()->set(self::CRITICAL_EXCEPTIONS_STOPPED, $count, (60 * $time));
            Redis::Cache()->delete(self::CRITICAL_EXCEPTIONS);

            // alert mogboard
            Discord::mog()->sendMessage(
                '571007332616503296',
                "**Companion Auto-Update has stopped for {$time} minutes to errors exceeding maximum allowed value.**"
            );

            if ($count2 > 3) {
                // alert mogboard
                Discord::mog()->sendMessage(
                    '571007332616503296',
                    "**Companion has auto stopped at least 3 times in the past hour...**"
                );
            }
        }
    }

    /**
     * Get the exception type
     */
    private function getExceptionCodeAndType(string $message)
    {
        foreach (self::ERRORS as $code => $error) {
            if (stripos($message, $code) !== false) {
                return [$code, $error];
            }
        }

        return ['unknown', $message];
    }
}
