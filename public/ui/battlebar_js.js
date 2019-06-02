var Dalamud =
/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "/ui/";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/js/BattleBar/App.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/js/BattleBar/App.js":
/*!************************************!*\
  !*** ./assets/js/BattleBar/App.js ***!
  \************************************/
/*! no exports provided */
/*! all exports used */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__WebSockets__ = __webpack_require__(/*! ./WebSockets */ "./assets/js/BattleBar/WebSockets.js");
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__UserInterface__ = __webpack_require__(/*! ./UserInterface */ "./assets/js/BattleBar/UserInterface.js");
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_2__BattleRooms__ = __webpack_require__(/*! ./BattleRooms */ "./assets/js/BattleBar/BattleRooms.js");




__WEBPACK_IMPORTED_MODULE_0__WebSockets__["a" /* default */].connect(function () {
    // request latest rooms
    __WEBPACK_IMPORTED_MODULE_2__BattleRooms__["a" /* default */].requestList();
    __WEBPACK_IMPORTED_MODULE_1__UserInterface__["a" /* default */].watch();
});

/***/ }),

/***/ "./assets/js/BattleBar/BattleRooms.js":
/*!********************************************!*\
  !*** ./assets/js/BattleBar/BattleRooms.js ***!
  \********************************************/
/*! exports provided: default */
/*! exports used: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__WebSockets__ = __webpack_require__(/*! ./WebSockets */ "./assets/js/BattleBar/WebSockets.js");
var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }



var BattleRooms = function () {
    function BattleRooms() {
        _classCallCheck(this, BattleRooms);

        this.room = null;
        this.renderedEnemyIds = [];
    }

    _createClass(BattleRooms, [{
        key: 'renderList',
        value: function renderList(rooms) {
            // if we've got a list of rooms
            if (rooms && Object.keys(rooms).length > 0) {
                $('.battle_rooms').html('');

                Object.keys(rooms).forEach(function (roomId) {
                    var room = rooms[roomId];

                    $('.battle_rooms').append('\n                    <button type="button" id="' + room.id + '" class="list-group-item list-group-item-action">' + room.name + '</li>\n                ');
                });

                return;
            }

            $('.battle_rooms').append('\n            <li class="list-group-item">No battle rooms</li>\n        ');
        }
    }, {
        key: 'requestList',
        value: function requestList() {
            __WEBPACK_IMPORTED_MODULE_0__WebSockets__["a" /* default */].webSocketSendMessage('LIST_ROOMS');
        }
    }, {
        key: 'create',
        value: function create(name, monsters) {
            __WEBPACK_IMPORTED_MODULE_0__WebSockets__["a" /* default */].webSocketSendMessage('CREATE_ROOM', {
                name: name,
                monsters: monsters.split(',')
            });
        }
    }, {
        key: 'join',
        value: function join(roomId) {
            __WEBPACK_IMPORTED_MODULE_0__WebSockets__["a" /* default */].webSocketSendMessage('JOIN_ROOM', roomId);
        }

        /**
         * Load up a room, this handles all HTML for it.
         */

    }, {
        key: 'load',
        value: function load(room) {
            this.room = room;

            var $room = $('.room_view');

            // todo - this should be in its own class
            $('.room_create, .room_view').removeClass('open');
            $room.addClass('open');

            // load room info
            console.log(room);

            $room.html('\n            <h2>(' + room.number + ') ' + room.name + '</h2>\n            <hr>\n            <div class="monster_battles"></div>\n        ');
        }
    }, {
        key: 'mobdata',
        value: function mobdata(_mobdata) {
            var $ui = $('.monster_battles');

            for (var monsterId in _mobdata.monstersData) {
                var monster = _mobdata.monstersData[monsterId];

                // if the entry hasn't been rendered yet, do it!
                if (this.renderedEnemyIds.indexOf(monster.spawn_id) === -1) {
                    this.renderedEnemyIds.push(monster.spawn_id);
                    this.renderMonsterBlock(monster);
                }

                // update info
                var $mobrow = $ui.find('#' + monster.spawn_id);
                var healthPercent = monster.hp / monster.hp_max * 100;

                $mobrow.find('.mob_bar').css("width", healthPercent + '%');
                $mobrow.find('.mob_hp').text(monster.hp);
            }

            console.log(_mobdata);
        }
    }, {
        key: 'renderMonsterBlock',
        value: function renderMonsterBlock(monster) {
            var $ui = $('.monster_battles');

            $ui.append('\n        <div id="' + monster.spawn_id + '">\n            <p>Name: ' + monster.level + ' ' + monster.name + ' - <span class="mob_hp">' + monster.hp + '</span>/' + monster.hp_max + '</p>        \n            <div class="progress">\n                <div class="progress-bar bg-danger mob_bar" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>\n            </div>\n        </div>\n        ');
        }
    }, {
        key: 'removeSpawns',
        value: function removeSpawns(spawns) {
            console.log(spawns);

            if (typeof spawns === 'undefined' || spawns.length === 0) {
                return;
            }

            var $ui = $('.monster_battles');

            for (var i in spawns) {
                var spawn_id = spawns[i];
                $ui.find('#' + spawn_id).remove();
            }
        }
    }]);

    return BattleRooms;
}();

/* harmony default export */ __webpack_exports__["a"] = (new BattleRooms());

/***/ }),

/***/ "./assets/js/BattleBar/CommandHandler.js":
/*!***********************************************!*\
  !*** ./assets/js/BattleBar/CommandHandler.js ***!
  \***********************************************/
/*! exports provided: default */
/*! exports used: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__BattleRooms__ = __webpack_require__(/*! ./BattleRooms */ "./assets/js/BattleBar/BattleRooms.js");
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__UserInterface__ = __webpack_require__(/*! ./UserInterface */ "./assets/js/BattleBar/UserInterface.js");
var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }




/**
 * This class handles any incoming actions and decides what to do, it's
 * effectively a websocket router
 *
 * todo - this file is going to get quite lengthy, each case logic should be its own class.
 */

var CommandHandler = function () {
    function CommandHandler() {
        _classCallCheck(this, CommandHandler);
    }

    _createClass(CommandHandler, [{
        key: 'handle',
        value: function handle(response) {
            switch (response.ACTION) {
                default:
                    console.log("Unknown action: " + response.ACTION);
                    break;

                case 'REGISTER_APP_CLIENT':
                    __WEBPACK_IMPORTED_MODULE_1__UserInterface__["a" /* default */].showAppStatusActivated();
                    break;

                case 'APP_DISCONNECTED':
                    __WEBPACK_IMPORTED_MODULE_1__UserInterface__["a" /* default */].showAppStatusDeactivated();
                    break;

                case 'LIST_ROOMS':
                    __WEBPACK_IMPORTED_MODULE_0__BattleRooms__["a" /* default */].renderList(response.DATA);
                    break;

                case 'LOAD_ROOM':
                    __WEBPACK_IMPORTED_MODULE_0__BattleRooms__["a" /* default */].load(response.DATA);
                    break;

                case 'JOIN_ROOM':
                    __WEBPACK_IMPORTED_MODULE_0__BattleRooms__["a" /* default */].join(response.DATA);
                    break;

                case 'GAME_PLAYER_NAME':
                    __WEBPACK_IMPORTED_MODULE_1__UserInterface__["a" /* default */].showPlayerName(response.DATA);
                    break;

                case 'GAME_PLAYER_DATA':
                    __WEBPACK_IMPORTED_MODULE_1__UserInterface__["a" /* default */].showPlayerData(response.DATA);
                    break;

                case 'GAME_MOB_DATA':
                    __WEBPACK_IMPORTED_MODULE_0__BattleRooms__["a" /* default */].mobdata(response.DATA);
                    break;

                case 'GAME_MOB_REMOVE_SPAWNS':
                    __WEBPACK_IMPORTED_MODULE_0__BattleRooms__["a" /* default */].removeSpawns(response.DATA);
                    break;

                case 'GAME_MOB_DEAD':
                    __WEBPACK_IMPORTED_MODULE_0__BattleRooms__["a" /* default */].removeSpawns();

            }
        }
    }]);

    return CommandHandler;
}();

/* harmony default export */ __webpack_exports__["a"] = (new CommandHandler());

/***/ }),

/***/ "./assets/js/BattleBar/UserInterface.js":
/*!**********************************************!*\
  !*** ./assets/js/BattleBar/UserInterface.js ***!
  \**********************************************/
/*! exports provided: default */
/*! exports used: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__BattleRooms__ = __webpack_require__(/*! ./BattleRooms */ "./assets/js/BattleBar/BattleRooms.js");
var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }



var UserInterface = function () {
    function UserInterface() {
        _classCallCheck(this, UserInterface);

        this.createRoomMonsterIdList = [];
    }

    _createClass(UserInterface, [{
        key: 'watch',
        value: function watch() {
            this.watchMenuForRoomCreate();
            this.watchForMonsterSearch();
            this.watchForMonsterSelection();
            this.watchForCreateRoomFormSubmit();
            this.watchForRoomSelection();
        }
    }, {
        key: 'watchMenuForRoomCreate',
        value: function watchMenuForRoomCreate() {
            $('.btn_create_room').on('click', function (event) {
                $('.room_create, .room_view').removeClass('open');
                $('.room_create').addClass('open');
            });
        }
    }, {
        key: 'watchForMonsterSearch',
        value: function watchForMonsterSearch() {
            $('.search_monster').on('click', function (event) {
                var monsterName = $('.monster_name').val().trim();

                $('.monster_list_search_results').html('');

                $.ajax({
                    url: 'https://xivapi.com/search',
                    data: {
                        indexes: 'bnpcname',
                        string: monsterName
                    },
                    success: function success(response) {
                        response.Results.forEach(function (monster) {
                            $('.monster_list_search_results').append('\n                            <button type="button" class="btn btn-outline-secondary monster_selected" id="' + monster.ID + '">' + monster.ID + ' - ' + monster.Name + '</button>\n                        ');
                        });
                    },
                    error: console.log
                });
            });
        }
    }, {
        key: 'watchForMonsterSelection',
        value: function watchForMonsterSelection() {
            var _this = this;

            $('.monster_list_search_results').on('click', '.btn', function (event) {
                var id = $(event.target).attr('id');

                _this.createRoomMonsterIdList.push(id);

                $('.monster_list').val(_this.createRoomMonsterIdList.join(','));
            });
        }
    }, {
        key: 'watchForCreateRoomFormSubmit',
        value: function watchForCreateRoomFormSubmit() {
            var _this2 = this;

            $('.create_battle_room').on('click', function (event) {
                var name = $('.room_name').val().trim();
                var monsters = $('.monster_list').val().trim();

                $('.room_name').val('');
                $('.monster_list').val('');
                $('.monster_list_search_results').html('');
                _this2.createRoomMonsterIdList = [];

                __WEBPACK_IMPORTED_MODULE_0__BattleRooms__["a" /* default */].create(name, monsters);
            });
        }
    }, {
        key: 'watchForRoomSelection',
        value: function watchForRoomSelection() {
            $('.battle_rooms').on('click', 'button', function (event) {
                var id = $(event.target).attr('id');

                console.log(id);

                __WEBPACK_IMPORTED_MODULE_0__BattleRooms__["a" /* default */].join(id);
            });
        }

        // ----------------------------------------------------------------------------

    }, {
        key: 'showAppStatusActivated',
        value: function showAppStatusActivated() {
            $('.app_status').html('<span class="badge badge-success">Monitoring App Detected!</span>');
        }
    }, {
        key: 'showAppStatusDeactivated',
        value: function showAppStatusDeactivated() {
            $('.app_player_name').html('-');
            $('.app_status').html('<span class="badge badge-danger">App not connected</span>');
        }
    }, {
        key: 'showPlayerName',
        value: function showPlayerName(data) {
            $('.app_player_name').html('<strong>' + data.player_name + '</strong>');
        }
    }, {
        key: 'showPlayerData',
        value: function showPlayerData(data) {
            // todo
        }
    }]);

    return UserInterface;
}();

/* harmony default export */ __webpack_exports__["a"] = (new UserInterface());

/***/ }),

/***/ "./assets/js/BattleBar/WebSockets.js":
/*!*******************************************!*\
  !*** ./assets/js/BattleBar/WebSockets.js ***!
  \*******************************************/
/*! exports provided: default */
/*! exports used: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__CommandHandler__ = __webpack_require__(/*! ./CommandHandler */ "./assets/js/BattleBar/CommandHandler.js");
var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }



var BattleBar = function () {
    function BattleBar() {
        _classCallCheck(this, BattleBar);

        this.websocket = null;
    }

    _createClass(BattleBar, [{
        key: 'connect',
        value: function connect(onConnect) {
            var _this = this;

            Swal.fire({
                title: 'Connecting',
                text: 'Please wait while we connect to the battle arena',
                allowOutsideClick: false,
                allowEscapeKey: false,
                onBeforeOpen: function onBeforeOpen() {
                    Swal.showLoading();
                }
            });

            console.log('Connecting to XIVAPI WebSocket');

            this.websocket = new WebSocket('wss://xivapi.local/socket');

            this.websocket.onopen = function (event) {
                setTimeout(function () {
                    Swal.close();
                }, 1000);

                _this.webSocketOnOpen(event);
                onConnect();
            };

            this.websocket.onmessage = function (event) {
                _this.webSocketOnMessage(event);
            };

            this.websocket.onclose = function (event) {
                Swal.fire({
                    title: 'Disconnected',
                    text: 'Lost connection to the battlegrounds',
                    type: 'error'
                });

                _this.webSocketOnDisconnect(event);
            };

            this.websocket.onerror = function (event) {
                _this.webSocketOnDisconnect(event);
            };
        }

        /**
         * Connected to the server
         */

    }, {
        key: 'webSocketOnOpen',
        value: function webSocketOnOpen() {
            // register API Key
            this.webSocketSendMessage('REGISTER_WEB_CLIENT');
        }

        /**
         * Receive a message FROM the server
         */

    }, {
        key: 'webSocketOnMessage',
        value: function webSocketOnMessage(event) {
            var response = JSON.parse(event.data);

            // do nothing if the lengths are bad
            if (response.ACTION.length === 0) {
                return;
            }

            // handle the command
            __WEBPACK_IMPORTED_MODULE_0__CommandHandler__["a" /* default */].handle(response);
        }

        /**
         * Whenever the socket errors or closes.
         */

    }, {
        key: 'webSocketOnDisconnect',
        value: function webSocketOnDisconnect() {
            console.log('disconnected');
        }

        /**
         * Send a message TO the server
         */

    }, {
        key: 'webSocketSendMessage',
        value: function webSocketSendMessage(action, data) {
            var request = {
                APIKEY: APIKEY,
                SOURCE: 'WEB',
                ACTION: action,
                DATA: data
            };

            this.websocket.send(JSON.stringify(request));
        }
    }]);

    return BattleBar;
}();

/* harmony default export */ __webpack_exports__["a"] = (new BattleBar());

/***/ })

/******/ })["default"];