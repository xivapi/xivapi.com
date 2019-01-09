<?php

namespace App\Service\Docs;

use App\Service\Lodestone\ServiceQueues;

class FreeCompany extends DocBuilder implements DocInterface
{
    public function build()
    {
        return $this
            ->h1('Free Companies')
            ->text('Search and retrieve Free Company data from The Lodestone, provides useful information such
                as profile information and member lists.')

            //
            // Terminology
            //
            ->terms()
            ->gap()

            //
            // Search
            //
            ->h6('Search')
            ->route('/freecompany/search *')
            ->usage("{endpoint}/freecompany/search?name=LeAwesome&server=Phoenix&key=_your_api_key_", true)
            ->text('Search for free companies on The Lodestone. This parses the lodestone in real time 
                so it will be slow for non-cached responses. All search queries are cached for 1 hour,
                it is important to know that Lodestone has a ~6 hour varnish and CDN cache.
                This does not search XIVAPI so free companies found may not be on the service and will 
                be added when requested by their specified lodestone ID.')
            ->queryParams([
                [ '`name`', 'The name of the free company to search, you can use `+` for spaces or let the 
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
            ->h6('Free Company')
            ->route('/freecompany/[lodestone_id]')
            ->usage('{endpoint}/freecompany/9231253336202687179')
            ->text('Get Free Company data, due to the nature of availability on the service this endpoint will 
                return either populated data or nothing, you will have to check the `Info` response to 
                see the current state.')

            ->h5('Pulling different bits of information')
            ->text('By default only the `FreeCompany` data will return, you can request more data 
                using the `data` query.')

            ->h3('data')
            ->usage('{endpoint}/freecompany/9231253336202687179?data=FCM')
            ->text('Pass a long a comma separated list of codes that relate to specific data sets you would like 
                to fetch, these are as follows:')
            ->table(
                ['Code', 'Meaning'],
                [
                    ['`FCM`', 'Free Company Members (Members appear the first time the FC **updates**)'],
                ]
            )

            ->text('Members will be included in this response once the Free Company has been updated at least one time.')
            ->note('Free Company members are not added the same time an FC is added, instead they\'re added 
                when the FC does it first update. This is so that the FC can check it exists first before 
                queuing member updates')
            ->states()

            ->h5('Cron schedule')
            ->auto([
                [ 'add', ServiceQueues::TOTAL_ACHIEVEMENT_UPDATES, '1' ],
                [ 'update', ServiceQueues::TOTAL_ACHIEVEMENT_UPDATES, '1' ]
            ])
            ->text('The updating schedule for free companies will also add and update free company members.')
            ->gap()
    
            ->h3('columns')
            ->text('Filter specific columns from the response, use dot notation to select nested values. 
                For more information on how this works, view `columns` on [/docs/Content](/docs/Content).')

            // Note
            ->line()
            ->lodestoneNotice()
            ->get();
    }
}
