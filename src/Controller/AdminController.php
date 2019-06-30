<?php

namespace App\Controller;

use App\Common\Entity\User;
use App\Common\Game\GameServers;
use App\Common\Service\Redis\RedisTracking;
use App\Common\User\Users;
use App\Entity\CompanionToken;
use App\Service\API\ApiPermissions;
use App\Service\Companion\CompanionErrorHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    /** @var Users */
    private $users;
    /** @var CompanionErrorHandler */
    private $ceh;
    /** @var CompanionErrorHandler */
    private $em;

    public function __construct(
        EntityManagerInterface $em,
        CompanionErrorHandler $ceh,
        Users $users
    ) {
        $this->em    = $em;
        $this->ceh   = $ceh;
        $this->users = $users;
    }
    
    private function authenticate(): User
    {
        $user = $this->users->getUser(true);
        $user->mustBeAdmin();
        return $user;
    }
    
    /**
     * @Route("/admin", name="admin")
     */
    public function admin()
    {
        $this->authenticate();
        
        return $this->render('admin/home.html.twig');
    }
    
    /**
     * @Route("/admin/companion/accounts", name="admin_companion_accounts")
     */
    public function companionAccounts()
    {
        $this->authenticate();
        
        $tokens = $this->em->getRepository(CompanionToken::class)->findAll();
    
        $tokenServers = [];
        $validServers = array_merge(
            GameServers::LIST_DC['Aether'],
            GameServers::LIST_DC['Primal'],
            GameServers::LIST_DC['Crystal'],
            GameServers::LIST_DC['Chaos'],
            GameServers::LIST_DC['Light']
        );
        
        /** @var CompanionToken $token */
        foreach ($tokens as $token) {
            [$user, $pass] = explode(',', getenv($token->getAccount()));
            $index = "{$token->getAccount()} - {$user}";
            
            if (!isset($tokenServers[$index])) {
                $tokenServers[$index] = [];
            }
    
            $tokenServers[$index][] = $token->getServer();
        }
        
        return $this->render('admin/companion_accounts.html.twig', [
            'token_servers' => $tokenServers,
            'valid_servers' => $validServers,
            'datacenter'  => GameServers::LIST_DC
        ]);
    }

    /**
     * @Route("/admin/companion/errors", name="admin_companion_errors")
     */
    public function companionErrors()
    {
        $this->authenticate();
        
        date_default_timezone_set("Europe/London");

        $user = $this->users->getUser(true);
        ApiPermissions::set($user->getPermissions());
        ApiPermissions::must(ApiPermissions::PERMISSION_ADMIN);

        $errors     = $this->ceh->getExceptions(20000);
        $lastError  = $errors[0];
        $errorGraph = [
            date('Y-m-d', (time() + (60 * 60 * 24))) => 0,
        ];
        $exception  = [];
        
        foreach ($errors as $error) {
            $index = date('Y-m-d', $error['Added']);
            $ex = $error['Exception'];

            $errorGraph[$index] = isset($errorGraph[$index]) ? $errorGraph[$index] + 1 : 1;
            $exception[$ex]     = isset($exception[$ex]) ? $exception[$ex] + 1 : 1;
        }

        krsort($errorGraph);
        $errorGraph = array_reverse($errorGraph);
        $errorGraph = array_splice($errorGraph, 0, 60);

        return $this->render('admin/companion_errors.html.twig', [
            'status' => [
                'at_critical' => $this->ceh->isCriticalExceptionCount(),
                'state'       => $this->ceh->getCriticalExceptionCount(),
                'last_error'  => $lastError,
            ],
            'errors' => [
                'list'       => $errors,
                'exceptions' => $exception,
            ],
            'errorGraph' => [
                'keys'   => array_keys($errorGraph),
                'values' => array_values($errorGraph),
            ],
        ]);
    }
    
    /**
     * @Route("/admin/tracking", name="admin_tracking")
     */
    public function tracking()
    {
        $this->authenticate();
        
        return $this->render('admin/statistics.html.twig');
    }
    
    /**
     * @Route("/admin/tracking_stats")
     */
    public function trackingStats()
    {
        $this->authenticate();
        
        $report = RedisTracking::get();
        $report = (Array)$report;
        ksort($report);
        
        return new Response(
            json_encode($report, JSON_PRETTY_PRINT)
        );
    }
    
    /**
     * @Route("/admin/tracking_stats_reset")
     */
    public function trackingStatsReset()
    {
        $this->authenticate();
        
        RedisTracking::reset();
        return $this->json(true);
    }
}
