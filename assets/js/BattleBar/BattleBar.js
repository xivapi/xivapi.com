import CommandHandler from './CommandHandler';
import Logger from './Logger';

class BattleBar
{
    constructor()
    {
        this.websocket = null;
    }

    start()
    {
        Logger.write('Connecting to XIVAPI WebSocket');

        this.websocket           = new WebSocket('wss://xivapi.local/socket');
        this.websocket.onopen    = this.webSocketOnOpen;
        this.websocket.onmessage = this.webSocketOnMessage;
        this.websocket.onclose   = this.webSocketOnDisconnect;
        this.websocket.onerror   = this.webSocketOnDisconnect;
    }

    /**
     * Connected to the server
     */
    webSocketOnOpen(event)
    {
        console.log('connection established');
        Logger.write("Connection established!");
    }

    /**
     * Receive a message FROM the server
     */
    webSocketOnMessage(event)
    {
        let command = event.data.split('::');
        let action  = command[0];
        let data    = command[1];

        // do nothing if the lengths are bad
        if (action.length === 0 || data.length === 0) {
            return;
        }

        // handle the command
        CommandHandler.handle(action, data);
    }

    /**
     * Whenever the socket errors or closes.
     */
    webSocketOnDisconnect(event)
    {
        console.log('disconnected');
    }

    /**
     * Send a message TO the server
     */
    webSocketSendMessage(message)
    {
        this.websocket.send(message);
    }
}

export default new BattleBar;
