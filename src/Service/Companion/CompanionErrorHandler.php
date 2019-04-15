<?php

namespace App\Service\Companion;

use App\Entity\CompanionError;
use App\Repository\CompanionErrorRepository;
use App\Service\Redis\Redis;
use App\Service\ThirdParty\Discord\Discord;
use App\Service\ThirdParty\GoogleAnalytics;
use Doctrine\ORM\EntityManagerInterface;

class CompanionErrorHandler
{
    const REDIS_KEY_CRITICAL_EXCEPTIONS = 'companion_critical_exception_count';

    const ERRORS = [
        'cURL error 28' => 'Sight Timed-out (CURL 28)',
        'SE_Login' => 'Failed to login to Server',

        '111001' => 'SE Account Token Expired',
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
        $errors = $this->repository->findAll();
        $errorTable = [];

        /** @var CompanionError $error */
        foreach ($errors as $i => $error) {
            if (!isset($errorTable[$error->getCode()])) {
                $errorTable[$error->getCode()] = [];
            }

            $errorTable[$error->getCode()][] = $error->getMessage();
        }

        /**
         * Build error message for discord
         */
        $message = '';
        foreach ($errorTable as $errorCode => $errorMessages) {
            $total = count($errorMessages);

            // error title
            $message .= "[{$errorTable}] {$errorMessages} - Total: {$total}\n";

            foreach ($errorMessages as $errorMessage) {
                $message .= "- {$errorMessage}\n";
            }

            $message .= "\n";
        }

        $message = "<@42667995159330816> Companion Error Report:\n```{$message}```";
        Discord::mog()->sendMessage(null, $message);

        /**
         * Delete old error records
         */
        foreach ($errors as $error) {
            $this->em->remove($error);
        }

        $this->em->flush();
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

        // some errors will increase the critical exception error rate
        if (in_array($errorCode, ['unknown', '340000','319201', 'cURL error 28'])) {
            $this->incrementCriticalExceptionCount();
        }

        $error = new CompanionError();
        $error
            ->setMessage($customMessage)
            ->setException($errorException)
            ->setCode($errorCode);

        $this->em->persist($error);
        $this->em->flush();
    }

    /**
     * Get the critical exception count
     */
    public function getCriticalExceptionCount()
    {
        $count = Redis::Cache()->get(self::REDIS_KEY_CRITICAL_EXCEPTIONS) ?: 0;
        return (int)$count;
    }

    /**
     * Record the total number of critical exceptions
     */
    private function incrementCriticalExceptionCount()
    {
        $count = Redis::Cache()->get(self::REDIS_KEY_CRITICAL_EXCEPTIONS) ?: 0;
        $count = (int)$count;
        $count++;

        Redis::Cache()->set(self::REDIS_KEY_CRITICAL_EXCEPTIONS, $count, (60 * 60 * 24 * 3));
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
