<?php

namespace App\Command\Search;

use App\Command\CommandHelperTrait;
use App\Service\Common\DataType;
use App\Service\Common\Language;
use App\Service\Redis\Cache;
use App\Service\Search\SearchContent;
use App\Service\SearchElastic\ElasticSearch;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateSearchCommand extends Command
{
    use CommandHelperTrait;

    protected function configure()
    {
        $this
            ->setName('UpdateSearchCommand')
            ->setDescription('Deploy all search data to live!')
            ->addArgument('environment',  InputArgument::OPTIONAL, 'prod OR dev')
            ->addArgument('content', InputArgument::OPTIONAL, 'Run a specific content')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this
            ->setSymfonyStyle($input, $output)
            ->title('SEARCH')
            ->startClock();

        // connect to production cache
        [$ip, $port] = (in_array($input->getArgument('environment'), ['prod','staging']))
            ? explode(',', getenv('ELASTIC_SERVER_PROD'))
            : explode(',', getenv('ELASTIC_SERVER_LOCAL'));
    
        if ($input->getArgument('environment') == 'prod') {
            $this->io->success('DEPLOYING TO PRODUCTION');
        }
        
        $elastic = new ElasticSearch($ip, $port);
        $cache   = new Cache();
        
        // import documents to ElasticSearch
        try {
            foreach (SearchContent::LIST as $contentName) {
                if ($input->getArgument('content') &&
                    $input->getArgument('content') != $contentName) {
                    continue;
                }
        
                $index  = strtolower($contentName);
                $ids    = $cache->get("ids_{$contentName}");
                
                if (empty($ids)) {
                    $this->io->error('No IDs for content: '. $contentName);
                    continue;
                }
                
                $total  = count($ids);
                $docs   = [];
            
                $this->io->text("<info>ElasticSearch import: {$total} {$contentName} documents to index: {$index}</info>");
    
                // rebuild index
                $elastic->deleteIndex($index);
        
                // create index
                $elastic->addIndex($index);
        
                // Add documents to elastic
                $count = 0;
                $this->io->progressStart($total);
                foreach ($ids as $id) {
                    $count++;
    
                    // grab content
                    $content = $cache->get("xiv_{$contentName}_{$id}");
                    
                    // if no name_en, skip it!
                    if (empty($content->Name_en)) {
                        continue;
                    }
    
                    // remove arrays from content
                    foreach ($content as $field => $value) {
                        if (is_array($value)) {
                            unset($content->{$field});
                        }
                    }
                    
                    // convert the whole thing to an array
                    $content = json_decode(json_encode($content), true);
                    
                    // ensure content types are correctly assigned
                    $content = DataType::ensureStrictDataTypes($content);
                    
                    // handle custom string columns
                    $content = $this->handleCustomStringColumns($contentName, $content);
                    
                    // handle clean up
                    $content = $this->handleCleanUp($contentName, $content);

                    // append to docs
                    $docs[$id] = $content;
    
                    //$elastic->addDocument($index, 'search', $id, $content);
                    
                    // insert docs
                    if ($count >= ElasticSearch::MAX_BULK_DOCUMENTS) {
                        $this->io->progressAdvance($count);
                        $elastic->bulkDocuments($index, 'search', $docs);
                        $docs = [];
                        $count = 0;
                    }
                }
        
                // add any reminders
                if (count($docs) > 0) {
                    $elastic->bulkDocuments($index, 'search', $docs);
                }
                $this->io->progressFinish();
            }
        } catch (\Exception $ex) {
            //print_r($content ?? ['no content']);
            print_r($ex->getMessage());
            throw $ex;
        }
    
        unset($content, $docs);
        $this->complete()->endClock();
    }
    
    private function handleCleanUp(string $contentName, array $content)
    {
        if ($contentName === 'Quest') {
            //
            // Remove junk
            //
            foreach(range(0,170) as $num) {
                unset(
                    $content["TextData_en"],
                    $content["TextData_de"],
                    $content["TextData_fr"],
                    $content["TextData_ja"],
                    $content["TextData_kr"],
                    $content["TextData_cn"],
                    
                    $content["Level{$num}"],
                    $content["Level{$num}Target"],
                    $content["Level{$num}TargetID"],
                    $content["ScriptInstruction{$num}_en"],
                    $content["ScriptInstruction{$num}_de"],
                    $content["ScriptInstruction{$num}_fr"],
                    $content["ScriptInstruction{$num}_ja"],
                    $content["ScriptArg{$num}"],
                    
                    $content["PreviousQuest0"]["Level{$num}"],
                    $content["PreviousQuest0"]["Level{$num}Target"],
                    $content["PreviousQuest0"]["Level{$num}TargetID"],
                    $content["PreviousQuest0"]["ScriptInstruction{$num}_en"],
                    $content["PreviousQuest0"]["ScriptInstruction{$num}_de"],
                    $content["PreviousQuest0"]["ScriptInstruction{$num}_fr"],
                    $content["PreviousQuest0"]["ScriptInstruction{$num}_ja"],
                    $content["PreviousQuest0"]["ScriptArg{$num}"]
                );
            }
        }
        
        return $content;
    }
    
    /**
     * This will create 2 new columns:
     * - NameCombined_[Lang]: Combines the fields of content where 2 names may
     *                        be present (eg Titles have Name + NameFemale)
     * - NameLocale: Provides a column with all names from all languages so
     *               1 column can be searched via multiple languages
     */
    private function handleCustomStringColumns(string $contentName, array $content)
    {
        //
        // Copy balloon dialogue to a name field, just for simplicity
        //
        if ($contentName == 'Balloon') {
            foreach (Language::LANGUAGES as $lang) {
                $content["Name_{$lang}"] = $content["Dialogue_{$lang}"] ?? '';
            }
        }
        
        //
        // Build NameCombined fields
        //
        foreach (Language::LANGUAGES as $lang) {
            $content["NameCombined_{$lang}"] = $content["Name_{$lang}"] ?? '';
            
            // append on female names
            if ($contentName == 'Title') {
                $content["NameCombined_{$lang}"] .= " ". ($content["NameFemale_{$lang}"] ?? '');
            }

            $content["NameCombined_{$lang}"] = trim($content["NameCombined_{$lang}"]);
        }
    
        //
        // Build NameLocale fields
        //
        $content['NameLocale'] = '';
        foreach (Language::LANGUAGES as $lang) {
            $content['NameLocale'] .= ' '. ($content["NameCombined_{$lang}"] ?? '');
        }
    
        $content['NameLocale'] = trim($content['NameLocale']);
        
        return $content;
    }
}
