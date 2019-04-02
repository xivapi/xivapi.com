<?php

namespace App\Controller;

use App\Service\Common\SiteVersion;
use App\Service\Companion\CompanionStatistics;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /** @var CompanionStatistics */
    private $companionStatistics;

    public function __construct(CompanionStatistics $companionStatistics)
    {
        $this->companionStatistics = $companionStatistics;
    }

    /**
     * @Route("/", name="home")
     */
    public function home()
    {
        return $this->render('home.html.twig');
    }

    /**
     * @Route("/discord", name="discord")
     */
    public function discord()
    {
        return $this->redirect('https://discord.gg/MFFVHWC', 301);
    }

    /**
     * @Route("/companion/statistics", name="companion_statistics")
     */
    public function companionStatistics()
    {
        return $this->json([
            'statistics' => $this->companionStatistics->getStatistics(),
            'exceptions' => $this->companionStatistics->getExceptions()
        ]);
    }

    /**
     * @Route("/version")
     */
    public function version()
    {
        $ver = SiteVersion::get();
        return $this->json([
            'Version'   => $ver->version,
            'Hash'      => $ver->hash,
            'Timestamp' => $ver->time
        ]);
    }
}
