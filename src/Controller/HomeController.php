<?php

namespace App\Controller;

use App\Service\Common\SiteVersion;
use App\Service\Companion\CompanionStatistics;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends Controller
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
     * @Route("/companion/statistics", name="companion_statistics")
     */
    public function companionStatistics()
    {
        return $this->render('companion_statistics.html.twig', [
            'companion_statistics' => $this->companionStatistics->getRecordedStatistics(),
            'companion_exceptions' => $this->companionStatistics->getExceptions()
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
