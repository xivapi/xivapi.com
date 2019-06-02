import BattleRooms from './BattleRooms';

class UserInterface
{
    constructor()
    {
        this.createRoomMonsterIdList = [];
    }

    watch()
    {
        this.watchMenuForRoomCreate();
        this.watchForMonsterSearch();
        this.watchForMonsterSelection();
        this.watchForCreateRoomFormSubmit();
        this.watchForRoomSelection();
    }

    watchMenuForRoomCreate()
    {
        $('.btn_create_room').on('click', event => {
            $('.room_create, .room_view').removeClass('open');
            $('.room_create').addClass('open');
        });
    }

    watchForMonsterSearch()
    {
        $('.search_monster').on('click', event => {
            const monsterName = $('.monster_name').val().trim();

            $('.monster_list_search_results').html('');

            $.ajax({
                url: 'https://xivapi.com/search',
                data: {
                    indexes: 'bnpcname',
                    string: monsterName
                },
                success: response => {
                    response.Results.forEach(monster => {
                        $('.monster_list_search_results').append(`
                            <button type="button" class="btn btn-outline-secondary monster_selected" id="${monster.ID}">${monster.ID} - ${monster.Name}</button>
                        `)
                    });
                },
                error: console.log
            });
        })
    }

    watchForMonsterSelection()
    {
        $('.monster_list_search_results').on('click', '.btn', event => {
            const id = $(event.target).attr('id');

            this.createRoomMonsterIdList.push(id);

            $('.monster_list').val(this.createRoomMonsterIdList.join(','));
        });
    }

    watchForCreateRoomFormSubmit()
    {
        $('.create_battle_room').on('click', event => {
            const name = $('.room_name').val().trim();
            const monsters = $('.monster_list').val().trim();

            $('.room_name').val('');
            $('.monster_list').val('');
            $('.monster_list_search_results').html('');
            this.createRoomMonsterIdList = [];

            BattleRooms.create(name, monsters);
        });
    }

    watchForRoomSelection()
    {
        $('.battle_rooms').on('click', 'button', event => {
            const id = $(event.target).attr('id');

            console.log(id);

            BattleRooms.join(id);
        })
    }

    // ----------------------------------------------------------------------------

    showAppStatusActivated()
    {
        $('.app_status').html('<span class="badge badge-success">Monitoring App Detected!</span>');
    }

    showAppStatusDeactivated()
    {
        $('.app_player_name').html('-');
        $('.app_status').html('<span class="badge badge-danger">App not connected</span>');
    }

    showPlayerName(data)
    {
        $('.app_player_name').html(`<strong>${data.player_name}</strong>`);
    }

    showPlayerData(data)
    {
        // todo
    }
}

export default new UserInterface;
