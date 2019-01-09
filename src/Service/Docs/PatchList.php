<?php

namespace App\Service\Docs;

class PatchList extends DocBuilder implements DocInterface
{
    public function build()
    {
        return $this
            ->h1('Patch List')
            
            // Lodestone
            ->route('/patchlist')
            ->usage("{endpoint}/patchlist")
            ->text('The API keeps a track record of when a piece of content is updated, this is based on which
                patch version the ID appears; ignoring all "dummy" rows. This patch list is handled manually
                and has been curated over many years. Work is in progress to ensure an accurate patch version
                for all current game files.')
            
            ->text('You can keep a hash of this patch list to know if any new data has been added to the API.')
            ->note('This patch list will not be accurate for Korean or Chinese game content.')
            ->gap()
            
            ->h6('Response')
            ->table(
                [ 'Field', 'Details' ],
                [
                    [ '`Banner`', 'The banner from lodestone, this will only be on main patches and but sub-patches.', ],
                    [ '`ExVersion`', 'The ID to the file: http://xivapi.com/ExVersion'],
                    [ '`ID`', 'This is an internal ID record.', ],
                    [ '`Name`', 'The name of the patch in several languages, this has not yet been translated.' ],
                    [ '`ReleaseDate`', 'Unix timestamp of the patch release date.' ],
                    [ '`Version`', 'A string that represents the version, eg: `3.55b`' ]
                ]
            )
            ->gap()

            ->h6('GamePatch')
            ->text('A field on content that states which patch it was first added.')
            ->text('Example: **Cronus Lux Replica** http://xivapi.com/Item/21004?pretty=1')
            ->json('{
                "GamePatch": {
                    "Banner": "https:\/\/img.finalfantasyxiv.com\/lds\/h\/k\/i4m9KdxaQkwYVT_F6JOcytNdEs.png",
                    "ExVersion": 2,
                    "ID": 40,
                    "Name": "Patch 4.1: The Legend Returns",
                    "Name_cn": "Patch 4.1: The Legend Returns",
                    "Name_de": "Patch 4.1: The Legend Returns",
                    "Name_en": "Patch 4.1: The Legend Returns",
                    "Name_fr": "Patch 4.1: The Legend Returns",
                    "Name_ja": "Patch 4.1: The Legend Returns",
                    "Name_kr": "Patch 4.1: The Legend Returns",
                    "ReleaseDate": "1507622400",
                    "Version": "4.0"
                }
            }')

            ->get();
    }
}
