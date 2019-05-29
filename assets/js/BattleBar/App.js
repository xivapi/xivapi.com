import WebSockets from './WebSockets';
import UserInterface from './UserInterface';
import BattleRooms from "./BattleRooms";

WebSockets.connect(() => {
    // request latest rooms
    BattleRooms.requestList();
    UserInterface.watch();
});

