<?php

namespace App\Common\Entity;

use App\Common\Service\Redis\Redis;
use App\Common\Utils\Language;
use App\Common\Utils\Random;
use Doctrine\Common\Collections\ArrayCollection;
use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\Request;

/**
 * @ORM\Table(
 *     name="users_alerts",
 *     indexes={
 *          @ORM\Index(name="uniq", columns={"uniq"}),
 *          @ORM\Index(name="item_id", columns={"item_id"}),
 *          @ORM\Index(name="last_checked", columns={"last_checked"}),
 *          @ORM\Index(name="server", columns={"server"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Common\Repository\UserAlertRepository")
 */
class UserAlert
{
    const TRIGGER_FIELDS = [
        // Prices
        'Prices' => [
            'Prices_Added',
            'Prices_CreatorSignatureName',
            'Prices_IsCrafted',
            'Prices_IsHQ',
            'Prices_HasMateria',
            'Prices_PricePerUnit',
            'Prices_PriceTotal',
            'Prices_Quantity',
            'Prices_RetainerName',
            //'Prices_StainID',
            'Prices_TownID',
        ],

        // History
        'History' => [
            'History_Added',
            'History_CharacterName',
            'History_IsHQ',
            'History_PricePerUnit',
            'History_PriceTotal',
            'History_PurchaseDate',
            'History_Quantity',
        ],
    ];
    
    const TRIGGER_OPERATORS = [
        1 => '[ > ] Greater than',
        2 => '[ >= ] Greater than or equal to',
        3 => '[ < ] Less than',
        4 => '[ <= ] Less than or equal to',
        5 => '[ = ] Equal-to',
        6 => '[ != ] Not equal-to',
        7 => '[ % ] Is Divisible by',
    ];
    
    const TRIGGER_OPERATORS_SHORT = [
        1 => '>',
        2 => '>=',
        3 => '<',
        4 => '<=',
        5 => '=',
        6 => '!=',
        7 => '%',
    ];
    
    // what to do once the trigger is fired
    const TRIGGER_ACTION_CONTINUE = 'continue';
    const TRIGGER_ACTION_DELETE   = 'delete';
    const TRIGGER_ACTION_PAUSE    = 'pause';

    #-------------------------------------------------------------------------------------------------------------------
    
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="guid")
     */
    private $id;
    /**
     * @var string
     * @ORM\Column(type="string", length=8, unique=true)
     */
    private $uniq;
    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User", inversedBy="alerts")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $itemId;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $added;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $activeTime;
    /**
     * @var int
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private $lastChecked = 0;
    /**
     * @var string
     * @ORM\Column(type="string", length=100)
     */
    private $name;
    /**
     * @var string
     * @ORM\Column(type="string", length=100)
     */
    private $server;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $expiry = 0;
    /**
     * @var array
     * @ORM\Column(type="array")
     */
    private $triggerConditions = [];
    /**
     * @var string
     * @ORM\Column(type="string", length=100)
     */
    private $triggerType;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $triggerLastSent = 0;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $triggersSent = 0;
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $triggerAction = self::TRIGGER_ACTION_CONTINUE;
    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $triggerDataCenter = false;
    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $triggerHq = false;
    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $triggerNq = false;
    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default": true})
     */
    private $triggerActive = true;
    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $notifiedViaEmail = false;
    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $notifiedViaDiscord = false;
    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $keepUpdated = false;
    /**
     * @ORM\OneToMany(targetEntity="UserAlertEvent", mappedBy="userAlert", cascade={"remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"added" = "DESC"})
     */
    private $events;

    public function __construct()
    {
        $this->id           = Uuid::uuid4();
        $this->added        = time();
        $this->activeTime   = time();
        $this->events       = new ArrayCollection();
        $this->uniq         = strtoupper(Random::randomHumanUniqueCode(8));
    }

    /**
     * Build a new alert from a json payload request.
     */
    public static function buildFromRequest(Request $request, ?UserAlert $alert = null): UserAlert
    {
        $json  = \GuzzleHttp\json_decode($request->getContent());
        $alert = $alert ?: new UserAlert();
 
        $alert
            ->setItemId($json->alert_item_id ?? $alert->getItemId())
            ->setName($json->alert_name ?? $alert->getName())
            ->setTriggerDataCenter($json->alert_dc ?? $alert->isTriggerDataCenter())
            ->setTriggerType($json->alert_type ?? $alert->getTriggerType())
            ->setTriggerHq($json->alert_hq ?? $alert->isTriggerHq())
            ->setTriggerNq($json->alert_nq ?? $alert->isTriggerNq())
            ->setNotifiedViaDiscord($json->alert_notify_discord ?? $alert->isNotifiedViaDiscord())
            ->setNotifiedViaEmail($json->alert_notify_email ?? $alert->isNotifiedViaEmail())
            ->setKeepUpdated($json->alert_dps_perk ?? $alert->isKeepUpdated());
        
        // reset trigger conditions
        $alert->setTriggerConditions([]);
        
        // add triggers
        foreach($json->alert_triggers as $trigger) {
            if (isset(self::TRIGGER_OPERATORS[$trigger->alert_trigger_op]) === false) {
                throw new \Exception('Invalid operator');
            }

            $trigger->alert_trigger_field = substr(strip_tags($trigger->alert_trigger_field), 0, 50);
            $trigger->alert_trigger_value = substr(strip_tags($trigger->alert_trigger_value), 0, 50);
            
            $alert->addTriggerCondition(
                $trigger->alert_trigger_field,
                $trigger->alert_trigger_op,
                $trigger->alert_trigger_value
            );
        }
        
        return $alert;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id)
    {
        $this->id = $id;

        return $this;
    }
    
    public function getUniq(): ?string
    {
        return $this->uniq;
    }
    
    public function setUniq(string $uniq)
    {
        $this->uniq = $uniq;
        
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function setItemId(int $itemId)
    {
        $this->itemId = $itemId;

        return $this;
    }
    
    public function getItem()
    {
        $item = Redis::Cache()->get("xiv_Item_{$this->itemId}");
        $item = Language::handle($item);
        return $item;
    }

    public function getAdded(): int
    {
        return $this->added;
    }

    public function setAdded(int $added)
    {
        $this->added = $added;

        return $this;
    }

    public function getActiveTime(): int
    {
        return $this->activeTime;
    }

    public function setActiveTime(int $activeTime)
    {
        $this->activeTime = $activeTime;

        return $this;
    }

    public function getLastChecked(): int
    {
        return $this->lastChecked;
    }
    
    public function setLastChecked(int $lastChecked)
    {
        $this->lastChecked = $lastChecked;
        
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = substr(strip_tags($name), 0, 100);

        return $this;
    }

    public function getServer(): string
    {
        return $this->server;
    }

    public function setServer(string $server)
    {
        $this->server = preg_replace("/[^a-zA-Z0-9]/", null, $server);

        return $this;
    }

    public function getExpiry(): int
    {
        return $this->expiry;
    }

    public function setExpiry(int $expiry)
    {
        $this->expiry = $expiry;

        return $this;
    }

    public function isExpired()
    {
        return time() > $this->expiry;
    }

    public function getTriggerConditions(): array
    {
        return $this->triggerConditions;
    }

    /**
     * Formats conditions.
     */
    public function getTriggerConditionsFormatted(): array
    {
        $conditions = [];
        foreach ($this->triggerConditions as $triggerCondition) {
            [$field, $operator, $value] = explode(',', $triggerCondition);
    
            $operatorLong = self::TRIGGER_OPERATORS[$operator];
            $operatorShort = self::TRIGGER_OPERATORS_SHORT[$operator];
            
            $conditions[] = [$field, $operator, $value, $operatorShort, $operatorLong];
        }

        return $conditions;
    }

    public function setTriggerConditions(array $triggerConditions)
    {
        $this->triggerConditions = $triggerConditions;

        return $this;
    }
    
    public function getTriggerType(): string
    {
        return $this->triggerType;
    }
    
    public function setTriggerType(string $triggerType)
    {
        $this->triggerType = $triggerType;
        
        return $this;
    }
    
    public function addTriggerCondition($field, $op, $value)
    {
        $this->triggerConditions[] = sprintf("%s,%s,%s", $field, $op, $value);
        return $this;
    }

    public function getTriggerLastSent(): int
    {
        return $this->triggerLastSent;
    }

    public function setTriggerLastSent(int $triggerLastSent)
    {
        $this->triggerLastSent = $triggerLastSent;

        return $this;
    }

    public function getTriggersSent(): int
    {
        return $this->triggersSent;
    }

    public function setTriggersSent(int $triggersSent)
    {
        $this->triggersSent = $triggersSent;

        return $this;
    }
    
    public function incrementTriggersSent(): self
    {
        $this->triggersSent++;
        return $this;
    }

    public function getTriggerAction(): int
    {
        return $this->triggerAction;
    }

    public function setTriggerAction(int $triggerAction)
    {
        $this->triggerAction = $triggerAction;

        return $this;
    }

    public function isTriggerDataCenter(): bool
    {
        return $this->triggerDataCenter;
    }

    public function setTriggerDataCenter(bool $triggerDataCenter)
    {
        $this->triggerDataCenter = $triggerDataCenter;

        return $this;
    }

    public function isTriggerHq(): bool
    {
        return $this->triggerHq;
    }

    public function setTriggerHq(bool $triggerHq)
    {
        $this->triggerHq = $triggerHq;

        return $this;
    }

    public function isTriggerNq(): bool
    {
        return $this->triggerNq;
    }

    public function setTriggerNq(bool $triggerNq)
    {
        $this->triggerNq = $triggerNq;

        return $this;
    }

    public function isTriggerActive(): bool
    {
        return $this->triggerActive;
    }

    public function setTriggerActive(bool $triggerActive)
    {
        $this->triggerActive = $triggerActive;

        return $this;
    }

    public function isNotifiedViaEmail(): bool
    {
        return $this->notifiedViaEmail;
    }

    public function setNotifiedViaEmail(bool $notifiedViaEmail)
    {
        $this->notifiedViaEmail = $notifiedViaEmail;

        return $this;
    }

    public function isNotifiedViaDiscord(): bool
    {
        return $this->notifiedViaDiscord;
    }

    public function setNotifiedViaDiscord(bool $notifiedViaDiscord)
    {
        $this->notifiedViaDiscord = $notifiedViaDiscord;

        return $this;
    }

    public function getEvents()
    {
        return $this->events;
    }

    public function setEvents($events)
    {
        $this->events = $events;

        return $this;
    }
    
    public function recentEvent()
    {
        /** @var UserAlertEvent $event */
        $event = $this->events->last() ?: null;
        
        if ($event == null) {
            return null;
        }
    
        /**
         * Build mini market snapshot
         */
        $market = [];
        foreach ($event->getData() as $row) {
            $server = $row[0];
            $prices = $row[1];
            $prices->_Server = $server;
            $market[] = $prices;
        }
        
        return $market;
    }
    
    public function recentEventDate()
    {
        /** @var UserAlertEvent $event */
        $event = $this->events->last() ?: null;
    
        if ($event == null) {
            return null;
        }
        
        return $event->getAdded();
    }
    
    public function isKeepUpdated(): bool
    {
        return $this->keepUpdated;
    }
    
    public function setKeepUpdated(bool $keepUpdated)
    {
        $this->keepUpdated = $keepUpdated;
        
        return $this;
    }
}
