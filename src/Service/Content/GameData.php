<?php

namespace App\Service\Content;

use App\Entity\ItemIcon;
use App\Common\Service\Redis\Redis;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GameData
{
    private $content = ["AOZArrangement","AOZBoss","AOZContent","AOZContentBriefingBNpc","Achievement","AchievementCategory","AchievementKind","Action","ActionCastTimeline","ActionCastVFX","ActionCategory","ActionComboRoute","ActionIndirection","ActionParam","ActionProcStatus","ActionTimeline","ActionTimelineMove","ActionTimelineReplace","ActionTransient","ActivityFeedButtons","ActivityFeedCaptions","ActivityFeedGroupCaptions","ActivityFeedImages","Addon","AddonHud","Adventure","AdventureExPhase","AetherCurrent","AetherCurrentCompFlgSet","AetherialWheel","Aetheryte","AetheryteSystemDefine","AirshipExplorationLevel","AirshipExplorationLog","AirshipExplorationParamType","AirshipExplorationPart","AirshipExplorationPoint","AnimaWeapon5","AnimaWeapon5Param","AnimaWeapon5PatternGroup","AnimaWeapon5SpiritTalk","AnimaWeapon5SpiritTalkParam","AnimaWeapon5TradeItem","AnimaWeaponFUITalk","AnimaWeaponFUITalkParam","AnimaWeaponIcon","AnimaWeaponItem","AozAction","AozActionTransient","AquariumFish","AquariumWater","ArrayEventHandler","AttackType","BGM","BGMFade","BGMSituation","BGMSwitch","BGMSystemDefine","BNpcAnnounceIcon","BNpcBase","BNpcCustomize","BNpcName","BNpcParts","BacklightColor","Balloon","BaseParam","BattleLeve","BeastRankBonus","BeastReputationRank","BeastTribe","Behavior","Buddy","BuddyAction","BuddyEquip","BuddyItem","BuddyRank","BuddySkill","Cabinet","CabinetCategory","Calendar","CharaMakeCustomize","CharaMakeType","ChocoboRace","ChocoboRaceAbility","ChocoboRaceAbilityType","ChocoboRaceItem","ChocoboRaceRank","ChocoboRaceStatus","ChocoboRaceTerritory","ChocoboRaceTutorial","ChocoboRaceWeather","ChocoboTaxi","ChocoboTaxiStand","ClassJob","ClassJobCategory","Companion","CompanionMove","CompanionTransient","CompanyAction","CompanyCraftDraft","CompanyCraftDraftCategory","CompanyCraftManufactoryState","CompanyCraftPart","CompanyCraftProcess","CompanyCraftSequence","CompanyCraftSupplyItem","CompanyCraftType","CompleteJournal","CompleteJournalCategory","ContentCloseCycle","ContentExAction","ContentFinderCondition","ContentFinderConditionTransient","ContentGauge","ContentGaugeColor","ContentMemberType","ContentNpcTalk","ContentRoulette","ContentRouletteOpenRule","ContentRouletteRoleBonus","ContentTalk","ContentTalkParam","ContentType","ContentsNote","CraftAction","CraftLeve","CraftType","Credit","CreditBackImage","CreditCast","Currency","CustomTalk","CustomTalkDynamicIcon","CustomTalkNestHandlers","CutScreenImage","Cutscene","DailySupplyItem","DeepDungeon","DeepDungeonBan","DeepDungeonDanger","DeepDungeonEquipment","DeepDungeonFloorEffectUI","DeepDungeonItem","DeepDungeonLayer","DeepDungeonMagicStone","DeepDungeonMap5X","DeepDungeonRoom","DeepDungeonStatus","DefaultTalk","DefaultTalkLipSyncType","DeliveryQuest","DescriptionPage","DescriptionString","DisposalShop","DisposalShopFilterType","DisposalShopItem","DpsChallenge","DpsChallengeOfficer","DpsChallengeTransient","ENpcBase","ENpcDressUp","ENpcDressUpDress","ENpcResident","EObj","EObjName","EmjAddon","EmjDani","Emote","EmoteCategory","EquipRaceCategory","EquipSlotCategory","EurekaAetherItem","EurekaAethernet","EurekaGrowData","EurekaLogosMixerProbability","EurekaMagiaAction","EurekaMagiciteItem","EurekaMagiciteItemType","EurekaSphereElementAdjust","EventAction","EventIconPriority","EventIconType","EventItem","EventItemCastTimeline","EventItemHelp","EventItemTimeline","ExVersion","ExportedSG","FCActivity","FCActivityCategory","FCAuthority","FCAuthorityCategory","FCChestName","FCHierarchy","FCProfile","FCReputation","FCRights","Fate","FccShop","Festival","FieldMarker","FishParameter","FishingRecordType","FishingRecordTypeTransient","FishingSpot","Frontline03","Frontline04","GCRankGridaniaFemaleText","GCRankGridaniaMaleText","GCRankLimsaFemaleText","GCRankLimsaMaleText","GCRankUldahFemaleText","GCRankUldahMaleText","GCScripShopCategory","GCScripShopItem","GCShop","GCShopItemCategory","GCSupplyDuty","GCSupplyDutyReward","GFATE","GFateClimbing2","GFateClimbing2Content","GFateClimbing2TotemType","GFateRideShooting","GardeningSeed","GatheringCondition","GatheringExp","GatheringItem","GatheringItemLevelConvertTable","GatheringItemPoint","GatheringLeve","GatheringLeveRoute","GatheringNotebookList","GatheringPoint","GatheringPointBase","GatheringPointBonus","GatheringPointBonusType","GatheringPointName","GatheringSubCategory","GatheringType","GcArmyCaptureTactics","GcArmyExpedition","GcArmyExpeditionMemberBonus","GcArmyExpeditionType","GcArmyMemberGrow","GcArmyTraining","GeneralAction","GilShop","GilShopItem","GoldSaucerArcadeMachine","GoldSaucerTextData","GrandCompany","GrandCompanyRank","GuardianDeity","GuildOrderGuide","GuildOrderOfficer","GuildleveAssignment","GuildleveAssignmentCategory","HairMakeType","HouseRetainerPose","HousingAethernet","HousingAppeal","HousingEmploymentNpcList","HousingEmploymentNpcRace","HousingExterior","HousingFurniture","HousingLandSet","HousingMapMarkerInfo","HousingMerchantPose","HousingPlacement","HousingPreset","HousingUnitedExterior","HousingYardObject","HowTo","HowToCategory","HowToPage","InstanceContent","InstanceContentBuff","InstanceContentCSBonus","InstanceContentGuide","InstanceContentTextData","Item","ItemAction","ItemFood","ItemLevel","ItemSearchCategory","ItemSeries","ItemSpecialBonus","ItemUICategory","JournalCategory","JournalGenre","JournalSection","Leve","LeveAssignmentType","LeveClient","LeveRewardItem","LeveRewardItemGroup","LeveVfx","Level","LogFilter","LogKind","LogKindCategoryText","LogMessage","LotteryExchangeShop","MacroIcon","MacroIconRedirectOld","MainCommand","MainCommandCategory","ManeuversArmor","Map","MapMarker","MapMarkerRegion","MapSymbol","Marker","MasterpieceSupplyDuty","MasterpieceSupplyMultiplier","Materia","MiniGameRA","MinionRace","MinionRules","MinionSkillType","MobHuntOrderType","MobHuntTarget","ModelChara","ModelState","MonsterNote","MonsterNoteTarget","Mount","MountAction","MountCustomize","MountFlyingCondition","MountSpeed","MountTransient","MoveTimeline","MoveVfx","NpcEquip","NpcYell","Omen","OnlineStatus","Opening","Orchestrion","OrchestrionCategory","OrchestrionPath","OrchestrionUiparam","ParamGrow","PartyContent","PartyContentCutscene","PartyContentTextData","Perform","PerformTransient","Pet","PetAction","Picture","PlaceName","PlantPotFlowerSeed","PreHandler","PublicContent","PublicContentCutscene","PublicContentTextData","PvPAction","PvPActionSort","PvPRank","PvPSelectTrait","PvPTrait","Quest","QuestClassJobReward","QuestClassJobSupply","QuestRepeatFlag","QuestRewardOther","QuickChat","QuickChatTransient","RPParameter","Race","RacingChocoboItem","RacingChocoboName","RacingChocoboNameCategory","RacingChocoboNameInfo","RacingChocoboParam","RecastNavimesh","Recipe","RecipeElement","RecipeLevelTable","RecipeNotebookList","RecommendContents","Relic","Relic3","RelicItem","RelicNote","RelicNoteCategory","Resident","RetainerTask","RetainerTaskLvRange","RetainerTaskNormal","RetainerTaskParameter","RetainerTaskRandom","Salvage","SatisfactionNpc","SatisfactionSupply","SatisfactionSupplyReward","ScenarioTree","ScenarioTreeTips","ScenarioTreeTipsClassQuest","ScenarioTreeTipsQuest","ScenarioType","ScreenImage","SecretRecipeBook","SkyIsland2Mission","SkyIsland2MissionDetail","SkyIsland2MissionType","SkyIsland2RangeType","SpearfishingItem","SpearfishingNotebook","SpearfishingRecordPage","SpecialShop","SpecialShopItemCategory","Stain","StainTransient","Status","StatusHitEffect","StatusLoopVFX","Story","SubmarineExploration","SubmarinePart","SubmarineRank","SwitchTalk","TerritoryType","TextCommand","Title","Tomestones","TomestonesItem","TopicSelect","Town","Trait","TraitRecast","TraitTransient","Transformation","Treasure","TreasureHuntRank","Tribe","TripleTriad","TripleTriadCard","TripleTriadCardRarity","TripleTriadCardResident","TripleTriadCardType","TripleTriadCompetition","TripleTriadRule","Tutorial","TutorialDPS","TutorialHealer","TutorialTank","UIColor","VFX","VaseFlower","Warp","WarpCondition","WarpLogic","Weather","WeatherGroup","WeatherRate","WeatherReportReplace","WeddingBGM","WeeklyBingoOrderData","WeeklyBingoRewardData","WeeklyBingoText","WeeklyLotBonus","World","WorldDCGroupType","YKW","ZoneSharedGroup"];
    
    /** @var EntityManagerInterface */
    private $em;
    /** @var ContentList */
    private $contentList;

    public function __construct(EntityManagerInterface $em, ContentList $contentList)
    {
        $this->em = $em;
        $this->contentList = $contentList;
    }
    
    /**
     * Returns all item ids to lodestone ids
     */
    public function getLodestoneIds()
    {
        $list = [];
        
        /** @var ItemIcon $item */
        foreach ($this->em->getRepository(ItemIcon::class)->findAll() as $item) {
            $list[] = [
                'ID'            => $item->getItem(),
                'LodestoneID'   => $item->getLodestoneId(),
                'LodestoneIcon' => $item->getLodestoneIcon(),
                'Status'        => $item->getStatus(),
            ];
        }
        
        return $list;
    }
    
    /**
     * get a single piece of content from the cache
     */
    public function one(string $contentName, string $contentId)
    {
        $contentName = $this->validate($contentName);
        $content = Redis::Cache()->get("xiv_{$contentName}_{$contentId}");
        
        if (!$content) {
            throw new NotFoundHttpException("Game Data does not exist: {$contentName} {$contentId}");
        }

        return $content;
    }

    public function list(Request $request, string $contentName)
    {
        $contentName = $this->validate($contentName);
        return $this->contentList->get($request, $contentName);
    }

    /**
     * Get the schema for a piece of content
     */
    public function schema(string $contentName)
    {
        $contentName = $this->validate($contentName);
        return Redis::Cache()->get("schema_{$contentName}");
    }

    /**
     * Get the game content list
     */
    public function content()
    {
        return Redis::Cache()->get('content');
    }

    /**
     * Validate the passed content name, this will
     */
    public function validate(string $contentName): string
    {
        foreach ($this->content as $realContentName) {
            if (strtolower($realContentName) == strtolower($contentName)) {
                return $realContentName;
            }
        }

        throw new NotFoundHttpException("No content data found for: {$contentName}");
    }
}
