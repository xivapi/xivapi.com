<?php

namespace App\Controller;

use App\Common\Controller\UserTraitController;
use App\Common\User\Users;
use App\Common\Utils\Random;
use App\Service\API\ApiRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AccountController extends AbstractController
{
    use UserTraitController;
    
    /** @var EntityManagerInterface $em */
    private $em;
    /** @var Users */
    private $users;
    /** @var Session */
    private $session;

    public function __construct(
        EntityManagerInterface $em,
        SessionInterface $session,
        Users $users
    ) {
        $this->em      = $em;
        $this->users   = $users;
        $this->session = $session;
    }

    /**
     * @Route("/account", name="account")
     */
    public function index()
    {
        return $this->render('account/index.html.twig', [
            'api_key_limits' => [
                'MAX_RATE_LIMIT_KEY'    => ApiRequest::MAX_RATE_LIMIT_KEY,
                'MAX_RATE_LIMIT_GLOBAL' => ApiRequest::MAX_RATE_LIMIT_GLOBAL,
            ]
        ]);
    }

    /**
     * @Route("/account/regenerate-key", name="account_regen_key")
     */
    public function regenerateKey()
    {
        $user = $this->users->getUser(true);

        $user->setApiPublicKey(
            Random::randomAccessKey()
        );

        $this->users->save($user);
        return $this->redirectToRoute('account');
    }
    
    /**
     * @Route("/account/save/google-analytics-id", name="account_save_google_analytics_id")
     */
    public function saveGoogleAnalyticsId(Request $request)
    {
        $user = $this->users->getUser(true);
        $user->setApiAnalyticsKey($request->get('google_analytics_key'));
        $this->users->save($user);
        return $this->redirectToRoute('account');
    }
}
