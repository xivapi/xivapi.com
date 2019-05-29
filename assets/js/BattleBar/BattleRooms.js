import BattleBar from './WebSockets';

class BattleRooms
{
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

    create(name, enemies)
    {
        BattleBar.webSocketSendMessage('CREATE_ROOM', {
            name: name,
            enemies: enemies.split(',')
        });
    }

    join(roomId)
    {
        BattleBar.webSocketSendMessage('JOIN_ROOM', roomId)
    }

    load(room)
    {
        const $room = $('.room_view');

        // todo - this should be in its own class
        $('.room_create, .room_view').removeClass('open');
        $room.addClass('open');

        // load room info
        console.log(room);

        $room.html(`
            <h2>(${room.number}) ${room.name}</h2>
            <p>
                Enemies: ${room.enemies.join(', ')}
            </p>
        `);
    }
}

export default new BattleRooms;
