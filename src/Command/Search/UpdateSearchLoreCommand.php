<?php

namespace App\Command\Search;

use App\Command\CommandHelperTrait;
use App\Service\Common\Language;
use App\Service\Data\CsvReader;
use App\Service\Data\SaintCoinach;
use App\Service\Redis\Redis;
use App\Service\SearchElastic\ElasticSearch;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateSearchLoreCommand extends Command
{
    use CommandHelperTrait;
    
    /** @var ElasticSearch */
    private $elastic;
    /** @var array */
    private $entries;
    /** @var int */
    private $count = 0;
    
    protected function configure()
    {
        $this
            ->setName('UpdateSearchLoreCommand')
            ->setDescription('Deploy all lore-finder data to live!')
            ->addArgument('environment',  InputArgument::OPTIONAL, 'prod OR dev')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this
            ->setSymfonyStyle($input, $output)
            ->title('LORE FINDER')
            ->startClock();

        // connect to production cache
        [$ip, $port] = (in_array($input->getArgument('environment'), ['prod','staging']))
            ? explode(',', getenv('ELASTIC_SERVER_PROD'))
            : explode(',', getenv('ELASTIC_SERVER_LOCAL'));
        
        if ($input->getArgument('environment') == 'prod') {
            $this->io->success('DEPLOYING TO PRODUCTION');
        }
        
        $this->elastic = new ElasticSearch($ip, $port);
        
        // recreate index
        $this->elastic->deleteIndex('lore_finder');
        $this->elastic->addIndex('lore_finder');
    
        /*
         * todo:
         * - CraftLeveTalk
         * - Completion
         * - GoldSaucerTalk
         * - GuildleveAssignmentTalk
         * - LogMessage - needs cleaning up
         * - SkyIsland2Mission
         * - SkyIsland2MissionDetail
         */
    
        //
        // Generic lore importers, these are very simple to handle
        //
        $this->addGeneric('TripleTriadCard', 'Description');
        $this->addGeneric('Status', 'Description');
        $this->addGeneric('PublicContentTextData', 'TextData');
        $this->addGeneric('EventItemHelp', 'Description');
        $this->addGeneric('Leve', 'Description');
        $this->addGeneric('Item', 'Description');
        $this->addGeneric('Achievement', 'Description');
        $this->addGeneric('Balloon', 'Dialogue');
        $this->addGeneric('InstanceContentTextData', 'Text');
        $this->addGeneric('ContentFinderConditionTransient', 'Description');
        $this->addGeneric('NpcYell', 'Text');
        $this->addGeneric('DefaultTalk', 'Text0');
        $this->addGeneric('DefaultTalk', 'Text1');
        $this->addGeneric('DefaultTalk', 'Text2');
        $this->addGeneric('Fate', 'Description');
        $this->addGeneric('Fate', 'Objective');
        $this->addGeneric('Fate', 'StatusText0');
        $this->addGeneric('Fate', 'StatusText1');
        $this->addGeneric('Fate', 'StatusText2');
        $this->addGeneric('Mount', 'Description');
        $this->addGeneric('Mount', 'DescriptionEnhanced');
        $this->addGeneric('Mount', 'Tooltip');
        $this->addGeneric('Companion', 'Description');
        $this->addGeneric('Companion', 'DescriptionEnhanced');
        $this->addGeneric('Companion', 'Tooltip');
        
        //
        // Complex lore importers, usually need special handling
        //
        
        $this->addCutscene();
        $this->addQuestDialogue();
        
        $this->addBulkEntries(true);
        $this->io->text("Total lore entries: {$this->count}");
        $this->complete()->endClock();
    }
    
    /**
     * Add an entry to elastic search
     */
    private function addEntry($context, $text, $source, $data)
    {
        // ignore empties
        if (!isset($text['en']) || empty($text['en'])) {
            return;
        }
        
        $id = Uuid::uuid4()->toString();
        
        $this->count++;
        $this->entries[$id] = [
            'ID'        => $id,
            'Text_en'   => strip_tags($text['en']),
            'Text_de'   => strip_tags($text['de']),
            'Text_fr'   => strip_tags($text['fr']),
            'Text_ja'   => strip_tags($text['ja']),
            'Text_cn'   => null,
            'Text_kr'   => null,
            'Context'   => $context,
            'Source'    => $source[0],
            'SourceID'  => $source[1],
            'Data'      => $data,
        ];
        
        $this->addBulkEntries();
    }
    
    /**
     * Add entries in bulks of 50
     */
    private function addBulkEntries($force = false)
    {
        if ($force === false && count($this->entries) < (ElasticSearch::MAX_BULK_DOCUMENTS * 4)) {
            return;
        }
        
        $entries = json_decode(json_encode($this->entries), true);
        $this->entries = [];
        $this->elastic->bulkDocuments('lore_finder', 'search', $entries);
    }
    
    
    /**
     * Generic content handler
     */
    private function addGeneric($contentName, $fieldName)
    {
        $this->io->text(__METHOD__ ." = {$contentName} : {$fieldName}");
        $ids = Redis::Cache()->get("ids_{$contentName}");
        $this->io->progressStart(count($ids));
    
        $total = count($ids);
        $missing = 0;
        foreach ($ids as $id) {
            $this->io->progressAdvance();
            
            // grab content object
            $object = Redis::Cache()->get("xiv_{$contentName}_{$id}");
            $source = [$contentName, $id];
        
            // we only care for things where the field exists and isn't empty
            if (!isset($object->{"{$fieldName}_en"}) || empty($object->{"{$fieldName}_en"})) {
                $missing++;
                continue;
            }
    
            $text = [
                'en' => null,
                'de' => null,
                'fr' => null,
                'ja' => null,
            ];
            
            // build multi-language array
            foreach(Language::LANGUAGES_ACTIVE as $lang) {
                $text[$lang] =  $object->{"{$fieldName}_{$lang}"};
            }

            $data = [
                'ID'          => $object->ID,
                'GamePatchID' => $object->GamePatch->ID ?? null,
                'Name_en'     => $object->Name_en ?? null,
                'Name_de'     => $object->Name_de ?? null,
                'Name_fr'     => $object->Name_fr ?? null,
                'Name_ja'     => $object->Name_ja ?? null,
                'Name_kr'     => $object->Name_kr ?? null,
                'Name_cn'     => $object->Name_cn ?? null,
                'Icon'        => $object->Icon ?? null,
                'Url'         => $object->Url ?? null,
            ];
    
            // add entry
            $this->addEntry("{$contentName}_{$fieldName}", $text, $source, $data);
        }
    
        $this->io->progressFinish();
        
        if ($missing > ($total * 0.8)) {
            $this->io->text("<info>Missing: {$missing}/{$total}</info>");
        }
    }
    
    /**
     * Questz, has lots of different text types
     */
    private function addQuestDialogue()
    {
        $this->io->text(__METHOD__);
        $ids = Redis::Cache()->get('ids_Quest');
        $this->io->progressStart(count($ids));
        
        foreach ($ids as $id) {
            $this->io->progressAdvance();
            $object = Redis::Cache()->get("xiv_Quest_{$id}");
            $source = ['Quest', $id];
            
            if (empty($object->TextData_en)) {
                continue;
            }
            
            $textDataType = [
                'Dialogue',
                'Journal',
                'Scene',
                'ToDo',
                'Pop',
                'Access',
                'Instance',
                'System',
                'Todo',
                'BattleTalk',
                'QA_Question',
                'QA_Answer'
            ];
            
            foreach($textDataType as $type) {
                if (isset($object->TextData_en->{$type})) {
                    $text = [
                        'en' => null,
                        'de' => null,
                        'fr' => null,
                        'ja' => null,
                    ];
                    
                    foreach(Language::LANGUAGES_ACTIVE as $lang) {
                        if (!isset($object->{"TextData_{$lang}"}->{$type})) {
                            continue;
                        }
                        
                        foreach($object->{"TextData_{$lang}"}->{$type} as $dialogue) {
                            $text[$lang] = $dialogue->Text;
                        }
                    }
    
                    $this->addEntry('Quest_'. $type, $text, $source, null);
                }
            }
        }
    
        $this->io->progressFinish();
    }
    
    /**
     * Not in API at the moment, so go directly to the file.
     */
    private function addCutscene()
    {
        $this->io->text(__METHOD__);
        
        $dirs = SaintCoinach::directory() . "/raw-exd-all/cut_scene";
        $dirs = glob("{$dirs}/*" , GLOB_ONLYDIR);
        
        $this->io->progressStart(count($dirs));
        foreach ($dirs as $folder) {
            $this->io->progressAdvance();
            $files = array_diff(scandir($folder), ['.', '..']);
            
            // remove none .en files as we will handle it manually
            foreach ($files as $i => $file) {
                if (stripos($file, '.en.') === false) {
                    unset($files[$i]);
                }
            }
            
            foreach ($files as $file) {
                $en = CsvReader::Get($folder ."/". $file, true);
                $de = CsvReader::Get($folder ."/". str_ireplace('.en.', '.de.', $file), true);
                $fr = CsvReader::Get($folder ."/". str_ireplace('.en.', '.fr.', $file), true);
                $ja = CsvReader::Get($folder ."/". str_ireplace('.en.', '.ja.', $file), true);
                // kr
                // cn
                
                foreach ($en as $i => $line) {
                    $source = ['Cutscene', $line[0]];
                    $text = [
                        'en' => $line[1],
                        'de' => $de[$i][1],
                        'fr' => $fr[$i][1],
                        'ja' => $ja[$i][1],
                    ];
                    
                    if (empty($text)) {
                        continue;
                    }
                    
                    $this->addEntry('Cutscene', $text, $source, null);
                }
            }
        }
        $this->io->progressFinish();
    }
    
}
