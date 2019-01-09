<?php

namespace App\Service\Docs;

use App\Service\Lodestone\ServiceQueues;

class Character extends DocBuilder implements DocInterface
{
    public function build()
    {
        return $this
            ->h1('Characters')
            
            ->text('Search and retrieve character data from The Lodestone. Providing useful information
            such as character profile data, minions and mounts obtained, achievements obtained and their relative
            dates. Character friends, their free company, pvp team and much more!')

            //
            // Terminology
            //
            ->terms()
            ->gap(2)

            //
            // Search
            //
            ->h6('Search')
            ->route('/character/search *')
            ->usage("{endpoint}/character/search?name=premium+virtue&server=Phoenix&key=_your_api_key_", true)
            ->text('Search for a character on **The Lodestone**. This does not search XIVAPI but instead 
            it goes directly to lodestone so the response will be "real-time". Responses are cached for 1 hour,
            it is important to know that Lodestone has a ~6 hour varnish and CDN cache.')
            ->queryParams([
                [ '`name`', 'The name of the character to search, you can use `+` for spaces or let the 
                    API handle it for you. If you search very short names you will get lots of responses. 
                    This is an issue with The Lodestone and not much XIVAPI can do about it at this time.' ],

                [ '`server`', '*(optional)* The server to search against, this is case sensitive - You can 
                    obtain a list of valid servers via: https://xivapi.com/servers' ],

                [ '`page`', '*(optional)* Search or move to a specific page.' ]
            ])

            ->gap(2)

            //
            // Get
            //
            ->h6('Character')
            ->route('/character/[lodestone_id]')
            ->usage('{endpoint}/character/730968')
            ->text('Get Character data, due to the nature of availability on the service this endpoint 
                will return either populated data or nothing, you will have to check the `Info` response to 
                see the current state. If the character does not exist on XIVAPI then this first request will
                return the state value 1 and the character will be added within the next few minutes. Query
                the endpoint again after a few minutes and it should contain data.')

            ->h5('Pulling different bits of information')
            ->text('By default only the `Character` data will return, you can request more data using the `data` query.')

            ->h3('data')
            ->usage('{endpoint}/character/730968?data=AC,FR,FC,FCM,PVP')
            ->text('Pass a long a comma separated list of codes that relate to specific data sets you would like 
                to fetch, these are as follows:')
            ->table(
                ['Code', 'Meaning'],
                [
                    ['`AC`',  'Achievements'],
                    ['`FR`',  'Friends'],
                    ['`FC`',  'Free Company (if added to the system)'],
                    ['`FCM`', 'Free Company Members (if added to the system)'],
                    ['`PVP`', 'PVP Team (if added to the system)']
                ]
            )
            ->text('Once you have the data you need, you can further reduce the data using the `columns` query')

            ->h3('columns')
            ->usage('{endpoint}/character/730968?data=AC,FR,FC,FCM,PVP&columns=Character.ID,Character.Name,Achievements.Points,FreeCompany.Name')
            ->text('Filter specific columns from the response, use dot notation to select nested values. 
                For more information on how this works, view `columns` information on [/docs/Content](/docs/Content).')

            ->h5('The "Info" states')
            ->text('At the top level of the response will be a `Info` field, this will contain the 
                `State` and `LastUpdated` timestamp of various different pieces of character data. 
                The `State` is a number and here is what they mean:')
            ->states()

            ->h5('Useful notes')
            ->list([
                'The API does not maintain a list of gear sets persisted over time, instead 
                it provides a `Key` which is in the format: `[ClassID]_[JobID]` you can use this to 
                maintain a list of gear sets per class/job in an associative array. Persistent gear 
                sets may be added in the future on XIVAPI depending on resources and demand.',
                'The API does not maintain information about each Grand Company, if someone moves to a different 
                Grand Company this will overwrite the information in `GrandCompany`.',
                'The characters FreeCompany ID will have an "i" next to it, eg: 
                `"FreeCompanyId": "i9231253336202687179"`, this is because FC ID\'s are big int and my JSON output 
                has a automatic numeric check configuration which will cause this to become a float.'
            ])

            ->h5('Cron schedule')
            ->auto([
                [ 'add', ServiceQueues::TOTAL_CHARACTER_UPDATES, '1' ],
                [ 'update', ServiceQueues::TOTAL_CHARACTER_UPDATES, '1' ],
                [ 'achievements', ServiceQueues::TOTAL_ACHIEVEMENT_UPDATES, '1' ],
                [ 'friends', ServiceQueues::TOTAL_CHARACTER_FRIENDS, '1' ]
            ])
            ->gap(2)


            //
            // Verification
            //
            ->h6('Verification')
            ->route('/character/[lodestone_id]/verification *')
            ->usage('{endpoint}/character/730968/verification?key=_your_api_key_', true)
            ->note('These fields are present on the route `/character/<lodestone_id>` however do not update
                in real-time and you should use the `/Verification` endpoint for a real-time check.')
            ->h5('Example response')
            ->json('{  
                "ID": "730968",
                "Bio": "LoremIpsum",
                "VerificationToken": "XIVD6189DABF298D8018703API",
                "VerificationTokenPass": false
            }')

            ->text('The API will attach a verification token onto the characters profile. This is unique to 
                this character and changes every hour. It is impossible to *guess* and can be used to verify 
                ownership of a character. If you would like to use your own token/verification code, you can 
                parse the "Bio" to see if the code exists on their profile.')
            ->text('If a player enters the `VerificationToken` onto their characters profile page; it will become 
                visible in the `Bio` section and the `VerificationTokenPass` field will return true. This endpoint 
                will cache for **15 seconds** at a time so please consider this when implementing your logic; eg, do 
                not check verification endpoint before asking the player to add the code to their profile page, you can 
                get the code from the `/character/<lodestone_id>` endpoint prior to this action.')
            ->h5('Frequently asked questions')
            ->list([
                '**Does the code have to stay on the profile?** - No, it only needs to be there until you 
                have processed the character through your verification procedure. The `VerificationToken` changes 
                every hour so please do not use this as a persistent way to verify someones ownership. It is intended 
                to be used as a  one-time verification and then you should attach the character information to a 
                internal user account in your system.',
                '**What happens if someone knows another characters code?** - Nothing *so long as the code is not 
                on their profile*. If the user leaves the code on their profile then someone could assume identity 
                of the character via someones application. It is important to build ways to override ownership of a 
                character into your application at all times. This is one of the reasons the code changes every 1 hour.',
                '**What happens if someone leaves the code on their profile?** - Nothing, you should advise players
                 not to do this and to remove it once they have gone through your verification process. However if 
                 they do, after 1 hour the code will become redundant.',
            ])
            ->gap(2)

            //
            // Update
            //
            ->h6('Update')
            ->route('/character/[lodestone_id]/update')
            ->usage('{endpoint}/character/730968/update')
            ->text('Request a character to be prioritised to update. If you hit this endpoint then the supplied 
                character should be updated within the next few minutes. A character can be manually updated 
                every 6 hours (this may change based on the system scale), this restriction is global 
                (anyone who uses the API can request this for the day).')
            ->text('There is an auto-update cycle in place for characters which will update those who are most 
                frequently requested via the API.')
            ->text('The response will contain a number based on the status of the update')
            ->table(
                [ 'Number', 'Details' ],
                [
                    [ '1', 'It has moved to the front of the queue', ],
                    [ '0', 'It has been less than 6 hours since last prioritised, please wait!.' ],
                ]
            )
            ->gap(2)

            //
            // Delete
            //
            ->h6('Delete')
            ->route('/character/[lodestone_id]/delete')
            ->usage('{endpoint}/character/730968/delete?key=_your_api_key_', true)
            ->text('Request a character to be deleted.')
            ->text('A character can only be deleted if it is in State 3 (cannot be found on Lodestone) or it 
                is a duplicate of another character, if it is a duplicate; eg a new character has been created 
                with the same name and server and the old one is still lurking on Lodestone then please provide 
                the `duplicate` parameter. This duplicate will be checked against the one stored. To use this
                correctly, if `/character/1000` is old and your new character is: `/character/1800` then you
                would query: `/character/1000/delete?duplicate=1800`.')
            ->text('Characters that are marked as "not found on Lodestone" will be automatically removed from the
                system every hour.')
            ->queryParams([
                [
                    '`duplicate=[id]`', 'This character is a duplicate of another character, if this 
                        is the case you must provide the duplicate lodestone ID so that information can 
                        be checked. The characters name and server must match' ]
            ])
            ->gap(2)

            // Note
            ->line()
            ->lodestoneNotice()
            ->get();
    }
}
