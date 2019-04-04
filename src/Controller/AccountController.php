<?php

namespace App\Controller;

use App\Service\API\ApiRequest;
use App\Service\ThirdParty\Patreon\Patreon;
use App\Service\User\SignInDiscord;
use App\Utils\Random;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\User\Users;

class AccountController extends AbstractController
{
    /** @var EntityManagerInterface $em */
    private $em;
    /** @var Users */
    private $users;
    /** @var Session */
    private $session;
    /** @var Patreon */
    private $patreon;
    
    public function __construct(
        EntityManagerInterface $em,
        SessionInterface $session,
        Users $users,
        Patreon $patreon
    ) {
        $this->em      = $em;
        $this->users   = $users;
        $this->session = $session;
        $this->patreon = $patreon;
    }

    /**
     * @Route("/account", name="account")
     */
    public function index()
    {
        return $this->render('account/index.html.twig', [
            'api_key_limits' => [
                'MAX_RATE_LIMIT_KEY' => ApiRequest::MAX_RATE_LIMIT_KEY,
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

    /**
     * @Route("/account/login/discord", name="account_login_discord")
     */
    public function loginDiscord(Request $request)
    {
        return $this->redirect(
            $this->users->setSsoProvider(new SignInDiscord($request))->login()
        );
    }
    
    /**
     * @Route("/account/login/discord/success", name="account_login_discord_success")
     */
    public function loginDiscordResponse(Request $request)
    {
        if ($request->get('error') == 'access_denied') {
            return $this->redirectToRoute('home');
        }

        $this->users->setSsoProvider(new SignInDiscord($request))->authenticate();
        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/account/logout", name="account_logout")
     */
    public function logout()
    {
        $this->users->logout();
        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/patreon", name="account_patreon")
     */
    public function patreon(Request $request)
    {
        $oAuthUrl = $this->patreon->generateLoginUri($request);

        return $this->render('account/patreon.html.twig', [
            'patreon_login_url' => $oAuthUrl
        ]);
    }

    /**
     * @Route("/account/patreon/login", name="account_patreon_login")
     */
    public function patreonLogin(Request $request)
    {
        $this->patreon->handlePatreonOAuthCode($request);

    }

    /**
     * @Route("/account/patreon/success", name="account_patreon_success")
     */
    public function patreonSuccess()
    {

    }
}
