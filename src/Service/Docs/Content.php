<?php

namespace App\Service\Docs;

use App\Service\Content\ContentList;

class Content extends DocBuilder implements DocInterface
{
    public function build()
    {
        return $this
            ->h1('Game Content')
            ->text('Obtain game content data of Final Fantasy XIV.')
            ->text('If you find anything odd and want to look at the raw CSV values, 
                visit: https://github.com/viion/ffxiv-datamining/tree/master/csv')
            ->gap()

            ->h6('Content')
            ->route('/content')
            ->usage('{endpoint}/content')
            ->text('View a list of available content that is accessible in the API. Content is added rapidly 
                when discovered and mapped to the SaintCoincach Schema, with huge effort from the community 
                there is a lot of information availble. Have fun hunting through!')
            ->gap(2)
            
            //
            // content!
            //

            ->h6('Content Lists')
            ->route('/[ContentName]')
            ->usage('{endpoint}/item')
            ->text('Returns a paginated list of content for the specified Content Name')
            ->h5('Pagination structure')
            ->json('{
                "pagination": {
                    "page": 1,
                    "page_next": 2,
                    "page_prev": false,
                    "page_total": 94,
                    "results": 250,
                    "results_per_page": 250,
                    "results_total": 23500
                }
            }')
            ->table(
                [ 'Field', 'Details' ],
                [
                    [ '`page`', 'The current page you have queried' ],
                    [ '`page_next`', 'The next page you can query, if `false` there is no next page and 
                        you\'re at the end' ],
                    [ '`page_prev`', 'The previous page you can query, if `false` there is no previous page 
                        and you\'re at the start' ],
                    [ '`results`', 'The total number of results in the current page' ],
                    [ '`results_per_page`', 'Your current maximum results a page can have' ],
                    [ '`results_total`', 'The total amount of results for the specified content' ],
                ]
            )
            ->gap(2)
            
            //
            // schema
            //
            ->h6('Content Schema')
            ->route('/[ContentName]/schema')
            ->usage('{endpoint}/Item/schema?pretty=1')
            ->text('View the current column and schema information of the content. Schema is automatically built 
                from the "biggest" document for that specific content.')
            ->h5('Response information')
            ->table(
                [ 'Field', 'Details' ],
                [
                    [ '`ColumnCount`', 'The total number of columns found in the schema, this can be even higher if <br> 
                        the content that generated the schema doesn\'t have everything possible!' ],
                    [ '`Columns`', 'A list of columns you can choose using the `columns=X,Y,Z` query parameter.<br> 
                        Sub content is in dot notation.' ],
                    [ '`ContentID`', 'The ID of the content that generated this schema, so you can view the real data!' ],
                    [ '`ContentSchema`', 'Similar to `Columns` but it provides the full structure and provides data <br> 
                        types where possible (these are auto-detected)'],
                ]
            )
            ->gap()

            ->h6('Common Parameters')
            
            // max items
            ->h3('limit')
            ->usage('{endpoint}/Item?columns=ID,Icon,Name&pretty=1&limit=5')
            ->text('Limit the number of items returned by the API.')
            ->list([
                'Default: ' . ContentList::DEFAULT_ITEMS,
                'Maximum: ' . ContentList::MAX_ITEMS,
            ])
            ->gap()
            
            // ids
            ->h3('ids')
            ->usage('{endpoint}/Item?columns=ID,Icon,Name&pretty=1&ids=1675,1676,1677,1678')
            ->text('Filter the ids down if you want data for a specific series of items.')
            ->gap(2)
            
            //
            // Actual content
            //

            ->h6('Content Data')
            ->route('/[ContentName]/[ID]')
            ->usage('{endpoint}/item/1675')
            ->text('Returns information about a specific object including extended information.')
            ->gap()
            
            ->h5('Common Parameters')
            
            ->h3('minify')
            ->usage('{endpoint}/Item/1675?minify=1')
            ->text('Provides a minified version of the content, usually down to 1 depth. This is useful if a piece 
                of content has a lot of extended information that you do not care about but may want it to provide 
                application features.')
            ->gap()
            
            ->h3('columns')
            ->usage('{endpoint}/Item/1675?columns=ID,Name,Icon')
            ->text('Obtain specific columns of information from the content. View the above `/[ContentName]` 
                documentation for information on how this works.')
            
            
            ->line()
            
            //
            // Server List
            //
            ->h6('Server List')
            ->route('/servers')
            ->usage('{endpoint}/servers')
            ->text('A list of servers on the official servers (JA, EN, FR, DE)')
    
            ->route('/servers/dc')
            ->usage('{endpoint}/servers/dc')
            ->text('Another list of servers grouped by their data center.')
            
            ->get();
    }
}
