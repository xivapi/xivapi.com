import Settings from './Settings';

export default class Request
{
    /**
     * Build tooltips payload from an array of link data
     */
    static build(links)
    {
        let payload = {};

        links.forEach(url => {
            url = url.split('/').filter(Boolean);
            let [cat, id] = url;

            if (typeof payload[cat] === 'undefined') {
                payload[cat] = [];
            }

            payload[cat].push(id);
        });

        return payload;
    }

    /**
     * Send a payload to the tooltips server
     */
    static send(payload, callback)
    {
        let xhr = new XMLHttpRequest();
        xhr.open("POST", Settings.url, true);
        xhr.onreadystatechange = () => {
            if (xhr.readyState === 4 && xhr.status === 200) {
                callback(
                    JSON.parse(xhr.responseText)
                );
            } else if (xhr.readyState === 4) {
                console.error('Could not fetch tooltips', xhr.status);
            }
        };
        xhr.send(
            JSON.stringify(payload)
        );
    }
}
