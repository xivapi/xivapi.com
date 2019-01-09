<?php

namespace App\Service\Docs;

class Lodestone extends DocBuilder implements DocInterface
{
    public function build()
    {
        return $this
            ->h1('Lodestone')
            ->text('Returns information from the official "The Lodestone" website: 
                https://na.finalfantasyxiv.com/lodestone/')
            ->note('At this time (August 2018) only the NA Lodestone is being parsed, 
                therefore `?language=X` query will not effect the output. Work is in progress 
                to add multi-language support.')

            ->gap()
            
            // Lodestone
            ->route('/lodestone')
            ->usage('{endpoint}/lodestone')
            ->text('WORK IN PROGRESS')
            ->text('Returns a collection of information from the Lodestone endpoints, included is:')
            ->list([
                'Banners',
                'News',
                'Topics',
                'Notices',
                'Maintenance',
                'Updates',
                'Status',
                'WorldStatus',
                'DevBlog (latest)',
                'DevPosts (latest)',
            ])
            ->text('The above data in this collection is generated every hour and cached, 
                providing a nice quick response.')
            ->gap()
            
            // News
            ->h6('News')
            ->route('/lodestone/news')
            ->usage('{endpoint}/lodestone/news')
            ->text('Gets the latest news information from the homepage.')
            ->gap()
    
            // Notices
            ->h6('Notices')
            ->route('/lodestone/notices')
            ->usage('{endpoint}/lodestone/notices')
            ->text('Gets the latest notices.')
            ->gap()
    
            // Maintenance
            ->h6('Maintenance')
            ->route('/lodestone/maintenance')
            ->usage('{endpoint}/lodestone/maintenance')
            ->text('Gets the latest maintenance posts (Does not contain specific details such as times).')
            ->gap()
    
            // Updates
            ->h6('Updates')
            ->route('/lodestone/updates')
            ->usage('{endpoint}/lodestone/updates')
            ->text('Get a list of update posts.')
            ->gap()
    
            // Status
            ->h6('Status')
            ->route('/lodestone/status')
            ->usage('{endpoint}/lodestone/status')
            ->text('Get a list of status posts.')
            ->gap()
    
            // WorldStatus
            ->h6('World Status')
            ->route('/lodestone/worldstatus')
            ->usage('{endpoint}/lodestone/worldstatus')
            ->text('Get world status information on the FFXIV Servers.')
            ->gap()
    
            // DevBlogs
            ->h6('Dev Blog')
            ->route('/lodestone/devblog')
            ->usage('{endpoint}/lodestone/devblog')
            ->text('Get the latest Developer Blog information, this is pulled from an XML feed.')
            ->gap()

            // DevPosts
            ->h6('Dev Posts (forums)')
            ->route('/lodestone/devposts')
            ->usage('{endpoint}/lodestone/devposts')
            ->text('Get the latest Dev posts from the official forums.')
            ->gap()
    
            // Feats
            ->h6('Feasts')
            ->route('/lodestone/feasts')
            ->usage('{endpoint}/lodestone/feasts')
            ->text('Get information on Feasts leaderboards')
            ->text('- `season=X` Pass along the season number to parse')
            ->text('- ?? - You can find more parameters on the feast page: 
                https://eu.finalfantasyxiv.com/lodestone/ranking/thefeast/')
            ->gap()
    
            // DeepDungeon
            ->h6('Deep Dungeon')
            ->route('/lodestone/deepdungeon')
            ->usage('{endpoint}/lodestone/deepdungeon')
            ->text('Get information on DeepDungeon rankings')
            ->text('- You can find more parameters on the deep dungeon page: 
                https://eu.finalfantasyxiv.com/lodestone/ranking/deepdungeon/')
            ->gap()
            
            ->note('All these routes query the lodestone directly in realtime. XIVAPI will 
                cache the lodestone response for a set amount of time. Please do not hammer these requests 
                or your IP will be blacklisted from the service.')
            
            ->get();
    }
}
