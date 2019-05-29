import BattleRooms from './BattleRooms';

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

            case 'LIST_ROOMS':
                BattleRooms.renderList(response.DATA);
                break;

            case 'LOAD_ROOM':
                BattleRooms.load(response.DATA);
                break;

            case 'JOIN_ROOM':
                BattleRooms.join(response.DATA);
                break;






            // -- test logic --

            /*
            case 'PLAYER_NAME':
                $('#character_name').html(data);
                break;

            case 'PLAYER_DATA':
                data = data.split(',');
                const player = {
                    id: data[0],
                    hp: data[1],
                    hpMax: data[2],
                    mp: data[3],
                    mpMax: data[4],
                    level: data[5],
                    classjob: data[6]
                };

                $('#player_hp').text(`${player.hp}/${player.hpMax}`);
                $('#player_mp').text(`${player.mp}/${player.mpMax}`);
                break;

            case 'TARGET_DATA':
                data = data.split(',');
                const target = {
                    id: data[0],
                    hp: data[1],
                    hpMax: data[2],
                    mp: data[3],
                    mpMax: data[4],
                    level: data[5],
                    name: data[6],
                    bNpcNameId: data[7],
                    bNpcBaseId: data[8],
                    memoryId1: data[9]
                };

                // console.log(target);

                const hpPercent = (target.hp / target.hpMax) * 100;
                $('#target_hp_bar').css('width', `${hpPercent}%`);

                $('#target_name').html(`[bNpcName ${target.bNpcNameId}] [bNpcBase ${target.bNpcBaseId}] ${target.name}`);
                $('#target_level').html(target.level);
                $('#target_hp').html(`${target.hp}/${target.hpMax}`);
                break;
            */
        }
    }
}

export default new CommandHandler;
