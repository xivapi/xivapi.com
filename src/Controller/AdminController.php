<?php

namespace App\Controller;

use App\Common\User\Users;
use App\Service\API\ApiPermissions;
use App\Service\Companion\CompanionErrorHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    /** @var Users */
    private $users;

    /** @var CompanionErrorHandler */
    private $ceh;

    public function __construct(
        CompanionErrorHandler $ceh,
        Users $users
    ) {
        $this->ceh = $ceh;
        $this->users = $users;
    }

    /**
     * @Route("/admin/companion")
     */
    public function home()
    {
        date_default_timezone_set("Europe/London");

        $user = $this->users->getUser(true);
        ApiPermissions::set($user->getPermissions());
        ApiPermissions::must(ApiPermissions::PERMISSION_ADMIN);

        $errors     = $this->ceh->getExceptions(500);
        $lastError  = $errors[0];
        $errorGraph = [
            date('dS H', time()) => 0
        ];
        $exception  = [];

        foreach (range(0,100) as $hour) {
            $seconds = time() - (3600 * $hour);
            $hour    = date('dS H', $seconds);
            $errorGraph[$hour] = 0;
        }

        foreach ($errors as $error) {
            $hour = date('dS H', $error['Added']);

            if (isset($errorGraph[$hour])) {
                $errorGraph[$hour] = $errorGraph[$hour] + 1;
            }

            $exception[$error['Exception']] = isset($exception[$error['Exception']])
                ? $exception[$error['Exception']] + 1 : 1;
        }

        krsort($errorGraph);

        $errorGraph = array_reverse($errorGraph);

        return $this->render('admin/index.html.twig', [
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
                'values' => array_values($errorGraph)
            ]
        ]);
    }
}
