<?php

namespace App\Controller;

use App\Common\Entity\User;
use App\Common\Game\GameServers;
use App\Common\Service\Redis\Redis;
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

        $dailyhits = [];

        foreach (range(0, 23) as $hour) {
            $dailyhits[$hour] = (int)Redis::cache()->getCount('stat_requests_'. $hour);
        }
        
        return $this->render('admin/home.html.twig', [
            'daily_hits' => $dailyhits,
            'daily_total' => array_sum($dailyhits),
            'total_hits' => Redis::cache()->getCount('stats_total'),
            'total_date' => Redis::cache()->get('stat_date')
        ]);
    }
    
    /**
     * @Route("/admin/companion", name="admin_companion")
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
        
        return $this->render('admin/companion.html.twig', [
            'token_servers' => $tokenServers,
            'valid_servers' => $validServers,
            'datacenter'  => GameServers::LIST_DC,

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
}
