import Settings from './Settings';
import Events from './Events';
import Request from './Request';
import Links from './Links';
import DOM from './DOM';

export default class Tooltips
{
    static init()
    {
        // add tooltip div
        DOM.addTooltip();

        // Read the users local
        Settings.readLocalSiteSettings();

        // Get all links
        const urls = Links.get();

        // build request payload
        const payload = Request.build(urls);

        // submit request
        Request.send(payload, response => {
            DOM.setVisuals(response);
            this.detectMouseMovement();
            this.detectMouseHover();
        });
    }

    static refresh()
    {
        const urls = Links.get();

        // build request payload
        const payload = Request.build(urls);

        // submit request
        Request.send(payload, response => {
            DOM.setVisuals(response);
            this.detectMouseHover();
        });
    }

    static detectMouseMovement()
    {
        document.body.addEventListener('mousemove', event => {
            // Get x/y position and page positions
            let x = event.pageX + Settings.opt.modX,
                y = event.pageY + Settings.opt.modY,
                width = document.getElementById(Settings.id).offsetWidth,
                height = document.getElementById(Settings.id).offsetHeight,
                yOffset = y + height,
                xOffset = x + width,
                scrollY = window.scrollY || window.scrollTop || document.getElementsByTagName("html")[0].scrollTop,
                scrollX = window.scrollX || window.scrollLeft || document.getElementsByTagName("html")[0].scrollLeft,
                yLimit = document.documentElement.clientHeight + scrollY,
                xLimit = document.documentElement.clientWidth + scrollX;

            // Positions based on window boundaries
            if (xOffset > xLimit) {
                console.log('x');
                x = event.pageX - (width + Settings.opt.modX);
            }

            if (yLimit < yOffset) {
                y = event.pageY - (height + Settings.opt.modY);
            }

            Settings.pos.x = x;
            Settings.pos.y = y;
        });
    }

    static detectMouseHover()
    {
        document.querySelectorAll(`[${Settings.attr}]`).forEach(ele => {
            const eventId = ele.getAttribute(Settings.attr2);

            // ignore already subscribed links
            if (Events.hasEventListeners(eventId)) {
                return;
            }

            Events.eventListeners.push(eventId);

            ele.addEventListener('mouseenter', event => {
                DOM.showTooltip(
                    Settings.storage[ ele.getAttribute(Settings.attr) ]
                );
            });

            ele.addEventListener('mouseleave', event => {
                DOM.hideTooltip();
            });

            ele.addEventListener('mousemove', event => {
                DOM.moveTooltip()
            });
        });
    }
}
