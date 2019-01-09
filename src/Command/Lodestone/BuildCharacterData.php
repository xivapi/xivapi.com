<?php

namespace App\Command\Lodestone;

use App\Service\LodestoneQueue\CharacterConverter;
use App\Service\Redis\Cache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This would run on the XIVAPI side. XIVAPI processes responses.
 */
class BuildCharacterData extends Command
{
    /** @var SymfonyStyle */
    private $io;
    /** @var Cache */
    private $cache;
    /** @var array */
    private $data;
    
    protected function configure()
    {
        $this->setName('BuildCharacterData');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->cache = new Cache();
        $this->cache->checkConnection();
        
        $this->io = new SymfonyStyle($input, $output);
        $this->io->title('Building character data');
    
        $this->CacheMounts();
        $this->cacheGeneric('Companion');
        $this->cacheGeneric('Race');
        $this->cacheGeneric('Tribe');
        $this->cacheGeneric('Title');
        $this->cacheGeneric('GrandCompany');
        $this->cacheGeneric('GuardianDeity');
        $this->cacheGeneric('Town');
        $this->cacheGeneric('BaseParam');
        $this->cacheGeneric('GCRankGridaniaFemaleText');
        $this->cacheGeneric('GCRankGridaniaMaleText');
        $this->cacheGeneric('GCRankLimsaFemaleText');
        $this->cacheGeneric('GCRankLimsaMaleText');
        $this->cacheGeneric('GCRankUldahFemaleText');
        $this->cacheGeneric('GCRankUldahMaleText');
        $this->cacheItems();
        
        $code = var_export($this->data, true);
        $code = str_ireplace([ 'array (', ')' ], [ '[', ']' ], $code);
    
        $template = __DIR__.'/../../Service/LodestoneQueue/CharacterDataTemplate.template';
        $template = file_get_contents($template);
        $template = str_ireplace('{{DATA}}', $code, $template);
        file_put_contents(
            __DIR__ . '/../../Service/LodestoneQueue/CharacterData.php',
            $template
        );
    }
    
    private function cacheGeneric($contentName)
    {
        $this->io->text("Cache: {$contentName}");
        foreach ($this->cache->get("ids_{$contentName}") as $id) {
            $content = $this->cache->get("xiv_{$contentName}_{$id}");
            $this->data[$contentName][CharacterConverter::convert($content->Name_en)] = $content->ID;
            
            if (isset($content->NameFemale_en)) {
                $this->data[$contentName][CharacterConverter::convert($content->NameFemale_en)] = $content->ID;
            }
        }
    }
    
    private function CacheMounts()
    {
        $this->io->text("Cache: Mount");
        foreach ($this->cache->get("ids_Mount") as $id) {
            $content = $this->cache->get("xiv_Mount_{$id}");
            
            if ($content->Order == -1) {
                continue;
            }
            
            $this->data['Mount'][CharacterConverter::convert($content->Name_en)] = $content->ID;
        }
    }
    
    private function cacheItems()
    {
        $this->io->text('Cache: Item');
        $ids = $this->cache->get('ids_Item');
        
        $this->io->progressStart(count($ids));
        
        foreach ($ids as $id) {
            $this->io->progressAdvance();
            
            $item = $this->cache->get("xiv_Item_{$id}");
            
            // no name? skip
            if (empty($item->Name_en)) {
                continue;
            }
            
            // build hash
            $hash = CharacterConverter::convert($item->Name_en);
            $this->data['Item'][$hash] = $item->ID;
        }
        $this->io->progressFinish();
    }
}
