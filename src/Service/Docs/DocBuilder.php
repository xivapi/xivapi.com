<?php

namespace App\Service\Docs;

use Doctrine\ORM\EntityManagerInterface;

class DocBuilder
{
    /** @var EntityManagerInterface */
    protected $em;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    
    const KEYWORDS = [
        '{endpoint}' => 'https://xivapi.com'
    ];

    private $doc = [];
    private $headings = [];

    public function getHeadings()
    {
        return $this->headings;
    }

    protected function get()
    {
        return implode("\n", $this->doc);
    }

    protected function space(): DocBuilder
    {
        $this->doc[] = " ";
        return $this;
    }

    protected function add($text, $removeNewlines = true): DocBuilder
    {
        $text = $removeNewlines ? str_ireplace(PHP_EOL, ' ', $text) : $text;

        $text = str_ireplace(
            array_keys(self::KEYWORDS),
            self::KEYWORDS,
            $text
        );

        $this->doc[] = $text;
        return $this;
    }
    
    protected function startSection(): DocBuilder
    {
        $this->doc[] = '<div class="doc-section">';
        return $this;
    }
    
    protected function endSection(): DocBuilder
    {
        $this->doc[] = '</div>';
        return $this;
    }

    //
    // Markdown
    //

    protected function h1($title): DocBuilder
    {
        return $this->add("# {$title}")->space();
    }

    protected function h2($title): DocBuilder
    {
        return $this->add("## {$title}")->space();
    }

    protected function h3($title): DocBuilder
    {
        $anchor = str_ireplace(' ', '-', $title);
        
        return $this
            ->add('<a id="'. $anchor .'"></a>')
            ->add("### {$title} [#](#{$anchor})")->space();
    }

    protected function h4($title): DocBuilder
    {
        return $this->add("#### {$title}")->space();
    }

    protected function h5($title): DocBuilder
    {
        return $this->add("##### {$title}")->space();
    }

    protected function h6($title): DocBuilder
    {
        $this->headings[] = $title;
        $anchor = 'section-'. count($this->headings);
        
        return $this
            ->add('<a id="'. $anchor .'"></a>')
            ->add("<h6>{$title} <a href=\"#{$anchor}\">#</a></h6>")->space();
    }

    protected function text($text): DocBuilder
    {
        return $this->add(trim($text))->space();
    }

    protected function list($items): DocBuilder
    {
        foreach ($items as $text) {
            $this->add("- {$text}");
        }

        return $this->space();
    }

    protected function json($json): DocBuilder
    {
        $json = json_decode(trim($json));
        $json = json_encode($json, JSON_PRETTY_PRINT);
        $this->code($json, 'json');
        return $this;
    }

    protected function code($code, $language = ''): DocBuilder
    {
        return $this->add("```{$language}\n$code\n```", false)->space();
    }

    protected function bold($text): DocBuilder
    {
        return $this->add("**{$text}**")->space();
    }

    protected function italic($text): DocBuilder
    {
        return $this->add("*{$text}*")->space();
    }

    protected function table($headings, $rows): DocBuilder
    {
        $this->add('| ' . implode(" | ", $headings) .' |');
        $this->add('| ' . implode(" | ", array_map(function($a) { return "---"; }, $headings)) .' |');

        foreach ($rows as $row) {
            $this->add('| ' . implode(" | ", $row) .' |');
        }

        return $this->space();
    }

    protected function link($name, $url): DocBuilder
    {
        return $this->add("[{$name}]({$url})");
    }
    
    protected function image($name, $url): DocBuilder
    {
        return $this->add("![{$name}]({$url})")->space();
    }

    protected function note($text): DocBuilder
    {
        return $this->add("> **NOTE:** {$text}")->space();
    }

    protected function line(): DocBuilder
    {
        return $this->add("---")->space();
    }

    protected function gap($length = 1): DocBuilder
    {
        foreach (range(1, $length) as $i) {
            $this->text('&nbsp;');
        }

        return $this;
    }

    //
    // Custom
    //
    protected function queryParams($params): DocBuilder
    {
        return $this->table(
            ['Param', 'Details'],
            $params
        );
    }

    protected function usage($text, $keyRequired = false): DocBuilder
    {
        $this->text("**Usage** ⇢ {$text}");

        if ($keyRequired) {
            $this->note('This endpoint requires a **developer key** to access. Create one under **Applications**.');
        }

        return $this;
    }

    protected function route($route): DocBuilder
    {
        $this->doc[] = "## `{$route}`";
        $this->doc[] = ' ';

        return $this;
    }

    protected function auto($values): DocBuilder
    {
        return $this->table(
            [ 'Type', 'per Queue', 'per Minute' ],
            $values
        );
    }

    protected function terms(): DocBuilder
    {
        return $this
            ->h5('Terminology')
            ->table(
                [ 'Term', 'Meaning' ],
                [
                    [ '**Queues**', 'Lodestone content (characters, fc, ls, pvp team) have an auto-update cronjob schedule that runs every minute to keep them up to date. A single queue will process a set number of pages (usually around 100). A server can run multiple queues, usually about 4 of them.' ]
                ]
            );
    }
    
    protected function states(): DocBuilder
    {
        return $this
            ->table(
            [ 'State', 'Value', 'Details' ],
            [
                [ 'STATE_NONE', '`0`', 'Content is not on XIVAPI and will not be added via this request' ],
                [ 'STATE_ADDING', '`1`', 'Content does not exist on the API and needs adding. The `Payload` should be empty if this state is provided. It *should* take 2 minutes or less to add the content.' ],
                [ 'STATE_CACHED', '`2`', 'Content exists in the system and you\'re being provided a cached response.' ],
                [ 'STATE_NOT_FOUND', '`3`', 'Content does not exist on The Lodestone.' ],
                [ 'STATE_BLACKLIST', '`4`', 'Content has been Blacklisted. No data can be obtained via the API for any application '],
                [ 'STATE_PRIVATE', '`5`', 'Content is private on lodestone, ask the owner to make the content public and then try again!' ],
            ]
        );
    }

    protected function lodestoneNotice(): DocBuilder
    {
        return $this->note('All routes marked with `*` will query the lodestone directly in real-time. 
            XIVAPI will cache the lodestone response for a set amount of time. Please do not hammer 
            these requests or your IP will be blacklisted from the service.');
    }
}
