import CommandHandler from './CommandHandler';

class BattleBar
{
    constructor()
    {
        this.websocket = null;
    }

    connect(onConnect)
    {
        Swal.fire({
            title: 'Connecting',
            text: 'Please wait while we connect to the battle arena',
            allowOutsideClick: false,
            allowEscapeKey: false,
            onBeforeOpen: () => {
                Swal.showLoading();
            }
        });

        console.log('Connecting to XIVAPI WebSocket');

        this.websocket = new WebSocket('wss://xivapi.local/socket');

        this.websocket.onopen = event => {
            setTimeout(() => {
                Swal.close();
            }, 1000);

            this.webSocketOnOpen(event);
            onConnect();
        };

        this.websocket.onmessage = event => {
            this.webSocketOnMessage(event);
        };

        this.websocket.onclose = event => {
            this.webSocketOnDisconnect(event);
        };

        this.websocket.onerror = event => {
            this.webSocketOnDisconnect(event);
        };
    }

    /**
     * Connected to the server
     */
    webSocketOnOpen()
    {
        // register API Key
        this.webSocketSendMessage('REGISTER_WEB_CLIENT');
    }

    /**
     * Receive a message FROM the server
     */
    webSocketOnMessage(event)
    {
        let response = JSON.parse(event.data);

        // do nothing if the lengths are bad
        if (response.ACTION.length === 0) {
            return;
        }

        // handle the command
        CommandHandler.handle(response);
    }

    /**
     * Whenever the socket errors or closes.
     */
    webSocketOnDisconnect()
    {
        console.log('disconnected');
    }

    /**
     * Send a message TO the server
     */
    webSocketSendMessage(action, data)
    {
        const request = {
            APIKEY: APIKEY,
            SOURCE: 'WEB',
            ACTION: action,
            DATA: data
        };

        this.websocket.send(
            JSON.stringify(request)
        );
    }
}

export default new BattleBar;
