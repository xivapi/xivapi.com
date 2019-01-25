<?php

namespace App\Service\DataCustom;

use App\Service\Common\Arrays;
use App\Service\Content\ManualHelper;
use App\Service\Common\Language;

class Quest extends ManualHelper
{
    const PRIORITY = 20;
    
    private $ENpcResidentToName = [];
    private $ENpcResidentToData = [];
    
    public function handle()
    {
        // todo - JournalSection
        
        // pre-warm NPCs
        $this->io->text("Warming ENpcResidents");
        foreach ($this->redis->get('ids_ENpcResident') as $id) {
            $npc  = Arrays::minification(
                $this->redis->get("xiv_ENpcResident_{$id}")
            );
            $name = preg_replace('/[0-9]+/', null, str_ireplace(' ', null, strtolower($npc->Name_en)));
            
            if (isset($this->ENpcResidentToName[$name])) {
                continue;
            }
            
            $this->ENpcResidentToName[$name] = $id;
            $this->ENpcResidentToData[$id]   = $npc;
        }
        
        // ----
        
        $ids = $this->getContentIds('Quest');
        foreach ($ids as $id) {
            $key = "xiv_Quest_{$id}";
            $quest = $this->redis->get($key);
            
            // Add to all quests
            $quest->ExperiencePoints = 0;
    
            // Do stuff
            $this->addQuestExp($quest);
            $this->addQuestText($quest);
            
            // todo
            #$this->addPreAndPostQuests($quest);
    
            // save
            $this->redis->set($key, $quest, self::REDIS_DURATION);
        }
    }
    
    /**
     * Append quest text
     */
    private function addQuestText($quest)
    {
        if (strlen($quest->TextFile_en) < 2) {
            return;
        }
        
        // grab folder
        $folder = substr(explode('_', $quest->TextFile_en)[1], 0, 3);
        $quest->TextFilename = "quest/{$folder}/{$quest->TextFile_en}.[lang].csv";
        $quest->TextData_en  = null;
        $quest->TextData_de  = null;
        $quest->TextData_fr  = null;
        $quest->TextData_ja  = null;
        $quest->TextData_cn  = null;
        $quest->TextData_kr  = null;
    
        // loop through languages
        foreach (Language::LANGUAGES as $language) {
            $filename = str_ireplace('[lang]', $language, $quest->TextFilename);
            $textdata = $this->getCsv($filename);
            if (!$textdata) {
                continue;
            }
            
            $textdataFormatted = [];
            foreach ($textdata as $i => $td) {
                $text    = $td[1];
                $command = $td[0];
                $command = explode('_', $command);
                
                if (!trim($text)) {
                    continue;
                }
                
                $data = (Object)[
                    'Key'   => $td[0],
                    'Type'  => null,
                    'Npc'   => null,
                    'Order' => null,
                    'Text'  => $text,
                ];
    
                if ($command[4] == 'BATTLETALK') {
                    $data->Type = 'BattleTalk';
                    $data->Npc = $this->addQuestTextNpcSearch(trim($command[3]));
                    $data->Order = isset($command[5]) ? intval($command[5]) : $i;
                    continue;
                }
    
                // build data structure from command
                switch($command[3]) {
                    case 'SEQ':
                        $data->Type = 'Journal';
                        $data->Order = intval($command[4]);
                        break;
        
                    case 'SCENE':
                        $data->Type = 'Scene';
                        $data->Order = intval($command[7]);
                        break;
        
                    case 'TODO':
                        $data->Type = 'ToDo';
                        $data->Order = intval($command[4]);
                        break;
        
                    case 'POP':
                        $data->Type = 'Pop';
                        $data->Order = $i;
                        break;
        
                    case 'ACCESS':
                        $data->Type = 'Access';
                        $data->Order = $i;
                        break;
        
                    case 'INSTANCE':
                        $data->Type = 'Instance';
                        $data->Order = $i;
                        break;
        
                    case 'SYSTEM':
                        $data->Type = 'System';
                        $data->Order = $i;
                        break;
        
                    case 'QIB':
                        $npc = filter_var($command[4], FILTER_SANITIZE_STRING);
            
                        // sometimes QIB can be a todo
                        if ($npc == 'TODO') {
                            $data->Type = 'Todo';
                            $data->Order = $i;
                            break;
                        }
            
                        $data->Type = 'BattleTalk';
                        $data->Npc = $this->addQuestTextNpcSearch(trim($npc));
                        $data->Order = $i;
                        break;
        
                    // 20 possible questions ...
                    case 'Q1':  case 'Q2':  case 'Q3':  case 'Q4':  case 'Q5':
                    case 'Q6':  case 'Q7':  case 'Q8':  case 'Q9':  case 'Q10':
                    case 'Q11': case 'Q12': case 'Q13': case 'Q14': case 'Q15':
                    case 'Q16': case 'Q17': case 'Q18': case 'Q19': case 'Q20':
                    $data->Type = 'QA_Question';
                    $data->Order = intval($command[4]);
                    break;
        
                    // with 20 possible answers ...
                    case 'A1':  case 'A2':  case 'A3':  case 'A4':  case 'A5':
                    case 'A6':  case 'A7':  case 'A8':  case 'A9':  case 'A10':
                    case 'A11': case 'A12': case 'A13': case 'A14': case 'A15':
                    case 'A16': case 'A17': case 'A18': case 'A19': case 'A20':
                    $data->Type = 'QA_Answer';
                    $data->Order = intval($command[4]);
                    break;
        
                    default:
                        $npc   = trim($command[3]);
                        $Order = isset($command[5]) ? intval($command[5]) : intval($command[4]);
            
                        // if npc is numeric, budge over 1
                        if (is_numeric($npc)) {
                            $npc   = trim($command[4]);
                            $Order = intval($command[3]);
                        }
            
                        $data->Type = 'Dialogue';
                        $data->Npc = $this->addQuestTextNpcSearch(trim($npc));
                        $data->Order = $Order;
                }
                
                // try get true npc
                $textdataFormatted[$data->Type][] = $data;
            }
            
            // set
            $quest->{"TextData_{$language}"} = $textdataFormatted;

            // clear memory
            unset($textdata, $data);
        }
        
        # DEBUG
        # file_put_contents(__DIR__.'/lol.json', json_encode($quest, JSON_PRETTY_PRINT));die;
    }
    
    private function addQuestTextNpcSearch($npcName)
    {
        if (!$npcName) {
            return null;
        }

        $name = preg_replace('/[0-9]+/', null, str_ireplace(' ', null, strtolower($npcName)));
        
        // if npc exists
        if (isset($this->ENpcResidentToName[$name])) {
            $npcId  = $this->ENpcResidentToName[$name];
            $npc    = $this->ENpcResidentToData[$npcId];
            return $npc;
        }
        
        return ucwords(strtolower($npcName));
    }
    
    /**
     * Add reward EXP to each quest using the crazy formula
     */
    private function addQuestExp($quest)
    {
        if ($quest->ClassJobLevel0 < 1 || $quest->ClassJobLevel0 > 100) {
            return;
        }
        
        $paramGrow  = $this->redis->get("xiv_ParamGrow_{$quest->ClassJobLevel0}");
        
        // CORE = Quest.ExpFactor * ParamGrow.QuestExpModifier * (45 + (5 * Quest.ClassJobLevel0)) / 100
        $EXP = $quest->ExpFactor * $paramGrow->QuestExpModifier * (45 + (5 * $quest->ClassJobLevel0)) / 100;
        
        // CORE + ((400 * (Quest.ExpFactor / 100)) + ((Quest.ClassJobLevel0-52) * (400 * (Quest.ExpFactor/100))))
        if (in_array($quest->ClassJobLevel0, [50])) {
            $EXP = $EXP + ((400 * ($quest->ExpFactor / 100)) + (($quest->ClassJobLevel0 - 50) * (400 * ($quest->ExpFactor / 100))));
        }
        
        // CORE + ((800 * (Quest.ExpFactor / 100)) + ((Quest.ClassJobLevel0-52) * (800 * (Quest.ExpFactor/100))))
        else if (in_array($quest->ClassJobLevel0, [51])) {
            $EXP = $EXP + ((800 * ($quest->ExpFactor / 100)) + (($quest->ClassJobLevel0 - 50) * (400 * ($quest->ExpFactor / 100))));
        }
        
        // CORE + ((2000 * (Quest.ExpFactor / 100)) + ((Quest.ClassJobLevel0-52) * (2000 * (Quest.ExpFactor/100))))
        else if (in_array($quest->ClassJobLevel0, [52,53,54,55,56,57,58,59])) {
            $EXP = $EXP + ((2000  * ($quest->ExpFactor / 100)) + (($quest->ClassJobLevel0 - 52) * (2000  * ($quest->ExpFactor / 100))));
        }
        
        // CORE + ((37125 * (Quest.ExpFactor / 100)) + ((Quest.ClassJobLevel0-60) * (3375 * (Quest.ExpFactor/100))))
        else if (in_array($quest->ClassJobLevel0, [60,61,62,63,64,65,66,67,68,69])) {
            $EXP = $EXP + ((37125  * ($quest->ExpFactor / 100)) + (($quest->ClassJobLevel0 - 60) * (3375  * ($quest->ExpFactor / 100))));
        }
        
        $quest->ExperiencePoints = $EXP;
    }
}
