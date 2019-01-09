const Settings =
{
    /**
     * Default Tooltip Settings
     */

    url: 'https://xivapi.com/Tooltips',
    img: 'https://dalamud.com',

    /**
     * Links to act upon
     */
    hrefs: [
        'dalamud.com',
        'de.dalamud.com',
        'fr.dalamud.com',
        'ja.dalamud.com',
        'cn.dalamud.com',
        'kr.dalamud.com'
    ],

    /**
     * The name of the attribute to hold the Tooltip Info
     */
    attr: 'data-xiv',
    attr2: 'data-xivhash',

    /**
     * The ID and ClassName of the tooltip
     * If this is changed, then the CSS needs changing
     */
    id: 'xtt',

    /**
     * Tooltip options
     */
    opt: {
        // add link icon?
        icon: true,
        // replace link text?
        name: true,
        // blank the link text?
        blank: false,
        // add rarity colour?
        rarity: true,
        // tooltip spacing from cursor
        modX: 15,
        modY: 15,
    },

    /**
     * Current position of mouse
     */
    pos: {
        x: 0,
        y: 0,
    },

    /**
     * Tooltip storage
     */
    storage: {},

    /**
     * Read local site settings
     */
    readLocalSiteSettings: () => {
        if (typeof xiv_tooltips === 'undefined') {
            return;
        }

        for(let option in xiv_tooltips) {
            Settings[option] = xiv_tooltips[option];
        }
    }
};

export default Settings;
