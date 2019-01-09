<?php

namespace App\Service\Apps;

use App\Entity\App;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class AppManager
{
    /** @var EntityManagerInterface $em */
    private $em;
    /** @var UserService $userService */
    private $userService;

    public function __construct(EntityManagerInterface $em, UserService $userService)
    {
        $this->em = $em;
        $this->userService = $userService;
    }

    /**
     * Fetch an API app from the request, if $keyRequired is set then
     * an exception is thrown if no key is provided (eg the endpoint
     * requires a key to be accessed)
     */
    public function fetch(Request $request)
    {
        return $this->em->getRepository(App::class)->findOneBy([
            'apiKey' => $request->get('key')
        ]);
    }

    /**
     * Fetch an App via its ID
     */
    public function get(string $id)
    {
        return $this->em->getRepository(App::class)->findOneBy([ 'id' => $id ]);
    }
    
    /**
     * Fetch an app via its key
     */
    public function getByKey(?string $id)
    {
        if (empty($id)) {
            return null;
        }

        $id = strtolower(trim($id));
        return $this->em->getRepository(App::class)->findOneBy([
            'apiKey' => $id
        ]);
    }

    /**
     * Create a new App
     */
    public function create()
    {
        $user = $this->userService->getUser();

        if (!$user) {
            throw new \Exception('Not logged in');
        }

        $app = new App();
        $app->setUser($user)
            ->setName('App #'. (count($user->getApps()) + 1))
            ->setLevel(2)
            ->setApiRateLimit(5);

        $this->em->persist($app);
        $this->em->flush();
        return $app;
    }
}
