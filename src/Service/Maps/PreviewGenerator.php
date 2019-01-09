<?php

namespace App\Service\Maps;

use App\Entity\MapPosition;
use App\Repository\MapPositionRepository;
use App\Service\Redis\Cache;
use Doctrine\ORM\EntityManagerInterface;
use Intervention\Image\ImageManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PreviewGenerator
{
    const PUBLIC_FOLDER = __DIR__ .'/../../../public';
    const MAX_PER_BATCH = 30;

    /** @var EntityManagerInterface */
    private $em;
    /** @var Cache */
    private $cache;
    /** @var ImageManager */
    private $image;
    /** @var SymfonyStyle */
    private $io;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em    = $em;
        $this->cache = new Cache();
        $this->image = new ImageManager([
            'driver' => 'imagick',
        ]);
    }

    public function setSymfonyStyle(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * Generate preview images of map positions for monsters, npcs, gathering, etc.
     */
    public function generate()
    {
        /** @var MapPositionRepository $repo */
        $repo = $this->em->getRepository(MapPosition::class);

        $this->generateMonsterPreviews($repo);
    }

    /**
     * generate monster preview images
     */
    private function generateMonsterPreviews(MapPositionRepository $repo)
    {
        $this->io->section(__METHOD__);

        /** @var MapPosition[] $positions */
        $positions = $repo->findBy([
            'Type'    => 'Monster',
            'Managed' => false,
        ]);


        // group by id and maps
        $list = [];
        foreach ($positions as $pos) {
            $list[$pos->getBNpcNameID()][$pos->getMapID()][] = [
                $pos->getPixelX(),
                $pos->getPixelY(),
            ];
        }

        $this->io->text(number_format(count($list)) .' BNpcName entries');
        foreach ($list as $bnpcNameId => $positions) {
            /**
             * @var int $mapId
             * @var array $coordinates
             */
            $this->io->text("Creating map previews for: BNPCName = {$bnpcNameId}, total maps: ". count($positions));
            foreach ($positions as $mapId => $coordinates) {
                // get aetherytes
                $teleports = $repo->findBy([
                    'Type'  => 'Aetheryte',
                    'MapID' => $mapId,
                ], [], self::MAX_PER_BATCH);

                // skip, there is no mapID 0 but Heaven on High returns a 0, bit tricky.
                if ($mapId == 0) {
                    continue;
                }

                // get map file
                // todo - move this to redis (at work no redis XD)
                $json = json_decode(file_get_contents('https://xivapi.com/Map/'. $mapId .'?pretty=1'));
                $file = $json->MapFilename;

                // read image
                $this->io->text("Building preview for: {$bnpcNameId} {$mapId} on {$file}");
                $img = $this->image->make("https://xivapi.com/{$file}");

                // render teleports
                /** @var MapPosition $tele */
                foreach ($teleports as $tele) {
                    $aetherFilename = self::PUBLIC_FOLDER .'/img-misc/mappy/aetheryte.png';

                    $info = getimagesize($aetherFilename);;
                    $x = $tele->getPixelX() - round(($info[0] / 2));
                    $y = $tele->getPixelY() - round(($info[1] / 2));;

                    $img->insert($aetherFilename, null, $x, $y);
                }

                // set
                $a = 30000;
                $b = 0;
                $c = 30000;
                $d = 0;

                // add positions
                foreach ($coordinates as $xy) {
                    [$x, $y] = $xy;

                    // render enemy circle
                    $img->insert(self::PUBLIC_FOLDER .'/img-misc/mappy/enemy_2x.png', null, $x, $y);

                    // compare against grid
                    $a = ($x < $a) ? $x : $a;
                    $b = ($x > $b) ? $x : $b;
                    $c = ($y < $c) ? $y : $c;
                    $d = ($y > $d) ? $y : $d;
                }

                // add padding around position framing
                $a = $a-200;
                $c = $c-200;
                $b = $b+200;
                $d = $d+200;

                // Crop
                $width  = $b - $a;
                $height = $d - $c;
                $img->crop($width, $height, $a, $c);

                // Resize image in half
                $img->resize($width / 2, $height / 2);
                $img->sharpen(2);

                // save
                $filename = self::PUBLIC_FOLDER ."/mp/{$bnpcNameId}_{$mapId}.jpg";
                $img->save($filename, 100);

                // todo - save to BNpcNameAdditional redis entry
                // todo - Modify content room to look for <ContentName>Additional entries and append on the data
            }
        }
    }
}
