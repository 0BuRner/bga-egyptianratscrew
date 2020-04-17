/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Hearts implementation : © Gregory Isabelli <gisabelli@boardgamearena.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * egyptianratscrew.js
 *
 * Hearts user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */
////////////////////////////////////////////////////////////////////////////////

/*
    In this file, you are describing the logic of your user interface, in Javascript language.
*/

define([
        "dojo", "dojo/_base/declare",
        "ebg/core/gamegui",
        "ebg/counter",
        "ebg/stock"
    ],
    function (dojo, declare) {
        return declare("bgagame.egyptianratscrew", ebg.core.gamegui, {
            constructor: function () {
                console.log('egyptianratscrew constructor');
                // Here, you can init the global variables of your user interface
                this.tableStock = null;
                this.playerStocks = [];
                this.playerHand = null;
                this.cardwidth = 72;
                this.cardheight = 96;

                // Array of current dojo connections
                this.connections = [];
            },

            /*
                setup:

                This method must set up the game user interface according to current game situation specified
                in parameter.

                The method is called each time the game interface is displayed to a player, ie:
                _ when the game starts
                _ when a player refresh the game page (F5)

                "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
            */
            setup: function (gamedatas) {
                console.log("starting game setup");

                var pile = dojo.query("#pile");
                this.connect(pile, "onclick", "onSlapPile");

                console.log("start creating card stocks");

                // Create cards stocks:
                this.createTableStock();
                this.initTableCards();

                for (let player_id in gamedatas.players) {
                    let nbr_cards = gamedatas.players[player_id].cards;
                    this.createPlayerStock(player_id);
                    this.initPlayerCards(player_id, nbr_cards);
                }

                // Setup game notifications to handle (see "setupNotifications" method below)
                this.setupNotifications();

                this.ensureSpecificImageLoading(['../common/point.png']);
            },


            ///////////////////////////////////////////////////
            //// Game & client states

            // onEnteringState: this method is called each time we are entering into a new game state.
            //                  You can use this method to perform some user interface changes at this moment.
            //

            onEnteringState: function (stateName, args) {
                console.log('Entering state: ' + stateName);

                switch (stateName) {
                    case 'playerTurn':
                        this.addTooltip('myhand', _('Cards in my hand'), _('Play a card'));
                        break;
                    case 'endTurn':
                        this.addTooltip('myhand', _('Cards in my hand'), _('Select a card'));
                        break;
                    case 'dummmy':
                        break;
                }
            },

            // onLeavingState: this method is called each time we are leaving a game state.
            //                 You can use this method to perform some user interface changes at this moment.
            //
            onLeavingState: function (stateName) {
                console.log('Leaving state: ' + stateName);
            },

            // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
            //                        action status bar (ie: the HTML links in the status bar).
            //
            onUpdateActionButtons: function (stateName, args) {
                console.log('onUpdateActionButtons: ' + stateName + ' : ' + args);
            },

            ///////////////////////////////////////////////////
            //// Utility methods

            /*
                Here, you can defines some utility methods that you can use everywhere in your javascript
                script.
            */

            createTableStock: function() {
                this.tableStock = new ebg.stock();
                this.tableStock.image_items_per_row = 13;
                this.tableStock.setOverlap(10,5);
                this.tableStock.setSelectionMode(0);
                this.tableStock.centerItems = true;
                this.tableStock.create(this, $('pile'), this.cardwidth, this.cardheight);
                // Add back card type
                this.tableStock.addItemType(-1, 0, g_gamethemeurl + 'img/card_back.png');
                // Add visible card types
                for (var color = 1; color <= 4; color++) {
                    for (var value = 2; value <= 14; value++) {
                        // Build card type id
                        var card_type_id = this.getCardUniqueId(color, value);
                        this.tableStock.addItemType(card_type_id, card_type_id, g_gamethemeurl + 'img/cards.jpg', card_type_id);
                    }
                }
            },

            initTableCards: function() {
                var cardsOnTable = this.gamedatas.cardsontable;
                for (var i in cardsOnTable) {
                    var card = cardsOnTable[i];
                    this.tableStock.addToStockWithId(-1, card.id);
                }
            },

            createPlayerStock: function(player_id) {
                let target = new ebg.stock();
                target.image_items_per_row = 13;
                target.setOverlap(10,0);
                target.setSelectionMode(0);
                target.create(this, $('player_cards_' + player_id), this.cardwidth, this.cardheight);
                target.addItemType(-1, 0, g_gamethemeurl + 'img/card_back.png');

                this.playerStocks[player_id] = target;
            },

            initPlayerCards: function(player_id, nbr_cards) {
                for (let i = 0; i < nbr_cards; i++) {
                    this.playerStocks[player_id].addToStockWithId(-1, i);
                }
            },

            /**
             * Utility to connect and disconnect a single element to/from an event.
             */
            connect: function (element, event, handler) {
                if (element == null) return;
                this.connections.push({
                    element: element,
                    event: event,
                    handle: dojo.connect(element, event, this, handler)
                });
            },

            disconnect: function (element, event) {
                dojo.forEach(this.connections, function (connection) {
                    if (connection.element == element && connection.event == event)
                        dojo.disconnect(connection.handle);
                });
            },

            /**
             * Utility to connect an event to all elements of the same css class.
             */
            connectClass: function (className, event, handler) {
                var list = dojo.query("." + className);
                for (var i = 0; i < list.length; i++) {
                    var element = list[i];
                    this.connections.push({
                        element: element,
                        event: event,
                        handle: dojo.connect(element, event, this, handler)
                    });
                }
            },

            /**
             * Utility to remove all registered events.
             */
            disconnectAll: function () {
                dojo.forEach(this.connections, function (connection) {
                    dojo.disconnect(connection.handle);
                });
                this.connections = [];
            },

            // Get card unique identifier based on its color and value
            getCardUniqueId: function (color, value) {
                return (color - 1) * 13 + (value - 2);
            },

            // playCardOnTable: function (player_id, color, value, card_id) {
            //     // player_id => direction
            //     dojo.place(
            //         this.format_block('jstpl_cardontable', {
            //             x: this.cardwidth * (value - 2),
            //             y: this.cardheight * (color - 1),
            //             player_id: player_id
            //         }), 'playertablecard_' + player_id);
            //
            //     if (player_id != this.player_id) {
            //         // Some opponent played a card
            //         // Move card from player panel
            //         this.placeOnObject('cardontable_' + player_id, 'overall_player_board_' + player_id);
            //     } else {
            //         // You played a card. If it exists in your hand, move card from there and remove
            //         // corresponding item
            //
            //         if ($('myhand_item_' + card_id)) {
            //             this.placeOnObject('cardontable_' + player_id, 'myhand_item_' + card_id);
            //             this.playerHand.removeFromStockById(card_id);
            //         }
            //     }
            //
            //     // In any case: move it to its final destination
            //     this.slideToObject('cardontable_' + player_id, 'playertablecard_' + player_id).play();
            //
            // },


            ///////////////////////////////////////////////////
            //// Player's action

            /*

                Here, you are defining methods to handle player's action (ex: results of mouse click on
                game objects).

                Most of the time, these methods:
                _ check the action is possible at this game state.
                _ make a call to the game server

            */

            onSlapPile: function () {
                // TODO client-side animation before receiving server confirmation?
                console.log("Pile slapped");

                if (this.checkAction('slapPile', true)) {
                    this.ajaxcall("/egyptianratscrew/egyptianratscrew/slapPile.html", {}, this, function (result) {
                    }, function (is_error) {
                    });
                }
            },

            onPlayCard: function () {
                // TODO client-side animation before receiving server confirmation?
                console.log("Card played");

                if (this.checkAction('playCard', true)) {
                    this.ajaxcall("/egyptianratscrew/egyptianratscrew/playCard.html", {}, this, function (result) {
                    }, function (is_error) {
                    });
                }
            },

            // onPlayerHandSelectionChanged: function () {
            //     var items = this.playerHand.getSelectedItems();
            //
            //     if (items.length > 0) {
            //         if (this.checkAction('playCard', true)) {
            //             // Can play a card
            //
            //             var card_id = items[0].id;
            //
            //             this.ajaxcall("/egyptianratscrew/egyptianratscrew/playCard.html", {
            //                 id: card_id,
            //                 lock: true
            //             }, this, function (result) {
            //             }, function (is_error) {
            //             });
            //
            //             this.playerHand.unselectAll();
            //         } else if (this.checkAction('giveCards')) {
            //             // Can give cards => let the player select some cards
            //             this.showMessage(_("You must select exactly 3 cards"), 'error');
            //         } else {
            //             this.playerHand.unselectAll();
            //         }
            //     }
            // },

            ///////////////////////////////////////////////////
            //// Reaction to cometD notifications

            /*
                setupNotifications:

                In this method, you associate each of your game notifications with your local method to handle it.

                Note: game notification names correspond to your "notifyAllPlayers" and "notifyPlayer" calls in
                      your emptygame.game.php file.

            */

            setupNotifications: function () {
                console.log('notifications subscriptions setup');

                dojo.subscribe('slapPile', this, "notif_slapPile");
                dojo.subscribe('playCard', this, "notif_playCard");
            },

            notif_playCard: function (notif) {
                console.log("Card played" + notif);
            },
            notif_slapPile: function (notif) {
                console.log("Pile slapped" + notif);
            },

            // notif_giveAllCardsToPlayer: function (notif) {
            //     // Move all cards on table to given table, then destroy them
            //     var winner_id = notif.args.player_id;
            //     for (var player_id in this.gamedatas.players) {
            //         var anim = this.slideToObject('cardontable_' + player_id, 'overall_player_board_' + winner_id);
            //         dojo.connect(anim, 'onEnd', function (node) {
            //             dojo.destroy(node);
            //         });
            //         anim.play();
            //     }
            // },
            // notif_newHand: function (notif) {
            //     // We received a new full hand of 13 cards.
            //     this.playerHand.removeAll();
            //
            //     for (var i in notif.args.cards) {
            //         var card = notif.args.cards[i];
            //         var color = card.type;
            //         var value = card.type_arg;
            //         this.playerHand.addToStockWithId(this.getCardUniqueId(color, value), card.id);
            //     }
            // },
        });
    });


