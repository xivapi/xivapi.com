<?php

namespace App\Service\DataCustom;

use App\Service\Common\Arrays;
use App\Service\Content\ManualHelper;

class Achievement extends ManualHelper
{
    /**
        ACHIEVEMENT TYPES
    
        https://github.com/viion/XIV-Datamining/blob/master/research/achievements_requirement_type.md

        0: complete specific Legacy thing
        1: do n things
        2: complete n other achievements
        3: achieve n levels as class
        4: affix n materia to the same piece of gear
        5: complete all n of these requirement_2 - 9 things
        6: complete a specific quest
        7: complete all specific hunting log entries
        8: discover every location within...
        9: complete any of these requirement_2 - 9 quests (?)
        10: level your companion chocobo to rank n
        11: achieve PVP rank n with a specific Grand Company
        12: participate in n matches in The Fold
        13: triumph in n matches in The Fold
        14: complete a specific Trial
        15: achieve rank n with a specific Beast Tribe
        16: there is no type 16
        17: participate in n Frontline matches
        18: guide a specific Grand Company to n Frontline victories
        19: triumph in n Frontline matches
        20: attune to all aether currents in a specific area
        21: obtain n minions
        22: there is no type 22
        23: complete all Verminion challenges
        24: obtain a variety of anima weapon
     */
    
    const PRIORITY = 20;
    
    const DATA_IDS = [
        0,1,2,3,4,5,6,7
    ];

    public function handle()
    {
        $ids = $this->getContentIds('Achievement');
    
        foreach ($ids as $id) {
            $key = "xiv_Achievement_{$id}";
            $achievement = $this->redis->get($key);
    
            // add this so all achievements get them
            $achievement->PreAchievements      = (isset($achievement->PreAchievements) && is_array($achievement->PreAchievements)) ? $achievement->PreAchievements : [];
            $achievement->PostAchievements     = (isset($achievement->PostAchievements) && is_array($achievement->PostAchievements)) ? $achievement->PostAchievements : [];
            $achievement->QuestRequirements    = (isset($achievement->QuestRequirements) && is_array($achievement->QuestRequirements)) ? $achievement->QuestRequirements : [];
            $achievement->ClassJobRequirements = $achievement->ClassJobRequirements ?? [];
    
            if ($achievement->Type == 2) {
                $this->insertPreAndPostAchievements($achievement);
            }
    
            if ($achievement->Type == 6 | 9) {
                $this->insertQuestRequirements($achievement);
            }
    
            if ($achievement->Type == 3) {
                $this->insertJobClassRequirements($achievement);
            }
    
            $achievement->QuestRequirements = !empty($achievement->QuestRequirements) ?: [];
    
            // save
            $this->redis->set($key, $achievement, self::REDIS_DURATION);
        }
    }

    /**
     * Attach pre and post quests
     */
    private function insertPreAndPostAchievements($achievement)
    {
        $pre = [];
        foreach (self::DATA_IDS as $i) {
            $value = $achievement->{"Data{$i}"};
            
            if ($value > 0) {
                $pre[] = $value;
            }
        }
    
        if ($pre) {
            $achievement->PreAchievements = [];
            foreach ($pre as $id) {
                //
                // Add pre-achievements
                //
                $preAchievement = Arrays::minification(
                    $this->redis->get("xiv_Achievement_{$id}")
                );
                if (!in_array($preAchievement, $achievement->PreAchievements)) {
                    $achievement->PreAchievements[] = $preAchievement;
                }
                
                //
                // Add post-achievements
                //
                
                // get post achievement and create the post achievements array if it does not exist
                $postAchievement = $this->redis->get("xiv_Achievement_{$id}");
                if (!isset($postAchievement->PostAchievements)) {
                    $postAchievement->PostAchievements = [];
                }

                // get the current achievement in minimum format and add to the post achievement
                $currentAchievement = Arrays::minification(
                    $this->redis->get("xiv_Achievement_{$achievement->ID}")
                );
                if (!in_array($currentAchievement, $postAchievement->PostAchievements)) {
                    $postAchievement->PostAchievements[] = $currentAchievement;
                    $this->redis->set("xiv_Achievement_{$id}", $postAchievement, self::REDIS_DURATION);
                }
            }
        }
    }
    
    /**
     * Attach pre-quests
     */
    private function insertQuestRequirements($achievement)
    {
        foreach (self::DATA_IDS as $i) {
            $value = $achievement->{"Data{$i}"};

            if ($value > 0) {
                $quest = Arrays::minification(
                    $this->redis->get("xiv_Quest_{$value}")
                );

                unset(
                    $quest->TextData_en,
                    $quest->TextData_de,
                    $quest->TextData_fr,
                    $quest->TextData_ja
                );
    
                if (is_object($quest)) {
                    $achievement->QuestRequirements[] = $quest;
                }
            }
        }

        $achievement->QuestRequirementsAll = ($achievement->Type == 6);
    }

    /**
     * Attach job/class requirements
     */
    private function insertJobClassRequirements($achievement)
    {
        $classJob   = $achievement->Data0;
        $level      = $achievement->Data1;

        if ($classJob < 1 || $level < 1) {
            return;
        }

        $achievement->ClassJobRequirements = [
            'Level'     => $level,
            'ClassJob'  => Arrays::minification(
                $this->redis->get("xiv_ClassJob_{$classJob}")
            ),
        ];
    }
}
