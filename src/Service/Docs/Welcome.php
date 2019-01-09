<?php

namespace App\Service\Docs;

class Welcome extends DocBuilder implements DocInterface
{
    public function build()
    {
        return $this

            ->h1('Welcome')
            ->text('The XIVAPI provides a massive amount of FINAL FANTASY XIV game data in a JSON format via 
                a REST API. You can fetch information on all sorts of game content that has been discovered and 
                mapped in the SaintCoinach Schema. In addition it provides Character, Free Company, Linkshell, PvPTeams 
                and Lodestone information!')
            
            ->h6('Patreon')
            ->text('<a href="https://www.patreon.com/bePatron?u=13230932" target="_blank"><img src="https://c5.patreon.com/external/logo/become_a_patron_button.png"></a>')
            ->line()
            
            //
            // ENDPOINTS
            //
            ->h6("Endpoints")
            ->table(['Production', 'Staging', 'Local'], [
                [ 'https://xivapi.com', 'https://staging.xivapi.com', 'http://xivapi.local' ]
            ])
            ->gap()

            //
            // API Keys
            //
            ->h6('Apps & API Keys')
            ->h4('Obtain a key: [Create an application](/app)')
            ->text('The API is very public and can be used without any keys but this will have 
                some restrictions. Without using a key you will have restricted access, this is to reduce
                abuse and web crawling. Keys are free so please get one! Below are the restrictions:')
            ->table(
                [ 'State', 'Rate Limit', 'Information' ],
                [
                    [
                        'No Key',
                        '1/req/second',
                        'This is the default state when no key is provided. Some endpoints cannot be accessed using the default key.'
                    ],
                    [
                        'Limited Key',
                        '2/req/second',
                        'A key that has just been created will be "limited" for the first hour.'
                    ],
                    [
                        'Full Key',
                        '10/req/second',
                        'An unrestricted key.'
                    ]
                ]
            )
            ->text('Endpoint restrictions for "No Key":')
            ->list([
                '`/market/{server}/items/{itemId}`',
                '`/market/{server}/items/{itemId}/history`',
                '`/market/{server}/category/{category}`',
                '`/market/categories`',
                '`/character/search`',
                '`/character/{id}/verification`',
                '`/character/{id}/delete`',
                '`/freecompany/search`',
                '`/freecompany/{id}/delete`',
                '`/linkshell/search`',
                '`/linkshell/{id}/delete`',
                '`/pvpteam/search`',
                '`/pvpteam/{id}/delete`',
                '`/lodestone/devposts`'
            ])

            ->gap()
            ->h3('key')
            ->usage('{endpoint}/Item?key=_your_api_key_')
            ->text('Keys provide usage statistics and have rate limits on them to prevent abuse of 
                the API. You can re-generate your API key at any time, make as many apps as you like 
                and use them freely.')


            ->text('A default key also has the following restrictions as they interact with The Lodestone:')

            ->h4('Rate-Limiting')
            ->text('Apps have their own individual rate limits. This is per ip per key. IPs are not stored in 
                the system but instead are hashed and used as a tracking point for that "second". Your number of 
                hits per second can be viewed in your app as the current requests per/second can be seen.')
            ->gap()
            ->gap()

            //
            //  GLOBAL QUERIES
            //
            ->h6('Global Queries')
            ->text('These query parameters can be set on all endpoints')

            // language=X
            ->h3('language')
            ->usage('{endpoint}/Item/1675?language=fr')
            ->text('This will tell the API to handle the request and the response in the specified language.')
            ->queryParams([
                [ '`en`', 'English' ],
                [ '`ja`', 'Japanese' ],
                [ '`de`', 'German' ],
                [ '`fr`', 'French' ],
                [ '`cn`', 'Chinese (WIP)' ],
                [ '`kr`', 'Korean (WIP)' ],
            ])

            ->text('To help with development; you may want to use the simplified field `Name`. 
                If you can provide the query `language=fr` and now `Name` will be the French name. 
                This is also extended to other string fields such as Descriptions.')

            ->text('Search will use the language parameter to decide which field to query the `string` 
                against, for example: `language=fr&string=LeAwsome` will search for `LeAwesome` on the 
                field `Name_fr`.')

            ->gap()

            // pretty printing
            ->h3('pretty')
            ->usage('{endpoint}/Item/1675?pretty=1')
            ->text('This will provide a nice pretty JSON response, this is intended for debugging 
                purposes. Don\'t use this in production as it adds weight to the response and queries will be longer.')
            ->bold('Example difference')
            ->code('{"ClassJobCategory.Name":"PLD","ID":1675,"Icon":"\/img\/ui\/game\/icon4\/1\/1675.png","Name":"Curtana"}', 'json')
            ->text('Will become:')
            ->json('{  
                "ClassJobCategory.Name": "PLD",
                "ID": 1675,
                "Icon": "\/img\/ui\/game\/icon4\/1\/1675.png", "Name":
                "Curtana"
            }')
            ->gap()

            // snake_case
            ->h3('snake_case')
            ->usage('{endpoint}/Item/1675?snake_case=1')
            ->text('All API responses by default are UpperCase as this is the format the game
                data is extracted, to maintain consistency all endpoints will return data
                 in UpperCase format, however if you prefer snake case this will convert all 
                 UpperCaseFields into lower_snake_case_fields.')
            ->gap()
    
            // columns
            ->h3('columns')
            ->usage('{endpoint}/Item?columns=ID,Icon,Name&pretty=1')
            ->text('This is a global query and can be used on any endpoint.')
            ->text('This query allows specific columns to be pulled from the data and exclude the rest of
                the JSON response. This allows you narrow down to specific bits of information and reduce
                the size of the payload to your application. For nested data you can use dot notation
                (to a max of 10 nested nodes) to access it, for example:')
            ->usage('{endpoint}/Item?columns=ID,Icon,Name,ClassJobCategory.Name')
            ->text('- ID, Icon, Name, ClassJobCategory.Name (nested)')
            ->json('[
                {
                    "ID": 2901,
                    "Icon": "\/i\/040000\/040635.png",
                    "Name": "Choral Chapeau",
                    "ClassJobCategory": {
                        "Name": "BRD"
                    }
                },
                {
                    "ID": 2902,
                    "Icon": "\/i\/041000\/041041.png",
                    "Name": "Healer\'s Circlet",
                    "ClassJobCategory": {
                        "Name": "WHM"
                    }
                },
                {
                    "ID": 2903,
                    "Icon": "\/i\/040000\/040634.png",
                    "Name": "Wizard\'s Petasos",
                    "ClassJobCategory": {
                        "Name": "BLM"
                    }
                }
            ]')
            ->text('Sometimes a piece of data will have an array of sub data, for example:')
            ->json('{
                "ID": 1,
                "Name": "Example",
                "Items": [
                    {
                        "Name": "foo"
                    },
                    {
                        "Name": "bar"
                    }
                ]
            }')
            ->bold('List Content')
            ->text('If any response is a "List" (contains: `Pagination` and `Results` at the top level
                then the `columns=X` will be performed on each result as opposed to globally, this means
                you can reduce the data for every list item in a search or on `/<ContentName>` lists.')
            
            ->bold('Nested arrays')
            ->text('If a field is an array of data, the entire array contents would return, you could reduce
                this further if you wish:')
            ->text('To access the data in `Items` individually you could do')
            ->code('columns=Items.0.Name,Items.1.Name')
            ->text('However, if you imagine an array having 50 items this could become tedious. You can therefore use a
                count format, eg:')
            ->code('columns=Items.*50.Name')
            ->text('This will return 50 rows from the column `Items` using the index `Name`, even if there
                are only 30 legitimate columns, 50 fields will be returned. This is intentional so you can
                build models knowing at all times X number of columns will return. You can use the FFXIV CSV
                files to know exactly how many there are exactly.')
            ->text('If you are unsure on the exact number of entries in the array or you do not mind a flexiable amount
                you can ignore the number to get all entries in the array, eg:')
            ->code('columns=Items.*.Name')

            ->gap(2)

            ->h3('tags')
            ->usage('{endpoint}/servers?key=xxxx&tags=lorem,ipsum')
            ->text('You can add tracking counters to your app for whatever purpose using "tags". Separate tags 
                with commas and they will appear in your dashboard with a counter next to them. You can have 
                as many tags you would like and counts will store for a period of 30 days before taping off and 
                being removed if they become inactive.')
            ->text('A tag must be alpha numeric and allows dashes and underscores.')
            ->gap()
            
            ->h4('Ints')
            ->text('The API will return `ints` as `strings` whenever an numeric value is a length of 10 or more,
                this means that unix timestamps, FC/LS IDs and any other long numbers will return as string
                and not cause overflow issues.')
            ->line()

            //
            // SaintCoinach
            //
            ->h6('SaintCoinach Schema')
            ->text('You can find the Saint-Coinach schema here:')
            ->list([ 'https://github.com/ufx/SaintCoinach/blob/master/SaintCoinach/ex.json' ])
            ->text('The schema is a huge JSON file that describes the EXD files found in the FFXIV game files. 
                Many community members take time to datamine and understand the way the EXD files are mapped and 
                this file helps describe it in a universal format.')

            ->h5('Special fields and schema differences')
            ->text('Some fields in the API are not part of the SaintCoinach Schema and have been implemented for 
                ease of use. For example: `NPC.Quests` provides all quests related to the NPC.')
            ->text('Other files are API specific, for example: GamePatch is the API\'s patching system, 
                `GameContentLinks` are reverse links from one content to another. Make sure to use the schema 
                endpoint on the API to see what you can obtain :)')
            ->text('In addition, to make things more simpler in the templates, some fields have been globally 
                simplified for example a contents "`Singular`" field is known as "`Name`", a "`Masculinity`"  
                would also be converted to "`Name`" with "`Feminine`" converted to "`NameFemale`"')

            ->h5('Field name change list')
            ->table(
                [ 'Content name', 'Schema field name', 'API field name' ],
                [
                    [ 'BNpcName', 'Singular', 'Name' ],
                    [ 'ENpcResident', 'Singular', 'Name' ],
                    [ 'Mount', 'Singular', 'Name' ],
                    [ 'Companion', 'Singular', 'Name' ],
                    [ 'Title', 'Masculine', 'Name' ],
                    [ 'Title', 'Feminine', 'NameFemale' ],
                    [ 'Race', 'Masculine', 'Name' ],
                    [ 'Race', 'Feminine', 'NameFemale' ],
                    [ 'Tribe', 'Masculine', 'Name' ],
                    [ 'Tribe', 'Feminine', 'NameFemale' ],
                    [ 'Quest', 'Id', 'TextFile' ],
                ]
            )
            ->gap()

            ->text('Another minor thing to be aware of is confusing names for various different content schemas, 
                for some reason Square-Enix have named stuff in the game files that do not match the in-game 
                representation. Below is a table of common content types that you will see in game and their 
                data-file name:')

            ->table(
                [ 'Content name', 'Schema name', 'details' ],
                [
                    [ 'Minions', 'Companion', 'I do not know why SE call them Companions' ],
                    [ 'Chocobo Companion', 'Buddy', 'Again, confusing with Minions...' ]
                ]
            )
            ->line()

            //
            // Open source
            //
            ->h6('Open Source')
            ->text('XIVAPI is all open source with many prototypes, libraries and other resources available on
                github:')
            ->list([
                '**Organisation**: https://github.com/xivapi',
                '**Source Code**: https://github.com/xivapi/xivapi.com',
            ])
            ->note('The xivapi.com is not really looking for contributions at this time as much of the
                functionality is being moved to micro services.')
            ->gap()

            ->h6('Other libraries')
            ->text('Other cool stuff for you!')
            ->table(
                [
                    'Name', 'Info'
                ],
                [
                    [
                        'Angular-Client',
                        '[https://github.com/xivapi/angular-client](https://github.com/xivapi/angular-client)<br>An Angular client for interacting with the XIVAPI'
                    ],
                    [
                        'Lodestone Parser PHP',
                        '[https://github.com/xivapi/lodestone-parser](https://github.com/xivapi/lodestone-parser)<br>A Lodestone Parser written in PHP',
                    ],
                    [
                        'Game Data',
                        '[https://github.com/xivapi/xivapi-data](https://github.com/xivapi/xivapi-data)<br>Extracting game data using SaintCoinach and automatically building content documents for the REST API'
                    ],
                    [
                        'Mappy',
                        '[https://github.com/xivapi/xivapi-mappy](https://github.com/xivapi/xivapi-mappy)<br>Parse map information from FFXIV via the games memory'
                    ],
                    [
                        'Companion PHP',
                        '[https://github.com/xivapi/companion-php](https://github.com/xivapi/companion-php)<br>A PHP library that exposes the FFXIV Companion App API'
                    ]
                ]
            )
            ->gap()
            ->line()

            //
            // Note
            //
            ->h5('HTTP or HTTPS?')
            ->text('**Please use: `https`**')
            ->text('Both are currently supported on the API as there is no sensitive data being provided 
                or available via the API. This may change in future so please try use HTTPS to 
                avoid your applications breaking.')

            ->get();
    }
}
