/**
 * This class handles any incoming actions and decides what to do, it's
 * effectively a websocket router
 */
class CommandHandler
{
    static handle(action, data)
    {
        switch(action) {
            default:
                console.log("Unknown action: " + action);
                break;
        }
    }
}

export default new BattleBar;
