import Settings from './Settings';
import Events from './Events';
import Views from './Views';

export default class DOM
{
    static addTooltip()
    {
        const div = document.createElement("div");
        div.classList.add(Settings.id);
        div.id = Settings.id;
        document.body.appendChild(div);
    }

    static getTooltip()
    {
        return document.getElementById(Settings.id);
    }

    static showTooltip(html)
    {
        this.getTooltip().innerHTML = html;
        this.getTooltip().classList.add(`${Settings.id}on`)
    }

    static hideTooltip()
    {
        this.getTooltip().innerHTML = '';
        this.getTooltip().classList.remove(`${Settings.id}on`)
    }

    static moveTooltip()
    {
        this.getTooltip().style.top = `${Settings.pos.y}px`;
        this.getTooltip().style.left = `${Settings.pos.x}px`;
    }

    static setVisuals(response)
    {
        // loop through each returned content name
        for(let contentName in response) {
            const content = response[contentName];

            // loop through each returned content id
            for(let id in content) {
                const data = content[id];
                const key  = `/${contentName}/${id}`;

                // store tooltip
                Settings.storage[key] = Views[contentName](data).trim();

                // customisation
                const text  = Settings.opt.name ? data[1] : false;
                const image = Settings.opt.icon ? `<img src="${Settings.img}${data[2]}" height="16" width="16">` : false;

                // modify links
                document.querySelectorAll(`[${Settings.attr}="${key}"]`).forEach(ele => {
                    // ignore already converted links
                    const eventId = ele.getAttribute(Settings.attr2);
                    if (Events.hasConverted(eventId)) {
                        return;
                    }

                    Events.converted.push(eventId);

                    let string = '';
                    string += image || '';
                    string += text || (Settings.opt.blank ? '' : ele.innerHTML);

                    // set element text
                    ele.innerHTML = string || original;

                    // if to add the content rarity colour
                    if (Settings.opt.rarity) {
                        ele.classList.add(`xiv-r${data[3]}`);
                    }
                });
            }
        }
    }
}
