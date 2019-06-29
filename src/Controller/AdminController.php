<?php

namespace App\Controller;

use App\Common\Game\GameServers;
use App\Common\User\Users;
use App\Entity\CompanionToken;
use App\Service\API\ApiPermissions;
use App\Service\Companion\CompanionErrorHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    
    /**
     * @Route("/admin")
     */
    public function index()
    {
        $user = $this->users->getUser(true);
        $user->mustBeAdmin();
        
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
        
        return $this->render('admin/home.html.twig', [
            'token_servers' => $tokenServers,
            'valid_servers' => $validServers,
            'datacenter'  => GameServers::LIST_DC
        ]);
    }

    /**
     * @Route("/admin/companion")
     */
    public function home()
    {
        $user = $this->users->getUser(true);
        $user->mustBeAdmin();
        
        date_default_timezone_set("Europe/London");

        $user = $this->users->getUser(true);
        ApiPermissions::set($user->getPermissions());
        ApiPermissions::must(ApiPermissions::PERMISSION_ADMIN);

        $errors     = $this->ceh->getExceptions(20000);
        $lastError  = $errors[0];
        $errorGraph = [];
        $exception  = [];
        
        foreach ($errors as $error) {
            $index = date('Y-m-d', $error['Added']);

            if (isset($errorGraph[$index])) {
                $errorGraph[$index] = $errorGraph[$index] + 1;
            }

            $exception[$error['Exception']] = isset($exception[$error['Exception']])
                ? $exception[$error['Exception']] + 1 : 1;
        }

        krsort($errorGraph);
        $errorGraph = array_reverse($errorGraph);
        array_splice($errorGraph, 0, 60);

        return $this->render('admin/companion.html.twig', [
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
