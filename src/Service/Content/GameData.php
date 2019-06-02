<?php

namespace App\Service\Content;

use App\Entity\ItemIcon;
use App\Common\Service\Redis\Redis;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GameData
{
    private $content = ["aozarrangement","aozboss","aozcontent","aozcontentbriefingbnpc","achievement","achievementcategory","achievementkind","action","actioncasttimeline","actioncastvfx","actioncategory","actioncomboroute","actionindirection","actionparam","actionprocstatus","actiontimeline","actiontimelinemove","actiontimelinereplace","actiontransient","activityfeedbuttons","activityfeedcaptions","activityfeedgroupcaptions","activityfeedimages","addon","addonhud","adventure","adventureexphase","aethercurrent","aethercurrentcompflgset","aetherialwheel","aetheryte","aetherytesystemdefine","airshipexplorationlevel","airshipexplorationlog","airshipexplorationparamtype","airshipexplorationpart","airshipexplorationpoint","animaweapon5","animaweapon5param","animaweapon5patterngroup","animaweapon5spirittalk","animaweapon5spirittalkparam","animaweapon5tradeitem","animaweaponfuitalk","animaweaponfuitalkparam","animaweaponicon","animaweaponitem","aozaction","aozactiontransient","aquariumfish","aquariumwater","arrayeventhandler","attacktype","bgm","bgmfade","bgmsituation","bgmswitch","bgmsystemdefine","bnpcannounceicon","bnpcbase","bnpccustomize","bnpcname","bnpcparts","backlightcolor","balloon","baseparam","battleleve","beastrankbonus","beastreputationrank","beasttribe","behavior","buddy","buddyaction","buddyequip","buddyitem","buddyrank","buddyskill","cabinet","cabinetcategory","calendar","charamakecustomize","charamaketype","chocoborace","chocoboraceability","chocoboraceabilitytype","chocoboraceitem","chocoboracerank","chocoboracestatus","chocoboraceterritory","chocoboracetutorial","chocoboraceweather","chocobotaxi","chocobotaxistand","classjob","classjobcategory","companion","companionmove","companiontransient","companyaction","companycraftdraft","companycraftdraftcategory","companycraftmanufactorystate","companycraftpart","companycraftprocess","companycraftsequence","companycraftsupplyitem","companycrafttype","completejournal","completejournalcategory","contentclosecycle","contentexaction","contentfindercondition","contentfinderconditiontransient","contentgauge","contentgaugecolor","contentmembertype","contentnpctalk","contentroulette","contentrouletteopenrule","contentrouletterolebonus","contenttalk","contenttalkparam","contenttype","contentsnote","craftaction","craftleve","crafttype","credit","creditbackimage","creditcast","currency","customtalk","customtalkdynamicicon","customtalknesthandlers","cutscreenimage","cutscene","dailysupplyitem","deepdungeon","deepdungeonban","deepdungeondanger","deepdungeonequipment","deepdungeonflooreffectui","deepdungeonitem","deepdungeonlayer","deepdungeonmagicstone","deepdungeonmap5x","deepdungeonroom","deepdungeonstatus","defaulttalk","defaulttalklipsynctype","deliveryquest","descriptionpage","descriptionstring","disposalshop","disposalshopfiltertype","disposalshopitem","dpschallenge","dpschallengeofficer","dpschallengetransient","enpcbase","enpcdressup","enpcdressupdress","enpcresident","eobj","eobjname","emjaddon","emjdani","emote","emotecategory","equipracecategory","equipslotcategory","eurekaaetheritem","eurekaaethernet","eurekagrowdata","eurekalogosmixerprobability","eurekamagiaaction","eurekamagiciteitem","eurekamagiciteitemtype","eurekasphereelementadjust","eventaction","eventiconpriority","eventicontype","eventitem","eventitemcasttimeline","eventitemhelp","eventitemtimeline","exversion","exportedsg","fcactivity","fcactivitycategory","fcauthority","fcauthoritycategory","fcchestname","fchierarchy","fcprofile","fcreputation","fcrights","fate","fccshop","festival","fieldmarker","fishparameter","fishingrecordtype","fishingrecordtypetransient","fishingspot","frontline03","frontline04","gcrankgridaniafemaletext","gcrankgridaniamaletext","gcranklimsafemaletext","gcranklimsamaletext","gcrankuldahfemaletext","gcrankuldahmaletext","gcscripshopcategory","gcscripshopitem","gcshop","gcshopitemcategory","gcsupplyduty","gcsupplydutyreward","gfate","gfateclimbing2","gfateclimbing2content","gfateclimbing2totemtype","gfaterideshooting","gardeningseed","gatheringcondition","gatheringexp","gatheringitem","gatheringitemlevelconverttable","gatheringitempoint","gatheringleve","gatheringleveroute","gatheringnotebooklist","gatheringpoint","gatheringpointbase","gatheringpointbonus","gatheringpointbonustype","gatheringpointname","gatheringsubcategory","gatheringtype","gcarmycapturetactics","gcarmyexpedition","gcarmyexpeditionmemberbonus","gcarmyexpeditiontype","gcarmymembergrow","gcarmytraining","generalaction","gilshop","gilshopitem","goldsaucerarcademachine","goldsaucertextdata","grandcompany","grandcompanyrank","guardiandeity","guildorderguide","guildorderofficer","guildleveassignment","guildleveassignmentcategory","hairmaketype","houseretainerpose","housingaethernet","housingappeal","housingemploymentnpclist","housingemploymentnpcrace","housingexterior","housingfurniture","housinglandset","housingmapmarkerinfo","housingmerchantpose","housingplacement","housingpreset","housingunitedexterior","housingyardobject","howto","howtocategory","howtopage","instancecontent","instancecontentbuff","instancecontentcsbonus","instancecontentguide","instancecontenttextdata","item","itemaction","itemfood","itemlevel","itemsearchcategory","itemseries","itemspecialbonus","itemuicategory","journalcategory","journalgenre","journalsection","leve","leveassignmenttype","leveclient","leverewarditem","leverewarditemgroup","levevfx","level","logfilter","logkind","logkindcategorytext","logmessage","lotteryexchangeshop","macroicon","macroiconredirectold","maincommand","maincommandcategory","maneuversarmor","map","mapmarker","mapmarkerregion","mapsymbol","marker","masterpiecesupplyduty","masterpiecesupplymultiplier","materia","minigamera","minionrace","minionrules","minionskilltype","mobhuntordertype","mobhunttarget","modelchara","modelstate","monsternote","monsternotetarget","mount","mountaction","mountcustomize","mountflyingcondition","mountspeed","mounttransient","movetimeline","movevfx","npcequip","npcyell","omen","onlinestatus","opening","orchestrion","orchestrioncategory","orchestrionpath","orchestrionuiparam","paramgrow","partycontent","partycontentcutscene","partycontenttextdata","perform","performtransient","pet","petaction","picture","placename","plantpotflowerseed","prehandler","publiccontent","publiccontentcutscene","publiccontenttextdata","pvpaction","pvpactionsort","pvprank","pvpselecttrait","pvptrait","quest","questclassjobreward","questclassjobsupply","questrepeatflag","questrewardother","quickchat","quickchattransient","rpparameter","race","racingchocoboitem","racingchocoboname","racingchocobonamecategory","racingchocobonameinfo","racingchocoboparam","recastnavimesh","recipe","recipeelement","recipeleveltable","recipenotebooklist","recommendcontents","relic","relic3","relicitem","relicnote","relicnotecategory","resident","retainertask","retainertasklvrange","retainertasknormal","retainertaskparameter","retainertaskrandom","salvage","satisfactionnpc","satisfactionsupply","satisfactionsupplyreward","scenariotree","scenariotreetips","scenariotreetipsclassquest","scenariotreetipsquest","scenariotype","screenimage","secretrecipebook","skyisland2mission","skyisland2missiondetail","skyisland2missiontype","skyisland2rangetype","spearfishingitem","spearfishingnotebook","spearfishingrecordpage","specialshop","specialshopitemcategory","stain","staintransient","status","statushiteffect","statusloopvfx","story","submarineexploration","submarinepart","submarinerank","switchtalk","territorytype","textcommand","title","tomestones","tomestonesitem","topicselect","town","trait","traitrecast","traittransient","transformation","treasure","treasurehuntrank","tribe","tripletriad","tripletriadcard","tripletriadcardrarity","tripletriadcardresident","tripletriadcardtype","tripletriadcompetition","tripletriadrule","tutorial","tutorialdps","tutorialhealer","tutorialtank","uicolor","vfx","vaseflower","warp","warpcondition","warplogic","weather","weathergroup","weatherrate","weatherreportreplace","weddingbgm","weeklybingoorderdata","weeklybingorewarddata","weeklybingotext","weeklylotbonus","world","worlddcgrouptype","ykw","zonesharedgroup"];
    
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
        $contentName = in_array(strtolower($contentName), $this->content) ? $contentName : false;

        if (!$contentName) {
            throw new NotFoundHttpException("No content data found for: {$contentName}");
        }

        return $contentName;
    }
}
