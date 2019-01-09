<?php

namespace App\Service\Docs;

use App\Entity\MapPosition;
use Doctrine\ORM\EntityManagerInterface;

class MapData extends DocBuilder implements DocInterface
{
    public function build()
    {
        $totalMapPositions = $this->em->getRepository(MapPosition::class)->getTotal();

        return $this
            ->h1('Map Information')
            ->note('At this time (September 2018) there is no caching on the Map Data and any positions will
                be available in real-time. This is because so much needs mapping and it is still in beta testing.
                Once all maps are "mapped", a cache will be placed infront of Map Data endpoints.')
            
            ->text('XIVAPI provides map data that has been gathered together by the FFXIV
                community using a tool known as Mappy! With these coordinates you can get the exact map position
                in-game or on a pixel map for various different content such as NPCs, Enemies and Gathering nodes.')
            
            ->h6('About')
            ->text('Mappy is a very basic windows application that reads your **FFXIV Game Memory** to retrieve
                data about entities around you, for example if you are next to a Monster in the open world then
                the application will record its position, HP, MP, TP, Level, etc. All this data is stored
                into JSON files in the apps directory which you can open in any text editor to view. Since it
                is JSON you can use it in any shape or form you would like.')
            
            ->image('Showcase1', '/img-map/showcase_1.png')
    
            ->gap()
            
            ->h6('Download')
            ->text('You can find the full source code for mappy on Github: https://github.com/xivapi/xivapi-mappy')
            ->text('Mappy does not send any personal information to XIVAPI, everything you see in the JSON files
                inside the `/log` directory is what is sent to the server, and this is only if you have an authorised
                dev apps key!')
            ->text('**Latest release:** https://github.com/xivapi/xivapi-mappy/releases')
            ->list([
                'Requirements: **PC, DX11, 64bit**'
            ])
            ->image('Showcase2', '/img-map/showcase_2.png')
            
            ->gap()
            
            ->h6('Endpoints')
            ->route('/mapdata/[ContentName]/[ID]', true)
            ->usage("{endpoint}/mapdata/PlaceName/43")
            ->text('`[ContentName]`: *(These are not case-sensitive)*')
            ->list([
                'PlaceName',
                'Map',
                'Territory'
            ])
            ->text('Obtain map positions for all kinds of content via different access points. The most
                common way to get the name of a zone is via `PlaceName`, however this does not
                provide `Map` information (the filename and such). These endpoints are still being improved.')
            ->text('The response is all map data, it is not paginated as there is usually only a thousand or so
                positions, not that many and the model/structure is always the same.')
            
            ->gap()
            
            ->h4('**Download All Data**')
            ->text('It can be tedious trying to obtain data for each endpoint, so if you prefer you can
                download **all position** data in 1 big CSV file!')
            ->text('**Total Records:** '. number_format($totalMapPositions))
            ->table([
                'Map Data', 'Memory Data'
            ], [
                [ '[/downloads/map-data](/downloads/xivapi-map-data)', '[/downloads/memory-data](/downloads/xivapi-memory-data)' ]
            ])
            ->text('MemoryData is various other information found in memory, some of it may be faulty right now
                (eg all results are 0) as Patch updates do break things. However if it looks correct, it likely is.
                You can link MemoryData to MapPositions via the `hash` as well as other content via thei
                respective IDs.')
            
            ->gap()
            
            ->h6('XIVAPI Integration')
            ->text('The app has the ability to submit data to XIVAPI if you have an approved dev apps key, if
                you are interested in doing this then please contact **@vekien** on the XIVAPI discord and
                if we need more map positions populating then you will be invited to the #beta channel
                to join the collaboration.')
            ->text('As map data is very static (only changes when SE change something on an update) there
                is not much reason to go back to old positions to update. The only real exception is F.A.T.Es
                where the position can only be recorded when the F.A.T.E is active.')

            ->get();
    }
}
