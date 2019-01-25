<?php

namespace App\Controller;

use App\Form\AppGoogleForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use App\Service\User\UserService;
use App\Service\User\SSO\DiscordSignIn;
use App\Entity\UserApp;
use App\Entity\MapCompletion;
use App\Entity\MapPosition;
use App\Entity\User;
use App\Form\AppForm;
use App\Repository\MapPositionRepository;
use App\Service\Apps\AppManager;
use App\Service\Redis\Cache;

class ApplicationsController extends Controller
{
    /** @var EntityManagerInterface $em */
    private $em;
    /** @var UserService */
    private $userService;
    /** @var Session */
    private $session;
    /** @var AppManager */
    private $apps;
    /** @var Cache */
    private $cache;
    
    public function __construct(
        EntityManagerInterface $em,
        UserService $userService,
        SessionInterface $session,
        AppManager $apps,
        Cache $cache
    ) {
        $this->em          = $em;
        $this->userService = $userService;
        $this->session     = $session;
        $this->apps        = $apps;
        $this->cache       = $cache;
    }

    /**
     * @Route("/app", name="app")
     */
    public function index()
    {
        return $this->render('app/index.html.twig');
    }
    
    /**
     * @Route("/app/logout", name="app_logout")
     */
    public function logout()
    {
        $this->userService->deleteCookie();
        return $this->redirectToRoute('home');
    }
    
    /**
     * @Route("/app/login/discord", name="app_login_discord")
     */
    public function loginDiscord(Request $request)
    {
        $url = $this->userService->setSsoProvider(new DiscordSignIn($request))->signIn();
        return $this->redirect($url);
    }
    
    /**
     * @Route("/app/login/discord/success", name="app_login_discord_success")
     */
    public function loginDiscordResponse(Request $request)
    {
        if ($request->get('error') == 'access_denied') {
            return $this->redirectToRoute('app');
        }
        
        $this->userService->setSsoProvider(new DiscordSignIn($request))->authenticate();
        return $this->redirectToRoute('app');
    }
    
    /**
     * @Route("/app/{id}", name="app_manage")
     */
    public function manage(Request $request, string $id)
    {
        if ($request->get('regen')) {
            $message = 'Your API key has been regenerated, the new one can be seen below.';
        }

        /** @var User $user */
        $user = $this->userService->getUser();

        if (!$user || ($id === 'new' && count($user->getApps()) >= $user->getAppsMax())) {
            return $this->redirectToRoute('app');
        }
        
        $user->checkBannedStatusAndRedirectUserToDiscord();

        if ($id === 'new') {
            $app = $this->apps->create();
            return $this->redirectToRoute('app_manage', [ 'id' => $app->getId() ]);
        }

        /** @var UserApp $app */
        $app = $this->apps->get($id);
        if (!$app || $app->getUser()->getId() !== $user->getId()) {
            throw new NotFoundHttpException('Application not found');
        }

        $form = $this->createForm(AppForm::class, $app);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($app);
            $this->em->flush();
            $message = 'Application information has been updated!';
        }

        return $this->render('app/app_form.html.twig', [
            'app'     => $app,
            'form'    => $form->createView(),
            'message' => $message ?? false,
        ]);
    }
    
    /**
     * @Route("/app/{id}/analytics", name="app_analytics")
     */
    public function analytics(Request $request, string $id)
    {
        /** @var User $user */
        $user = $this->userService->getUser();
    
        if (!$user || ($id === 'new' && count($user->getApps()) >= $user->getAppsMax())) {
            return $this->redirectToRoute('app');
        }
    
        $user->checkBannedStatusAndRedirectUserToDiscord();
    
        /** @var UserApp $app */
        $app = $this->apps->get($id);
        if (!$app || $app->getUser()->getId() !== $user->getId()) {
            throw new NotFoundHttpException('Application not found');
        }
    
        $form = $this->createForm(AppGoogleForm::class, $app);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($app);
            $this->em->flush();
            $message = 'Application information has been updated!';
        }
    
        return $this->render('app/app_google.html.twig', [
            'app'     => $app,
            'form'    => $form->createView(),
            'message' => $message ?? false,
        ]);
    }
    
    /**
     * @Route("/app/{id}/regenerate", name="app_regenerate")
     */
    public function appRegenerate(string $id)
    {
        /** @var User $user */
        $user = $this->userService->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app');
        }
    
        $user->checkBannedStatusAndRedirectUserToDiscord();
        
        /** @var UserApp $app */
        $app = $this->apps->get($id);
        
        if (!$app) {
            throw new NotFoundHttpException('Application not found');
        }
        
        if ($app->getUser()->getId() !== $user->getId()) {
            return $this->redirectToRoute('app');
        }
        
        // generate new key
        $app->generateApiKey();
        $this->em->persist($app);
        $this->em->flush();
        
        return $this->redirectToRoute('app_manage', [
            'id' => $id,
            'regen' => 1,
        ]);
    }
    
    /**
     * @Route("/app/{id}/delete", name="app_delete")
     */
    public function appDelete(string $id)
    {
        /** @var User $user */
        $user = $this->userService->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app');
        }
        
        $user->checkBannedStatusAndRedirectUserToDiscord();
        
        /** @var UserApp $app */
        $app = $this->apps->get($id);
        
        if (!$app) {
            throw new NotFoundHttpException('Application not found');
        }
        
        if ($app->getUser()->getId() !== $user->getId()) {
            return $this->redirectToRoute('app');
        }
        
        return $this->render('app/delete.html.twig', [
            'app' => $app,
            'url' => $this->generateUrl('app_delete_confirm', [
                'id' => $app->getId(),
            ]),
        ]);
    }
    
    /**
     * @Route("/app/{id}/delete/confirm", name="app_delete_confirm")
     */
    public function appDeleteConfirm(string $id)
    {
        /** @var User $user */
        $user = $this->userService->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app');
        }
    
        $user->checkBannedStatusAndRedirectUserToDiscord();
        
        /** @var UserApp $app */
        $app = $this->apps->get($id);
        
        if (!$app) {
            throw new NotFoundHttpException('Application not found');
        }
        
        if ($app->getUser()->getId() !== $user->getId()) {
            return $this->redirectToRoute('app');
        }
        
        $this->em->remove($app);
        $this->em->flush();
        
        return $this->redirectToRoute('app');
    }
}
