import BattleBar from './WebSockets';

class BattleRooms
{
    constructor()
    {
        this.room = null;
        this.renderedEnemyIds = [];
    }

    renderList(rooms)
    {
        // if we've got a list of rooms
        if (rooms && Object.keys(rooms).length > 0) {
            $('.battle_rooms').html('');

            Object.keys(rooms).forEach(roomId => {
                const room = rooms[roomId];

                $('.battle_rooms').append(`
                    <button type="button" id="${room.id}" class="list-group-item list-group-item-action">${room.name}</li>
                `)
            });

            return
        }

        $('.battle_rooms').append(`
            <li class="list-group-item">No battle rooms</li>
        `);
    }

    requestList()
    {
        BattleBar.webSocketSendMessage('LIST_ROOMS');
    }

    create(name, monsters)
    {
        BattleBar.webSocketSendMessage('CREATE_ROOM', {
            name: name,
            monsters: monsters.split(',')
        });
    }

    join(roomId)
    {
        BattleBar.webSocketSendMessage('JOIN_ROOM', roomId)
    }

    /**
     * Load up a room, this handles all HTML for it.
     */
    load(room)
    {
        this.room = room;

        const $room = $('.room_view');

        // todo - this should be in its own class
        $('.room_create, .room_view').removeClass('open');
        $room.addClass('open');

        // load room info
        console.log(room);

        $room.html(`
            <h2>(${room.number}) ${room.name}</h2>
            <hr>
            <div class="monster_battles"></div>
        `);
    }

    mobdata(mobdata)
    {
        const $ui = $('.monster_battles');

        for(let monsterId in mobdata.monstersData) {
            let monster = mobdata.monstersData[monsterId];

            // if the entry hasn't been rendered yet, do it!
            if (this.renderedEnemyIds.indexOf(monster.spawn_id) === -1) {
                this.renderedEnemyIds.push(monster.spawn_id);
                this.renderMonsterBlock(monster);
            }

            // update info
            const $mobrow = $ui.find(`#${monster.spawn_id}`);
            const healthPercent = (monster.hp / monster.hp_max) * 100;

            $mobrow.find('.mob_bar').css("width", `${healthPercent}%`);
            $mobrow.find('.mob_hp').text(monster.hp);
        }

        console.log(mobdata);
    }

    renderMonsterBlock(monster)
    {
        const $ui = $('.monster_battles');

        $ui.append(`
        <div id="${monster.spawn_id}">
            <p>Name: ${monster.level} ${monster.name} - <span class="mob_hp">${monster.hp}</span>/${monster.hp_max}</p>        
            <div class="progress">
                <div class="progress-bar bg-danger mob_bar" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
        `);
    }

    removeSpawns(spawns)
    {
        console.log(spawns);

        if (typeof spawns === 'undefined' || spawns.length === 0) {
            return;
        }

        const $ui = $('.monster_battles');

        for(let i in spawns) {
            let spawn_id = spawns[i];
            $ui.find(`#${spawn_id}`).remove();
        }
    }
}

export default new BattleRooms;
