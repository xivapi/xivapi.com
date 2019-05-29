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
        value: function create(name, enemies) {
            __WEBPACK_IMPORTED_MODULE_0__WebSockets__["a" /* default */].webSocketSendMessage('CREATE_ROOM', {
                name: name,
                enemies: enemies.split(',')
            });
        }
    }, {
        key: 'join',
        value: function join(roomId) {
            __WEBPACK_IMPORTED_MODULE_0__WebSockets__["a" /* default */].webSocketSendMessage('JOIN_ROOM', roomId);
        }
    }, {
        key: 'load',
        value: function load(room) {
            var $room = $('.room_view');

            // todo - this should be in its own class
            $('.room_create, .room_view').removeClass('open');
            $room.addClass('open');

            // load room info
            console.log(room);

            $room.html('\n            <h2>(' + room.number + ') ' + room.name + '</h2>\n            <p>\n                Enemies: ' + room.enemies.join(', ') + '\n            </p>\n        ');
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

                case 'LIST_ROOMS':
                    __WEBPACK_IMPORTED_MODULE_0__BattleRooms__["a" /* default */].renderList(response.DATA);
                    break;

                case 'LOAD_ROOM':
                    __WEBPACK_IMPORTED_MODULE_0__BattleRooms__["a" /* default */].load(response.DATA);
                    break;

                case 'JOIN_ROOM':
                    __WEBPACK_IMPORTED_MODULE_0__BattleRooms__["a" /* default */].join(response.DATA);
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
    }

    _createClass(UserInterface, [{
        key: 'watch',
        value: function watch() {
            this.watchMenuForRoomCreate();
            this.watchForEnemySearch();
            this.watchForEnemySelection();
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
        key: 'watchForEnemySearch',
        value: function watchForEnemySearch() {
            $('.enemy_list_search_results').html('');
            $('.search_enemy').on('click', function (event) {
                var enemyName = $('.enemy_name').val().trim();

                $.ajax({
                    url: 'https://xivapi.com/search',
                    data: {
                        indexes: 'bnpcname',
                        string: enemyName
                    },
                    success: function success(response) {
                        response.Results.forEach(function (enemy) {
                            $('.enemy_list_search_results').append('\n                            <button type="button" class="btn btn-outline-secondary enemy_selected" id="' + enemy.ID + '">' + enemy.ID + ' - ' + enemy.Name + '</button>\n                        ');
                        });
                    },
                    error: console.log
                });
            });
        }
    }, {
        key: 'watchForEnemySelection',
        value: function watchForEnemySelection() {
            var enemyList = [];
            $('.enemy_list_search_results').on('click', '.btn', function (event) {
                var id = $(event.target).attr('id');

                enemyList.push(id);

                $('.enemy_list').val(enemyList.join(','));
            });
        }
    }, {
        key: 'watchForCreateRoomFormSubmit',
        value: function watchForCreateRoomFormSubmit() {
            $('.create_battle_room').on('click', function (event) {
                var name = $('.room_name').val().trim();
                var enemies = $('.enemy_list').val().trim();

                __WEBPACK_IMPORTED_MODULE_0__BattleRooms__["a" /* default */].create(name, enemies);
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