<?php

namespace App\Controller;

use App\Service\API\ApiPermissions;
use App\Service\Companion\CompanionErrorHandler;
use App\Service\User\Users;
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
        $user = $this->users->getUser(true);
        ApiPermissions::set($user->getApiPermissions());
        ApiPermissions::must(ApiPermissions::PERMISSION_ADMIN);

        $errors     = $this->ceh->getExceptions(500);
        $errorGraph = [];

        foreach ($errors as $error) {
            $hour = date('z-H', $error['Added']);
            $errorGraph[$hour] = isset($errorGraph[$hour]) ? $errorGraph[$hour] + 1 : 1;
        }

        return $this->render('admin/index.html.twig', [
            'errorGraph' => [
                'keys'   => array_keys($errorGraph),
                'values' => array_values($errorGraph)
            ]
        ]);
    }
}
