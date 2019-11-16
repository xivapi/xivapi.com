<?php

namespace App\Service\Content;

use App\Entity\ItemIcon;
use App\Common\Service\Redis\Redis;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GameData
{
    private $content = ["AOZArrangement","AOZBoss","AOZContent","AOZContentBriefingBNpc","Achievement","AchievementCategory","AchievementHideCondition","AchievementKind","Action","ActionCastTimeline","ActionCastVFX","ActionCategory","ActionComboRoute","ActionIndirection","ActionParam","ActionProcStatus","ActionTimeline","ActionTimelineMove","ActionTimelineReplace","ActionTransient","ActivityFeedButtons","ActivityFeedCaptions","ActivityFeedGroupCaptions","ActivityFeedImages","Addon","AddonHud","Adventure","AdventureExPhase","AetherCurrent","AetherCurrentCompFlgSet","AetherialWheel","Aetheryte","AetheryteSystemDefine","AirshipExplorationLevel","AirshipExplorationLog","AirshipExplorationParamType","AirshipExplorationPart","AirshipExplorationPoint","AnimaWeapon5","AnimaWeapon5Param","AnimaWeapon5PatternGroup","AnimaWeapon5SpiritTalk","AnimaWeapon5SpiritTalkParam","AnimaWeapon5TradeItem","AnimaWeaponFUITalk","AnimaWeaponFUITalkParam","AnimaWeaponIcon","AnimaWeaponItem","AozAction","AozActionTransient","AquariumFish","AquariumWater","ArrayEventHandler","AttackType","BGM","BGMFade","BGMFadeType","BGMScene","BGMSituation","BGMSwitch","BGMSystemDefine","BNpcAnnounceIcon","BNpcCustomize","BNpcName","BNpcParts","BNpcState","BacklightColor","Ballista","Balloon","BaseParam","BattleLeve","BeastRankBonus","BeastReputationRank","BeastTribe","Behavior","BehaviorPath","BenchmarkOverrideEquipment","Buddy","BuddyAction","BuddyEquip","BuddyRank","BuddySkill","Cabinet","CabinetCategory","Calendar","Carry","Channeling","CharaMakeClassEquip","CharaMakeCustomize","CharaMakeType","ChocoboRace","ChocoboRaceAbility","ChocoboRaceAbilityType","ChocoboRaceItem","ChocoboRaceRank","ChocoboRaceStatus","ChocoboRaceTerritory","ChocoboRaceTutorial","ChocoboRaceWeather","ChocoboTaxi","ChocoboTaxiStand","CircleActivity","ClassJob","ClassJobCategory","Companion","CompanionMove","CompanionTransient","CompanyAction","CompanyCraftDraft","CompanyCraftDraftCategory","CompanyCraftManufactoryState","CompanyCraftPart","CompanyCraftProcess","CompanyCraftSequence","CompanyCraftSupplyItem","CompanyCraftType","CompanyLeve","CompanyLeveRule","CompleteJournal","CompleteJournalCategory","ConfigKey","ContentCloseCycle","ContentExAction","ContentFinderCondition","ContentFinderConditionTransient","ContentGauge","ContentGaugeColor","ContentMemberType","ContentNpcTalk","ContentRandomSelect","ContentRoulette","ContentRouletteOpenRule","ContentRouletteRoleBonus","ContentTalk","ContentTalkParam","ContentType","ContentsNote","CraftAction","CraftLeve","CraftLevelDifference","CraftType","Credit","CreditBackImage","CreditCast","CreditList","CreditListText","Currency","CustomTalk","CustomTalkDynamicIcon","CustomTalkNestHandlers","CutScreenImage","Cutscene","CutsceneMotion","CutsceneWorkIndex","CycleTime","DailySupplyItem","DawnContent","DawnGrowMember","DawnMemberUIParam","DawnQuestAnnounce","DawnQuestMember","DeepDungeon","DeepDungeonBan","DeepDungeonDanger","DeepDungeonEquipment","DeepDungeonFloorEffectUI","DeepDungeonItem","DeepDungeonLayer","DeepDungeonMagicStone","DeepDungeonMap5X","DeepDungeonRoom","DeepDungeonStatus","DefaultTalk","DefaultTalkLipSyncType","DeliveryQuest","DescriptionPage","DescriptionString","DisposalShop","DisposalShopFilterType","DisposalShopItem","DpsChallenge","DpsChallengeOfficer","DpsChallengeTransient","ENpcBase","ENpcDressUp","ENpcDressUpDress","ENpcResident","EObj","EObjName","EmjAddon","EmjDani","Emote","EmoteCategory","EmoteMode","EquipRaceCategory","EquipSlotCategory","EurekaAetherItem","EurekaAethernet","EurekaGrowData","EurekaLogosMixerProbability","EurekaMagiaAction","EurekaMagiciteItem","EurekaMagiciteItemType","EurekaSphereElementAdjust","EventAction","EventIconPriority","EventIconType","EventItem","EventItemCastTimeline","EventItemHelp","EventItemTimeline","EventSystemDefine","ExVersion","ExportedSG","FCActivity","FCActivityCategory","FCAuthority","FCAuthorityCategory","FCChestName","FCCrestSymbol","FCHierarchy","FCProfile","FCRank","FCReputation","FCRights","Fate","FateEvent","FateProgressUI","FateTokenType","FccShop","Festival","FieldMarker","FishParameter","FishingRecordType","FishingRecordTypeTransient","FishingSpot","Frontline03","Frontline04","FurnitureCatalogCategory","FurnitureCatalogItemList","GCRankGridaniaFemaleText","GCRankGridaniaMaleText","GCRankLimsaFemaleText","GCRankLimsaMaleText","GCRankUldahFemaleText","GCRankUldahMaleText","GCScripShopCategory","GCScripShopItem","GCShop","GCShopItemCategory","GCSupplyDuty","GCSupplyDutyReward","GFATE","GFateClimbing2","GFateClimbing2Content","GFateClimbing2TotemType","GFateRideShooting","GardeningSeed","GatheringCondition","GatheringExp","GatheringItem","GatheringItemLevelConvertTable","GatheringItemPoint","GatheringLeve","GatheringLeveRoute","GatheringNotebookList","GatheringPoint","GatheringPointBase","GatheringPointBonus","GatheringPointBonusType","GatheringPointName","GatheringType","GcArmyCaptureTactics","GcArmyExpedition","GcArmyExpeditionMemberBonus","GcArmyExpeditionType","GcArmyMemberGrow","GcArmyTraining","GeneralAction","GilShop","GilShopItem","GimmickAccessor","GimmickJump","GimmickRect","GoldSaucerArcadeMachine","GoldSaucerTextData","GrandCompany","GrandCompanyRank","GuardianDeity","Guide","GuidePage","GuidePageString","GuideTitle","GuildOrder","GuildOrderGuide","GuildOrderOfficer","GuildleveAssignment","GuildleveAssignmentCategory","HWDAnnounce","HWDCrafterSupply","HWDCrafterSupplyReward","HWDDevLayerControl","HWDDevLevelUI","HWDDevLively","HWDDevProgress","HWDInfoBoardArticle","HWDInfoBoardArticleTransient","HWDInfoBoardArticleType","HWDIntroduction","HWDLevelChangeDeception","HWDSharedGroup","HWDSharedGroupControlParam","HairMakeType","HouseRetainerPose","HousingAethernet","HousingAppeal","HousingEmploymentNpcList","HousingEmploymentNpcRace","HousingExterior","HousingFurniture","HousingLandSet","HousingMapMarkerInfo","HousingMerchantPose","HousingPlacement","HousingPreset","HousingUnitedExterior","HousingYardObject","HowTo","HowToCategory","HowToPage","HugeCraftworksNpc","HugeCraftworksRank","IndividualWeather","InstanceContent","InstanceContentBuff","InstanceContentCSBonus","InstanceContentGuide","InstanceContentTextData","Item","ItemAction","ItemFood","ItemLevel","ItemSearchCategory","ItemSeries","ItemSpecialBonus","ItemUICategory","JobHudManual","JobHudManualPriority","JournalCategory","JournalGenre","JournalSection","Knockback","LegacyQuest","Leve","LeveAssignmentType","LeveClient","LeveRewardItem","LeveRewardItemGroup","LeveVfx","Level","Lobby","LogFilter","LogKind","LogKindCategoryText","LogMessage","LotteryExchangeShop","MacroIcon","MacroIconRedirectOld","MainCommand","MainCommandCategory","ManeuversArmor","Map","MapMarker","MapMarkerRegion","MapSymbol","Marker","MasterpieceSupplyDuty","MasterpieceSupplyMultiplier","Materia","MateriaJoinRate","MateriaJoinRateGatherCraft","MateriaTomestoneRate","MiniGameRA","MinionRace","MinionRules","MinionSkillType","MobHuntOrderType","MobHuntTarget","ModelChara","ModelSkeleton","ModelState","MonsterNote","MonsterNoteTarget","MotionTimeline","MotionTimelineBlendTable","Mount","MountAction","MountCustomize","MountFlyingCondition","MountSpeed","MountTransient","MoveTimeline","MoveVfx","MovieSubtitle","MovieSubtitle500","MovieSubtitleVoyage","NotebookDivision","NotebookDivisionCategory","NotoriousMonster","NpcEquip","NpcYell","Omen","OnlineStatus","OpenContent","OpenContentCandidateName","Opening","Orchestrion","OrchestrionCategory","OrchestrionPath","OrchestrionUiparam","ParamGrow","PartyContent","PartyContentCutscene","PartyContentTextData","Perform","PerformTransient","Pet","PetAction","PhysicsGroup","PhysicsWind","Picture","PlaceName","PlantPotFlowerSeed","PreHandler","PresetCamera","PresetCameraAdjust","PublicContent","PublicContentCutscene","PublicContentTextData","PvPAction","PvPActionSort","PvPRank","PvPSelectTrait","PvPTrait","Quest","QuestBattle","QuestChapter","QuestClassJobReward","QuestClassJobSupply","QuestDerivedClass","QuestRedo","QuestRedoChapterUI","QuestRedoChapterUICategory","QuestRedoGroup","QuestRedoIncompChapter","QuestRepeatFlag","QuestRewardOther","QuickChat","QuickChatTransient","RPParameter","Race","RacingChocoboItem","RacingChocoboName","RacingChocoboNameCategory","RacingChocoboNameInfo","RacingChocoboParam","RecastNavimesh","RecipeLevelTable","RecipeNotebookList","RecommendContents","Relic","Relic3","RelicItem","RelicNote","RelicNoteCategory","Resident","RetainerTask","RetainerTaskLvRange","RetainerTaskNormal","RetainerTaskParameter","RetainerTaskRandom","Salvage","SatisfactionNpc","SatisfactionSupply","SatisfactionSupplyReward","ScenarioTree","ScenarioTreeTips","ScenarioTreeTipsClassQuest","ScenarioTreeTipsQuest","ScenarioType","ScreenImage","SecretRecipeBook","SkyIsland2Mission","SkyIsland2MissionDetail","SkyIsland2MissionType","SkyIsland2RangeType","SnipeTalk","SnipeTalkName","SpearfishingItem","SpearfishingNotebook","SpearfishingRecordPage","SpecialShopItemCategory","Stain","StainTransient","Status","StatusHitEffect","StatusLoopVFX","Story","SubmarineExploration","SubmarineMap","SubmarinePart","SubmarineRank","SwitchTalk","TerritoryType","TextCommand","Title","Tomestones","TomestonesItem","TopicSelect","Town","Trait","TraitRecast","TraitTransient","Transformation","Treasure","TreasureHuntRank","TreasureSpot","Tribe","TripleTriad","TripleTriadCard","TripleTriadCardRarity","TripleTriadCardResident","TripleTriadCardType","TripleTriadCompetition","TripleTriadRule","Tutorial","TutorialDPS","TutorialHealer","TutorialTank","UIColor","VFX","VaseFlower","Warp","WarpCondition","WarpLogic","WeaponTimeline","Weather","WeatherGroup","WeatherRate","WeatherReportReplace","WeddingBGM","WeeklyBingoOrderData","WeeklyBingoRewardData","WeeklyBingoText","WeeklyLotBonus","World","WorldDCGroupType","YKW","YardCatalogCategory","YardCatalogItemList","ZoneSharedGroup"];
    
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
