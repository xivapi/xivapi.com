<?php

namespace App\Common\Controller;

use App\Common\User\SignInDiscord;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

trait UserTraitController
{
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
}
