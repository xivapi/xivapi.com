<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\User\Users;
use App\Service\User\SSO\DiscordSignIn;

class AccountController extends AbstractController
{
    /** @var EntityManagerInterface $em */
    private $em;
    /** @var Users */
    private $users;
    /** @var Session */
    private $session;
    
    public function __construct(EntityManagerInterface $em, SessionInterface $session, Users $users)
    {
        $this->em      = $em;
        $this->users   = $users;
        $this->session = $session;
    }

    /**
     * @Route("/account", name="account")
     */
    public function index()
    {
        return $this->render('account/index.html.twig');
    }
    
    /**
     * @Route("/account/logout", name="account_logout")
     */
    public function logout()
    {
        $this->users->deleteCookie();
        return $this->redirectToRoute('home');
    }
    
    /**
     * @Route("/account/login/discord", name="account_login_discord")
     */
    public function loginDiscord(Request $request)
    {
        $url = $this->users->setSsoProvider(new DiscordSignIn($request))->signIn();
        return $this->redirect($url);
    }
    
    /**
     * @Route("/account/login/discord/success", name="account_login_discord_success")
     */
    public function loginDiscordResponse(Request $request)
    {
        if ($request->get('error') == 'access_denied') {
            return $this->redirectToRoute('account');
        }
        
        $this->users->setSsoProvider(new DiscordSignIn($request))->authenticate();
        return $this->redirectToRoute('account');
    }
    
    
}
