<?php

namespace App\Service\Docs;

use Doctrine\ORM\EntityManagerInterface;

class Docs
{
    /** @var EntityManagerInterface */
    private $em;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    
    const LIST = [
        'Welcome'       => Welcome::class,
        'Search'        => Search::class,
        'Content'       => Content::class,
        
        'Character'     => Character::class,
        'FreeCompany'   => FreeCompany::class,
        'Linkshell'     => Linkshell::class,
        'PvPTeam'       => PvPTeam::class,

        'Market'        => Market::class,

        'MapData'       => MapData::class,
        
        'Lodestone'     => Lodestone::class,
        'PatchList'     => PatchList::class,
    ];

    public function getMarkdown($filename = null)
    {
        $filename = self::LIST[$filename] ?? self::LIST['Welcome'];

        /** @var Welcome $doc */
        $doc      = new $filename($this->em);
        $markdown = (new \Parsedown())->text($doc->build());
    
        // add some css classes
        $markdown = str_ireplace([
            '<table>',
            '<blockquote>',
            '</blockquote>'
        ],[
            '<table class="table table-sm table-bordered">',
            '<div class="alert alert-secondary" role="alert">',
            '</div>',
        ],
        $markdown);
        
        return [
            'html'      => $markdown,
            'headings'  => $doc->getHeadings(),
        ];
    }
}
