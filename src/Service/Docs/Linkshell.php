<?php

namespace App\Service\Docs;

use App\Service\Lodestone\ServiceQueues;

class Linkshell extends DocBuilder implements DocInterface
{
    public function build()
    {
        return $this
            ->h1('Linkshells')
            ->text('Search and retrieve Linkshell data from The Lodestone.')

            //
            // Terminology
            //
            ->terms()
            ->gap(2)

            //
            // Search
            //
            ->h6('Search')
            ->route('/linkshell/search *')
            ->usage("{endpoint}/linkshell/search?name=LeAwesome&server=Phoenix&key=_your_api_key_", true)
            ->text('Search for linkshells on The Lodestone. This parses the lodestone in real time so it will 
                be slow for uncached responses. All search queries are cached for 1 hour. This does not search 
                XIVAPI so linkshells found may not be on the service and will be added when requested by their 
                specified lodestone ID.')
            ->queryParams([
                [ '`name`', 'The name of the linkshell to search, you can use `+` for spaces or let the API 
                    handle it for you. If you search very short names you will get lots of responses. This is 
                    an issue with The Lodestone and not much XIVAPI can do about it at this time.' ],
                [ '`server`', '*(optional)* The server to search against, this is case sensitive - You can 
                    obtain a list of valid servers via: https://xivapi.com/servers' ],
                [ '`page`', '*(optional)* Search or move to a specific page.' ]
            ])
            ->gap(2)

            //
            // Get
            //
            ->h6('Linkshell Members')
            ->route('/linkshell/[lodestone_id]')
            ->usage('{endpoint}/linkshell/19984723346535274')
            ->text('Get Linkshell data, due to the nature of availability on the service this endpoint 
                will return either populated data or nothing, you will have to check the `Info` 
                response to see the current state.')
            ->states()
            ->h5('Update schedule')
            ->auto([
                [ 'add', ServiceQueues::TOTAL_LINKSHELL_UPDATES, '1' ],
                [ 'update', ServiceQueues::TOTAL_LINKSHELL_UPDATES, '1' ]
            ])
            ->text('The updating schedule for linkshells will also add and update linkshell members.')
            ->gap()
    
            ->h3('columns')
            ->text('Filter specific columns from the response, use dot notation to select nested values. 
                For more information on how this works, view `columns` on [/docs/Content](/docs/Content).')
            ->get();
    }
}
