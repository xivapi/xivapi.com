<?php

namespace App\Service\DataCustom;

use App\Service\Common\Arrays;
use App\Service\Content\ManualHelper;

class NPC extends ManualHelper
{
    const PRIORITY = 20;
    
    public function handle()
    {
        $this->connectShopsToItems();
        $this->connectShopsToNpcs();
    }
    private function connectShopsToItems()
    {
        $this->io->text(__METHOD__);
        
        $newdata = [];
        foreach ($this->redis->get('ids_GilShop') as $id) {
            $content = $this->redis->get("xiv_GilShop_{$id}");
            
            // Append Shop Items
            $content->Items = [];
    
            // assuming a max of 50 items
            foreach (range(0,50) as $shopNum) {
                $gilShopItem = $this->redis->get("xiv_GilShopItem_{$id}.{$shopNum}");
                $gilShopItem = Arrays::minification($gilShopItem);
        
                if ($gilShopItem) {
                    $content->Items[] = Arrays::minification(
                        $this->redis->get("xiv_Item_{$gilShopItem->Item}")
                    );
                }
            }
            
            $newdata["xiv_GilShop_{$id}"] = $content;
            $newdata = $this->pipeToRedis($newdata, 50);
        }
    }
    
    private function connectShopsToNpcs()
    {
        $this->io->text(__METHOD__);
        $newdata = [];
        
        // grab NPCs
        $ids = $this->redis->get('ids_ENpcResident');
        foreach ($ids as $id) {
            $key = "xiv_ENpcResident_{$id}";
            $npc = $this->redis->get($key);
            
            // Add columns so that all entities have them
            $npc->Base = null;
            $npc->TopicSelect = [];
            $npc->SpecialShop = [];
            $npc->GilShop = [];
            $npc->SwitchTalk = [];
            $npc->DefaultTalk = [];
            $npc->CustomTalk = [];
            $npc->Quests = [];
            $npc->CraftLeve = [];
            
            // Grab NPC base
            $npc->Base = $this->redis->get("xiv_ENpcBase_{$npc->ID}");
            
            foreach (range(0, 31) as $dataNumber) {
                $dataValue = $npc->Base->{"ENpcData{$dataNumber}"};
                
                //
                // TopicSelect
                //
                if ($dataValue >= 3276800 && $dataValue <= 3276900) {
                    $topicSelect = Arrays::minification(
                        $this->redis->get("xiv_TopicSelect_{$dataValue}")
                    );
                    
                    // add empty entries to all topic selects
                    $topicSelect->SpecialShop = [];
                    $topicSelect->GilShop = [];
    
                    // loop through each shop number in the topic selection
                    foreach (range(0, 9) as $shopNumber) {
                        $shopValue = $topicSelect->{"Shop{$shopNumber}"};
        
                        //
                        // Special Shops
                        //
                        if ($shopValue >= 1769000 && $shopValue <= 1770000) {
                            $specialShop = $this->redis->get("xiv_SpecialShop_{$shopValue}");
                            $npc->SpecialShop[] = $specialShop;
                            
                            $topicSelect->SpecialShop[] = [
                                'TopicSelectID' => $topicSelect->ID,
                                'SpecialShopID' => $specialShop->ID,
                            ];
                        }
        
                        //
                        // GilShop
                        //
                        if ($shopValue >= 262100 && $shopValue <= 263000) {
                            $gilShop = $this->redis->get("xiv_GilShop_{$shopValue}");
                            $npc->GilShop[] = $gilShop;
                            
                            $topicSelect->GilShop[] = [
                                'TopicSelectID' => $topicSelect->ID,
                                'GilShopID' => $gilShop->ID,
                            ];
                        }
                    }
    
                    $npc->TopicSelect[] = $topicSelect;
                }
    
                //
                // SpecialShop (with no TopicSelect)
                //
                if ($dataValue >= 1769000 && $dataValue <= 1770000) {
                    $npc->SpecialShop[] = $this->redis->get("xiv_SpecialShop_{$dataValue}");
                }
                
                //
                // GilShop (with no TopicSelect)
                //
                if ($dataValue >= 262100 && $dataValue <= 263000) {
                    $npc->GilShop[] = $this->redis->get("xiv_GilShop_{$dataValue}");
                }
                
                //
                // SwitchTalk
                //
                if ($dataValue >= 2031600 && $dataValue <= 2032400) {
                    $npc->SwitchTalk[] = $this->redis->get("xiv_SwitchTalk_{$dataValue}");
                }
                
                //
                // DefaultTalk
                //
                if ($dataValue >= 589800 && $dataValue <= 594900) {
                    $npc->DefaultTalk[] = Arrays::minification(
                        $this->redis->get("xiv_DefaultTalk_{$dataValue}")
                    );
                }
                
                //
                // CustomTalk
                //
                if ($dataValue >= 720896 && $dataValue <= 721406) {
                    $npc->CustomTalk[] = Arrays::minification(
                        $this->redis->get("xiv_CustomTalk_{$dataValue}")
                    );
                }
                
                //
                // CraftLeve
                //
                if ($dataValue >= 917500 && $dataValue <= 918500) {
                    $npc->CraftLeve[] = Arrays::minification(
                        $this->redis->get("xiv_CraftLeve_{$dataValue}")
                    );
                }
                
                //
                // Quests
                //
                if ($dataValue >= 65530 && $dataValue <= 68700) {
                    $npc->Quests[] = Arrays::minification(
                        $this->redis->get("xiv_Quest_{$dataValue}")
                    );
                }
                
                //
                // Triple Triad
                //
                if ($dataValue >= 2293760 && $dataValue <= 2359300) {
                    $npc->TripleTriadID = $dataValue;
                    break;
                }
                
            }
            
            #file_put_contents(__DIR__.'/lol.json', json_encode($npc, JSON_PRETTY_PRINT));die;
            
            // save
            $newdata[$key] = $npc;
            $newdata = $this->pipeToRedis($newdata, 100);
        }
    }
    
}
