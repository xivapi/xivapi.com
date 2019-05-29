import BattleRooms from './BattleRooms';

class UserInterface
{
    watch()
    {
        this.watchMenuForRoomCreate();
        this.watchForEnemySearch();
        this.watchForEnemySelection();
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

    watchForEnemySearch()
    {
        $('.enemy_list_search_results').html('');
        $('.search_enemy').on('click', event => {
            const enemyName = $('.enemy_name').val().trim();

            $.ajax({
                url: 'https://xivapi.com/search',
                data: {
                    indexes: 'bnpcname',
                    string: enemyName
                },
                success: response => {
                    response.Results.forEach(enemy => {
                        $('.enemy_list_search_results').append(`
                            <button type="button" class="btn btn-outline-secondary enemy_selected" id="${enemy.ID}">${enemy.ID} - ${enemy.Name}</button>
                        `)
                    });
                },
                error: console.log
            });
        })
    }

    watchForEnemySelection()
    {
        let enemyList = [];
        $('.enemy_list_search_results').on('click', '.btn', event => {
            const id = $(event.target).attr('id');

            enemyList.push(id);

            $('.enemy_list').val(enemyList.join(','));
        });
    }

    watchForCreateRoomFormSubmit()
    {
        $('.create_battle_room').on('click', event => {
            const name = $('.room_name').val().trim();
            const enemies = $('.enemy_list').val().trim();

            BattleRooms.create(name, enemies);
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
}

export default new UserInterface;
