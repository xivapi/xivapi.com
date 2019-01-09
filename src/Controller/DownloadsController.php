<?php

namespace App\Controller;

use App\Entity\MapPosition;
use App\Entity\MemoryData;
use App\Service\Common\Arrays;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Routing\Annotation\Route;

class DownloadsController extends Controller
{
    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/downloads/xivapi-map-data")
     */
    public function mapDataDownload()
    {
        $filename = __DIR__.'/xivapi-map-data.csv';

        Arrays::repositoryToCsv(
            $this->em->getRepository(MapPosition::class),
            $filename
        );

        return $this->file(new File($filename));
    }

    /**
     * @Route("/downloads/xivapi-memory-data")
     */
    public function memoryDataDownload()
    {
        $filename = __DIR__.'/xivapi-memory-data.csv';

        Arrays::repositoryToCsv(
            $this->em->getRepository(MemoryData::class),
            $filename
        );

        return $this->file(new File($filename));
    }
}
