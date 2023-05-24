<?php

namespace App\Service\DataCustom;

use App\Common\Constants\RedisConstants;
use App\Common\Utils\Arrays;
use App\Service\Content\ManualHelper;
use App\Common\Service\Redis\Redis;

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
        
        foreach (Redis::Cache(true)->get('ids_GilShop') as $id) {
            $content = Redis::Cache(true)->get("xiv_GilShop_{$id}");
            
            // Append Shop Items
            $content->Items = [];
    
            // assuming a max of 100 items
            foreach (range(0,100) as $shopNum) {
                $gilShopItem = Redis::Cache(true)->get("xiv_GilShopItem_{$id}.{$shopNum}");
                $gilShopItem = Arrays::minification($gilShopItem);
        
                if ($gilShopItem) {
                    $content->Items[] = Arrays::minification(
                        Redis::Cache(true)->get("xiv_Item_{$gilShopItem->Item}")
                    );
                }
            }
    
            Redis::Cache(true)->set("xiv_GilShop_{$id}", $content, RedisConstants::TIME_10_YEAR);
        }
    }
    
    private function connectShopsToNpcs()
    {
        $this->io->text(__METHOD__);
        
        // grab NPCs
        $ids = Redis::Cache(true)->get('ids_ENpcResident');
        foreach ($ids as $id) {
            $key = "xiv_ENpcResident_{$id}";
            $npc = Redis::Cache(true)->get($key);
            
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
            $npc->Base = Redis::Cache(true)->get("xiv_ENpcBase_{$npc->ID}");
            
            foreach (range(0, 31) as $dataNumber) {
                if (!isset($npc->Base->{"ENpcData{$dataNumber}"})) {
                    break;
                }
                
                $dataValue = $npc->Base->{"ENpcData{$dataNumber}"};
                
                //
                // TopicSelect
                //
                if ($dataValue >= 3276800 && $dataValue <= 3276900) {
                    $topicSelect = Arrays::minification(
                        Redis::Cache(true)->get("xiv_TopicSelect_{$dataValue}")
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
                            $specialShop = Redis::Cache(true)->get("xiv_SpecialShop_{$shopValue}");
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
                            $gilShop = Redis::Cache(true)->get("xiv_GilShop_{$shopValue}");
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
                    $npc->SpecialShop[] = Redis::Cache(true)->get("xiv_SpecialShop_{$dataValue}");
                }
                
                //
                // GilShop (with no TopicSelect)
                //
                if ($dataValue >= 262100 && $dataValue <= 263000) {
                    $npc->GilShop[] = Redis::Cache(true)->get("xiv_GilShop_{$dataValue}");
                }
                
                //
                // SwitchTalk
                //
                if ($dataValue >= 2031600 && $dataValue <= 2032400) {
                    $npc->SwitchTalk[] = Redis::Cache(true)->get("xiv_SwitchTalk_{$dataValue}");
                }
                
                //
                // DefaultTalk
                //
                if ($dataValue >= 589800 && $dataValue <= 594900) {
                    $npc->DefaultTalk[] = Arrays::minification(
                        Redis::Cache(true)->get("xiv_DefaultTalk_{$dataValue}")
                    );
                }
                
                //
                // CustomTalk
                //
                if ($dataValue >= 720896 && $dataValue <= 721406) {
                    $npc->CustomTalk[] = Arrays::minification(
                        Redis::Cache(true)->get("xiv_CustomTalk_{$dataValue}")
                    );
                }
                
                //
                // CraftLeve
                //
                if ($dataValue >= 917500 && $dataValue <= 918500) {
                    $npc->CraftLeve[] = Arrays::minification(
                        Redis::Cache(true)->get("xiv_CraftLeve_{$dataValue}")
                    );
                }
                
                //
                // Quests
                //
                if ($dataValue >= 65530 && $dataValue <= 68700) {
                    $npc->Quests[] = Arrays::minification(
                        Redis::Cache(true)->get("xiv_Quest_{$dataValue}")
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
            
            // save
            Redis::Cache(true)->set($key, $npc, RedisConstants::TIME_10_YEAR);
        }
    }
    
}
