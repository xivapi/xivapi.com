<?php

namespace App\Service\Companion;

use App\Entity\CompanionError;
use App\Repository\CompanionErrorRepository;
use App\Service\Redis\Redis;
use App\Service\ThirdParty\Discord\Discord;
use App\Service\ThirdParty\GoogleAnalytics;
use Companion\Exceptions\CompanionException;
use Doctrine\ORM\EntityManagerInterface;

class CompanionErrorHandler
{
    const CRITICAL_EXCEPTIONS = 'companion_critical_exception_count_v2';
    const CRITICAL_EXCEPTIONS_TIMEOUT = (60 * 10);

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
     * Report to discord the error status
     */
    public function report()
    {
        $errors = [];
        $timeout = time() - (60 * 60 * 12);
        
        foreach ($this->getExceptions(999) as $ex) {
            if ($ex['Added'] < $timeout) {
                continue;
            }
            
            $errors[] = sprintf(
                "[%s][%s] %s: %s",
                date('Y-m-d H:i:s', $ex['Added']),
                $ex['Type'],
                $ex['Exception'],
                $ex['Message']
            );
        }

        if (empty($errors)) {
            $message = "No companion errors to report in the past 12 hours.";
            Discord::mog()->sendMessage('571007332616503296', $message);
            return;
        }

        $errors  = implode("\n", $errors);
        $message = "Companion error report (12 hours):\n```{$errors}```";
        Discord::mog()->sendMessage('571007332616503296', $message);

        // delete Redis record
        Redis::Cache()->delete(self::CRITICAL_EXCEPTIONS);
    }

    /**
     * Record an exception
     */
    public function exception(string $companionError, string $customMessage)
    {
        // Analytics
        GoogleAnalytics::companionTrackItemAsUrl('companion_error');

        // Get the error exception type
        [$errorCode, $errorException] = $this->getExceptionCodeAndType($companionError);

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
            '571007332616503296',
            "[{$date} UTC] **Companion Error:** {$error->getCode()} {$error->getMessage()} {$error->getException()}"
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
                'Type'      => $ex->getCode(),
            ];
        }

        return $exceptions;
    }

    /**
     * Get the critical exception count
     */
    public function getCriticalExceptionCount()
    {
        $count = Redis::Cache()->get(self::CRITICAL_EXCEPTIONS) ?: 0;
        return (int)$count;
    }

    /**
     * Record the total number of critical exceptions
     */
    private function incrementCriticalExceptionCount()
    {
        $count = Redis::Cache()->get(self::CRITICAL_EXCEPTIONS) ?: 0;
        $count = (int)$count;
        $count++;

        Redis::Cache()->set(
            self::CRITICAL_EXCEPTIONS,
            $count,
            CompanionConfiguration::ERROR_SYSTEM_TIMEOUT
        );
        
        if ($count > CompanionConfiguration::ERROR_COUNT_THRESHOLD) {
            Discord::mog()->sendMessage(
                '571007332616503296',
                '**Companion Auto-Update has stopped for 1 hour due to errors exceeding maximum allowed value.**'
            );
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
