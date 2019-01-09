import Settings from './Settings';

export default class Links
{
    /**
     * Get all Links
     */
    static get()
    {
        let links = [];

        for(let i = 0; i < document.links.length; i++) {
            const href = document.links[i].href;
            const ele = document.links[i];

            // create a parser
            const url = document.createElement('a');
            url.href = href;

            // ignore non valid domains
            if (Settings.hrefs.indexOf(url.hostname) === -1) {
                continue;
            }

            // ignore already processed links
            if (ele.getAttribute(Settings.attr2) !== null && ele.getAttribute(Settings.attr2).length > 0) {
                continue;
            }

            // add an unique id
            ele.setAttribute(Settings.attr2, Math.floor((Math.random() * 9999999999) + 1));

            // add data attribute
            ele.setAttribute(Settings.attr, url.pathname);

            // store record
            if (links.indexOf(url.pathname) === -1) {
                links.push(url.pathname);
            }
        }

        return links;
    }
}
