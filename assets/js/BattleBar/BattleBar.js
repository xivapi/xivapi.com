import CommandHandler from './CommandHandler';

class BattleBar
{
    constructor()
    {
        this.websocket = null;
    }

    start()
    {
        // todo - write start logic, it should check for a local var so it only
        // todo - starts on pages it needs to.

        BattleBar.websocket           = new WebSocket('wss://xivapi.local/socket');
        BattleBar.websocket.onopen    = this.webSocketOnOpen;
        BattleBar.websocket.onmessage = this.webSocketOnMessage;
        BattleBar.websocket.onclose   = this.webSocketOnDisconnect;
        BattleBar.websocket.onerror   = this.webSocketOnDisconnect;
    }

    /**
     * Connected to the server
     */
    static webSocketOnOpen(event)
    {
        console.log("Connection established!");
    }

    /**
     * Receive a message FROM the server
     */
    static webSocketOnMessage(event)
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
    static webSocketOnDisconnect(event)
    {
        console.log('disconnected');
    }

    /**
     * Send a message TO the server
     */
    static webSocketSendMessage(message)
    {
        this.websocket.send(message);
    }
}

export default new BattleBar;
