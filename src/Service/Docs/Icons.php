<?php

namespace App\Service\Docs;

class Icons
{
    const ROOT = __DIR__ . '/../../../public';

    const ICON_SETS = [
        'class_job_set_1'     => [ 'Class/Jobs Plain', '/cj/1' ],
        'class_job_companion' => [ 'Class/Jobs Companion', '/cj/companion' ],
        'class_job_svg'       => [ 'Class/Jobs SVG', '/cj/svg/ClassJob' ],
        'class_job_misc'      => [ 'Class/Jobs Misc', '/cj/misc' ],
        

        '---',

        'custom'        => [ 'Custom Icons', '/c' ],
        'fates'         => [ 'Fate Icons', '/f' ],
        'legacy'        => [ '1.0 Legacy UI', '/img-1.0' ],
        'languages'     => [ 'Language Flags', '/img-lang' ],
        'logos'         => [ 'Logos and Banners', '/img-logo' ],
        'companion'     => [ 'Companion App', '/img-misc/companion' ],
        'grandcompany'  => [ 'Grand Company Ranks', '/img-misc/gc' ],
        'gear'          => [ 'Gear Slot Icons', '/img-misc/gear' ],
        'lodestone'     => [ 'Lodestone UI', '/img-misc/lodestone' ],
        'mappy'         => [ 'Mappy Icons', '/img-misc/mappy' ],
        'misc'          => [ 'Misc', '/img-misc'],

        '---',

        // game icons (auto)
        // maps (auto)
    ];

    /**
     * Get a list of viewable icons
     */
    public function get($set = false)
    {
        if ($set) {
            return $this->getIconSet($set);
        }

        return $this->getIconSetList();
    }

    private function getIconSetList()
    {
        $sets = self::ICON_SETS;

        // add game icons
        foreach (scandir(self::ROOT .'/i/') as $dir) {
            if ($dir == '.' || $dir == '..' || !is_dir(self::ROOT .'/i/'.$dir)) continue;

            $sets['icons'. $dir] = [ "Game Icons - Set: {$dir}", "/i/{$dir}/" ];
        }

        $sets[] = '---';

        // add maps
        foreach (scandir(self::ROOT .'/m/') as $dir) {
            if ($dir == '.' || $dir == '..' || !is_dir(self::ROOT .'/m/'.$dir)) continue;

            $sets['maps'. $dir] = [ "Game Maps - Territory: {$dir}", "/m/{$dir}/" ];
        }

        return $sets;
    }

    /**
     * Get icons for a specific set
     */
    private function getIconSet(string $set)
    {
        $setList = $this->getIconSetList();

        if (!isset($setList[$set])) {
            die("Why you trying to hack my site? :( you die now.");
        }

        [$name, $path] = $setList[$set];

        $icons = [];
        foreach (scandir(self::ROOT . $path) as $filename) {
            $pi = pathinfo($filename);

            if (!isset($pi['extension']) || !in_array($pi['extension'], ['png', 'jpg', 'gif', 'svg'])) {
                continue;
            }

            $icons[] = [
                'url'   => str_ireplace('//', '/', "{$path}/{$filename}"),
                'name'  => $pi['filename'],
                'ext'   => $pi['extension'],
                'size'  => $this->getImageFilesize(self::ROOT . "/{$path}/{$filename}"),
                'res'   => $this->getImageResolution(self::ROOT . "/{$path}/{$filename}")
            ];
        }

        return [
            'name'  => $name,
            'icons' => $icons
        ];
    }

    private function getImageFilesize($filename)
    {
        $size = filesize($filename);

        $mod = 1024;
        $units = explode(' ','B KB MB GB TB PB');
        for ($i = 0; $size > $mod; $i++) {
            $size /= $mod;
        }
        return round($size, 2) . ' ' . $units[$i];
    }

    private function getImageResolution($image)
    {
        $size = getimagesize($image);

        return [
            'w' => $size[0],
            'h' => $size[1],
        ];
    }

    /**
     * Download some icons
     */
    public function downloadIconSet(string $set)
    {
        $icons = $this->getIconSet($set);

        $zipFilename = 'downloads/xivapi_'. $set .'.zip';

        $zip = new \ZipArchive();
        $zip->open($zipFilename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($icons['icons'] as $icon) {
            $zip->addFile(self::ROOT . $icon['url'], $icon['name'] .'.'. $icon['ext']);
        }

        $zip->close();

        return self::ROOT . '/'. $zipFilename;
    }
}
