import BattleRooms from './BattleRooms';
import UserInterface from './UserInterface';

/**
 * This class handles any incoming actions and decides what to do, it's
 * effectively a websocket router
 *
 * todo - this file is going to get quite lengthy, each case logic should be its own class.
 */
class CommandHandler
{
    handle(response)
    {
        switch(response.ACTION) {
            default:
                console.log("Unknown action: " + response.ACTION);
                break;

            case 'REGISTER_APP_CLIENT':
                UserInterface.showAppStatusActivated();
                break;

            case 'APP_DISCONNECTED':
                UserInterface.showAppStatusDeactivated();
                break;

            case 'LIST_ROOMS':
                BattleRooms.renderList(response.DATA);
                break;

            case 'LOAD_ROOM':
                BattleRooms.load(response.DATA);
                break;

            case 'JOIN_ROOM':
                BattleRooms.join(response.DATA);
                break;

            case 'GAME_PLAYER_NAME':
                UserInterface.showPlayerName(response.DATA);
                break;

            case 'GAME_PLAYER_DATA':
                UserInterface.showPlayerData(response.DATA);
                break;

            case 'GAME_MOB_DATA':
                BattleRooms.mobdata(response.DATA);
                break;

            case 'GAME_MOB_REMOVE_SPAWNS':
                BattleRooms.removeSpawns(response.DATA);
                break;

            case 'GAME_MOB_DEAD':
                BattleRooms.removeSpawns()

        }
    }
}

export default new CommandHandler;
