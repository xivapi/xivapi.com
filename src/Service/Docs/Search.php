<?php

namespace App\Service\Docs;

use App\Service\Search\SearchContent;
use App\Service\Search\SearchRequest;

class Search extends DocBuilder implements DocInterface
{
    public function build()
    {
        $indexes = [];
        foreach (SearchContent::LIST as $name) {
            $index      = strtolower($name);
            $default    = in_array($name, SearchContent::LIST_DEFAULT) ? '*(Default)*' : '';
            $indexes[]  = "`{$index}` {$name} {$default}";
        }
        
        return $this
            ->h1('Search')
            ->text('XIVAPI provides the ability to quickly search all game content via ElasticSearch. 
                This search endpoint only searches game content and not: characters, free companies, 
                linkshells or pvp teams. Those have their own dedicated search endpoints as they 
                relay to Lodestone.')
            ->gap()

            ->note("A `string` or `filter` is required to search.")

            //
            // Search
            //
            ->h6('Search')
            ->route('/search')
            ->usage("{endpoint}/search?string=allagan+visor&pretty=1")
            ->text('Search for something! The Search is multi-content and contains combined data, 
                this means your search request covers a vast amount of selected content 
                (which you can further extend via filters) and results are combined based on best-case matching.')
            ->h5('A typical example searching for `ifrit`')
            ->text("
                &nbsp;&nbsp;&nbsp; ⇒ Items (eg: Ifrit\'s Blade) <br>
                &nbsp;&nbsp;&nbsp; ⇒ Recipes (eg: Wind-up Ifrit) <br>
                &nbsp;&nbsp;&nbsp; ⇒ Quests (eg: Ifrit Ain\'t Broke) <br>
                &nbsp;&nbsp;&nbsp; ⇒ NPCs (eg: Ifrit-Egi) <br>
                &nbsp;&nbsp;&nbsp; ⇒ Enemies (eg: Ifrit himself!) <br>
                &nbsp;&nbsp;&nbsp; ⇒ Minions: (eg: Wind-up Ifrit)
            ")
            ->gap()

            ->h6('Common Parameters')

            // indexes=a,b,c
            ->h3('indexes')
            ->usage("{endpoint}/search?indexes=achievement,item,companion&string=ifrit&pretty=1")
            ->text('Search a specific series of indexes separated by commas.')
            ->list($indexes)
            ->gap()
        
        
            // string=hello
            ->h3('string')
            ->usage('{endpoint}/search?string=allagan&pretty=1')
            ->text('Search the default string column for the value "hello". You can get very different 
                results based on what search column you choose and what string algorithm is currently 
                active. Please read the `string_algo` param for detailed information how this works')
            ->gap()
        
            // string column
            ->h3('string_column')
            ->usage('{endpoint}/search?string_column=Description_en&string=the+end+is+nigh&pretty=1')
            ->text('Adjust which column the string search is performed on, by default this is the `Name` 
                column. This can be changed to things like descriptions or even lore columns.  It can only 
                be changed to one of the filterable columns.')
            ->gap()
        
            // string algo
            ->h3('string_algo')
            ->text('**Default:** `'. SearchRequest::defaults()->stringAlgo .'`')
            ->text('Here are some examples of expected outcomes when searching for: **Mother Miounne**')
            ->table(
                [ 'string', 'string_algo', 'Found?', 'Result Number', 'Notes' ],
                [
                    [ 'mother', '`custom`', 'Y', '1', '' ],
                    [ 'mother+miounn', '`custom`', 'Y', '1', '' ],
                    [ 'other+mi', '`custom`', 'Y', '1', '' ],
                    
                    [ 'mother', '`wildcard`', 'Y', '1', '' ],
                    [ 'mother+miounn', '`wildcard`', 'Y', '1', '' ],
                    [ 'other+mi', '`wildcard`', 'N', '', '' ],
                    [ 'other+mi', '`wildcard_plus`', 'Y', '1', '' ],
                    [ 'mo????+miounne', '`wildcard_plus`', 'Y', '1', '`?` will figure out what you mean' ],
                    
                    [ 'mother', '`prefix`', 'Y', '3', '**mother horbill** is #1' ],
                    [ 'mother', '`term`', 'N', '', '' ],
                    [ 'mother+miounne', '`match_phrase_prefix`', 'Y', '3', '' ],
                    [ 'mother', '`match_phrase_prefix`', 'Y', '3', '', '' ],
                    [ 'mother+m', '`match_phrase_prefix`', 'Y', '3', '' ],
                    [ 'other+m', '`match_phrase_prefix`', 'N', '', '' ],
                ]
            )
            ->note('These are all based on what ElasticSearch can do, if you do not get the desired result 
                then hop onto Discord and custom search algorithm could be implemented. For high level details 
                you could read the [Elastic Search Docs](https://www.elastic.co/guide/en/elasticsearch/reference/current/term-level-queries.html).')
            ->gap()
            
            ->h5('String Algorithms')
            ->table(
                [ 'Option', 'Scoring?', 'Details', 'ElasticSearch Docs' ],
                [
                    [
                        '`custom`',
                        'Y',
                        'Performs `wildcard_plus` and a `fuzzy` at the same time, this allows you to search <br> split non-full words, eg: `Ifrit Axe` = `Ifrit\'s Battleaxe`.',
                        '`wildcard` + `fuzzy`',
                    ],
                    [
                        '`wildcard`',
                        'N',
                        'Searches post string, eg: `Aim` would match `*Aim*ing`',
                        '[Docs](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-wildcard-query.html)'
                    ],
                    [
                        '`wildcard_plus`',
                        'N',
                        'Will search each word individually, eg: "Ifrit Axe" searched as: `*ifrit* *axe*`',
                        '[Docs](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-wildcard-query.html)'
                    ],
                    [
                        '`fuzzy`',
                        'Y',
                        'Perform a fuzzy search. Fuzziness = 5',
                        '[Docs](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-fuzzy-query.html)'
                    ],
                    [
                        '`term`',
                        'Y',
                        'Match whole words by keyword terms.',
                        '[Docs](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-term-query.html)'
                    ],
                    [
                        '`prefix`',
                        'Y',
                        'Match a prefix, like a cheap auto-complete',
                        '[Docs](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-prefix-query.html)'
                    ],
                    [
                        '`match`',
                        'Y',
                        'Perform a match query.',
                        '[Docs](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html)'
                    ],
                    [
                        '`match_phrase`',
                        'Y',
                        'Perform a match phrase query',
                        '[Docs](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query-phrase.html)'
                    ],
                    [
                        '`match_phrase_prefix`',
                        'Y',
                        'Perform a match phrase prefix query',
                        '[Docs](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query-phrase-prefix.html)'
                    ],
                    [
                        '`multi_match`',
                        'Y',
                        'Match against multiple string columns, seperated by a comma.',
                        '[Docs](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html)'
                    ],
                    [
                        '`query_string`',
                        'Y',
                        'Perform a query string, this has lots of logic!',
                        '[Docs](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html)'
                    ],
                    [
                        '`similar`',
                        'Y',
                        'Perform a "more like this" similar query',
                        '[Docs](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-mlt-query.html)'
                    ],
                ]
            )
            ->gap()
            // other stuff
        
            ->h6('Minor Parameters')

            ->h3('page')
            ->text('Pull content from a specific page in the search results. You can find your page 
                information in `pagination`')
            ->gap()
            
            ->h3('sort_field')
            ->text('The column to sort the results by. By default all sorting will be handled by 
                ElasticSearch and scores. If you provide a `sort_field` you will loose scoring.')
            ->gap()
            
            ->h3('sort_order')
            ->text('The order the `sort_field` should order by, this will either be `asc` Ascending or 
                `desc` Descending.  ')
            ->gap()
            
            ->h3('limit')
            ->text('Limit the number of results, this cannot go higher than the current max')
            ->text('Current max: `500`')
            ->gap()
            
            ->h3('columns')
            ->text('You can use the global `columns` query parameter to select what fields you want in the search. To help
                make building models easier any column you request will be returned even if that column does not exist
                for the specified content, eg `LevelEquip` will appear on `instantcontent` as `null`. This is to ensure
                responses are consistent with what you ask for.')
            ->text('The default columns are: `_`(index), `_Score`(ElasticSearch Score), `ID`,
                `Name`, `Icon`, `Url`, `UrlType`. All content contains these fields.')
            ->gap()
            
            ->h3('bool')
            ->text('Define how the condition for each filter and string query should be performed, these
                can be: `must`, `should`, `must_not` and `filter`')
            ->text('Info can be found [on the ElasticSearch Docs](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-bool-query.html)')
            ->gap()
        
            ->line()
        
            ->h6('Filters')

            ->h3('filters')
            ->usage('{endpoint}/search?string=bow&indexes=item&pretty=1&filters=LevelItem%3E35,LevelItem%3C50&sort_field=LevelItem&sort_order=desc')
            ->text('Breakdown example of usage link:')
            ->table(
                [ 'Field', 'Operator', 'Value',' Notes' ],
                [
                    [ 'LevelItem', '>', '35', 'Items that are i.level above 35' ],
                    [ 'LevelItem', '<', '50', 'Items that are i.level below 50' ]
                ]
            )
            ->code('filters = Field >= Value, A < B, Field = 1337')

            ->h5('How it works')
            ->text('Provide a comma separated list of filters. The format of the filters is:')
            ->list([
                '`[ column ][ operator ][ value ]`',
                '`LevelItem > 50` - Items that are i.level 50 or above.',
            ])
            
            ->h5('Operators')
            ->text('You can perform filters against all non-text & non-array columns on any searchable content.')
            ->table(
                [ 'Operator', 'Information' ],
                [
                    [ '=',  'Performs a `match`, eg: LevelItem=50 means only items that are level 50.' ],
                    [ '>',  'Performs a "Greater than" `range` query. (gt)' ],
                    [ '>=', 'Performs a "Greater than or equal to" `range` query. (gte)' ],
                    [ '<',  'Performs a "Less than" `range` query. (lt)' ],
                    [ '<=', 'Performs a "Less than or equal to" `range` query. (lte)' ]
                ]
            )
            ->note('Where you see `[LANGUAGE]` change this to the language you would prefer, eg: `Name_en`
                or omit it completely to use query language, eg: `Name` would be whatever `language=X` 
                query is (English if omitted)')
            ->gap()
            
            ->h6('Examples')
            ->list([
                '[LevelItem > 200, LevelItem < 210, LevelEquip > 50]({endpoint}/search?pretty=1&filters=LevelItem>200,LevelItem<210,LevelEquip>50&columns=ID,Name,Icon,LevelItem,LevelEquip,ItemUICategory.Name,ClassJobUse.ClassJobCategory.Name&sort_field=LevelItem&sort_order=asc)',
                '["Ifrit Axe" will find "Ifrit\'s Battleaxe" with a high score]({endpoint}/search?pretty=1&string=Ifrit+Axe&columns=ID,Name,Icon,LevelItem,LevelEquip,ItemUICategory.Name,ClassJobUse.ClassJobCategory.Name)',
                '["rakshasa casting" finds expected items]({endpoint}/search?pretty=1&string=rakshasa+casting&columns=_Score,ID,Name,Icon,LevelItem,LevelEquip)',
            ])
            ->gap()
            
            ->h6('Notes')
            ->text('The search response is in the format:')
            ->json('{
                "SpeedMs": "0",
                "Pagination": { },
                "Results": { }
            }')
            ->text('The SpeedMs is the total time the search took in milliseconds, this is returned from 
                ElasticSearch. The pagination is based on your apps key limits. In the results there will 
                be a field called `_` this is the index for that result (look at indexes further up in the 
                docs for more information on this).')
            ->text('The field `_Score` is the weight score for the `string_algo` used and is decided by ElasticSearch')
        
            ->get();
    }
}
