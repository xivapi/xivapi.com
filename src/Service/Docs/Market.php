<?php

namespace App\Service\Docs;

use App\Service\Common\Environment;
use App\Service\Companion\CompanionTokenManager;

class Market extends DocBuilder implements DocInterface
{
    public function build()
    {
        [$statusHeaders, $statusData] = CompanionTokenManager::getAccountsLoginStatusInformation();
        
        return $this
            ->h1('Market *(beta)*')
            ->text('Get in-game market board information for any server, at any time.')
            ->text('If you need any help, please hop on **Discord**: https://discord.gg/MFFVHWC')
            ->link('View Server Status', 'http://xivapi.com/docs/Market#section-5')
            
            // beta notes
            ->h4('*beta* note:')
            ->note('**BETA** - This feature is in BETA as it is a very unknown territory for developers, SE have not provided
                an open API and could break/change things at any time.')
    
            ->note('**DO NOT DO IT** - Are you thinking of scraping the entire market? Just don\'t. You can go make your own
                implementation, you can find all my stuff open source which includes the logic to connect
                to the Companion App API and you can mass scrape all you want there. If you attempt to mass
                scrape on XIVAPI side at this time your key will be deleted, continuous abuse will mean a ban on
                your account. Please consider what you actually need and come into the discord to talk to
                one of the developers who can guide you into making things much more efficient.')
            
            ->note('**SLOW** - The Companion API is not fast and will take 1-2 seconds to response so please consider
                this when building your apps. The cache on XIVAPI is set to 300 seconds for all Companion
                API calls. It is not know what kind of caching SE use on the app.')
            
            ->note('**SERVERS** - Most servers are supported, however some servers are congested which is preventing
                new characters from being created. We are trying our best to get a character made!')
            
            ->line()
    
            // item prices
            ->h6('Item Prices')
            ->route('/market/[Server]/items/[Item_ID]')
            ->usage("{endpoint}/market/phoenix/items/5?key=_your_api_key_", true)
            ->text('A list of prices for an item on a specific server.')
            ->h4('Response info')
            ->code(json_encode([
                'Item' => [
                    'ID'     => 5,
                    'Icon'   => '/i/020000/020006.png',
                    'Name'   => 'Earth Shard',
                    'Rarity' => 1,
                    'Url'    => 'Url to XIVAPI Item endpoint'
                ],
                'Lodestone' => [
                    'Icon'        => 'Lodestone Icon Hash',
                    'IconHq'      => 'Lodestone Icon Hash (HQ Icon)',
                    'LodestoneId' => 'Lodestone Item ID'
                ],
                'Prices' => [
                    [
                        'CraftSignature' => 'Name of crafter',
                        'ID' => 'Item ID',
                        'IsCrafted' => true,
                        'IsHQ' => true,
                        'Materia' => [],
                        'PricePerUnit' => 1000,
                        'PriceTotal' => 5000,
                        'Quantity' => 5,
                        'RetainerName' => 'Name of retainer selling',
                        'Stain' => 'ID of stain? (aka Dye), not enriched at this time',
                        'Town' =>
                            [
                                'ID' => 'Town ID',
                                'Icon' => 'Icon of town',
                                'Name' => 'Name of town retainer is in',
                                'Url' => 'Url to XIVAPI Town endpoint',
                            ],
                        ]
                    ]
            ], JSON_PRETTY_PRINT), 'json')
            ->gap()
            
            // item history
            ->h6('Item History')
            ->route('/market/[Server]/items/[Item_ID]/history')
            ->usage("{endpoint}/market/phoenix/items/5/history?key=_your_api_key_", true)
            ->text('Get the price history for an item on a specific server.')
            ->h4('Response info')
            ->code(json_encode([
                'History' => [
                    [
                        'CharacterName' => '(string) [ player name ]',
                        'IsHQ'          => true,
                        'PricePerUnit'  => 1000,
                        'PriceTotal'    => 15000,
                        'PurchaseDate'  => '(string) [ unix timestamp ]',
                        'Quantity'      => 15,
                    ],
                    [
                        'CharacterName' => '(string) [ player name ]',
                        'IsHQ'          => true,
                        'PricePerUnit'  => 5000,
                        'PriceTotal'    => 10000,
                        'PurchaseDate'  => '(string) [ unix timestamp ]',
                        'Quantity'      => 2,
                    ]
                ],
                'Item' => [
                    'ID'     => 5,
                    'Icon'   => '/i/020000/020006.png',
                    'Name'   => 'Earth Shard',
                    'Rarity' => 1,
                    'Url'    => '(string) XIVAPI Endpoint Url'
                ],
            ], JSON_PRETTY_PRINT), 'json')
            ->gap()
            
            // item category listing
            ->h6('Item Category Listing')
            ->route('/market/[Server]/category/[Category_ID]')
            ->usage("{endpoint}/market/phoenix/category/10?key=_your_api_key_", true)
            ->text('Get the list of items and their sale quantity in this category.')
            ->h4('Response Info')
            ->text(' The response is just an array of results.')
            ->code(json_encode([
                [
                    "ID" => 5,
                    'Item' => [
                        'ID'     => 5,
                        'Icon'   => '/i/020000/020006.png',
                        'Name'   => 'Earth Shard',
                        'Rarity' => 1,
                        'Url'    => '(string) XIVAPI Endpoint Url'
                    ],
                    'Quantity' => 80,
                ],
                [
                    "ID" => 6,
                    'Item' => [
                        'ID'     => 6,
                        'Icon'   => '/i/020000/020007.png',
                        'Name'   => 'Lightning Shard',
                        'Rarity' => 1,
                        'Url'    => 'Url to XIVAPI Item endpoint'
                    ],
                    'Quantity' => 45,
                ]
            ], JSON_PRETTY_PRINT), 'json')
            ->gap()
    
            // market categories
            ->h6('Market Categories')
            ->route('/market/categories')
            ->usage("{endpoint}/market/categories?key=_your_api_key_", true)
            ->text('Get a list of market categories, this is the ID used in the endpoint:
                `/market/[server]/category/[category_id]`')
            ->h4('Response Info')
            ->text('The response is just an array of categories.')
            ->code(json_encode([
                [
                    'ID'     => 9,
                    'Icon'   => '/i/060000/060101.png',
                    'Name'   => 'Pugilist\'s Arms',
                    'Url'    => 'Url to XIVAPI Item endpoint',
                    'Order'  => 4,
                ],
                [
                    'ID'     => 10,
                    'Icon'   => '/i/060000/060102.png"',
                    'Name'   => 'Gladiator\'s Arms',
                    'Url'    => 'Url to XIVAPI Item endpoint',
                    'Order'  => 0,
                ]
            ], JSON_PRETTY_PRINT), 'json')
            
            ->line()
            
            ->h6('Server Status')
            ->text('Below is a table of each server and their market accessibility status. If your server is
                "offline" it may mean that the server is congested and new characters cannot be made or the
                companion API is having issues accessing the Data Center where the server is based.')
            ->text(constant(Environment::CONSTANT) === 'staging'
                ? 'Please ignore the table below in staging as the status is only updated in the prod environment'
                : 'Live as of: '. date('Y-m-d H:i') .' UTC')
            ->table($statusHeaders, $statusData)
            ->gap(2)

            ->h6('Examples')
            ->text('Some very basic concepts using the Market API')
            ->table(
                ['Language', 'Link', 'Information'],
                [
                    [
                        'Javascript',
                        '[JS Fiddle](http://jsfiddle.net/vekien/Lsu3pw9q/134/embedded/result/)',
                        'Super simple example using bootstrap, jquery and some vanilla javascript.'
                    ]
                ]
            )
            ->gap()

            ->h6('Open Source')
            ->text('The library the handles all companion interaction is open source (PHP)')
            ->link('GitHub: companion-php', 'https://github.com/xivapi/companion-php')

            ->get();
    }
}
