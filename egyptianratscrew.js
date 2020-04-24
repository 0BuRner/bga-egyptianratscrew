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
                console.log('Egyptian Ratscrew constructor');
                this.cardwidth = 72;
                this.cardheight = 96;

                // Here, you can init the global variables of your user interface
                this.players = [];
                this.tableStock = null;
                this.playerStocks = [];
                this.cardsOrder = { '-1': 0 };
                this.slapCounter = 0;
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

                // Create cards stocks:
                this.createTableStock();
                this.initTableCards();

                this.players = gamedatas.players;

                for (let player_id in gamedatas.players) {
                    let nbr_cards = gamedatas.players[player_id].cards;
                    this.createPlayerStock(player_id);
                    this.addPlayerCards(player_id, nbr_cards);
                }

                // Setup game actions trigger
                // dojo.connect($("pile"), "onclick", this, "onSlapPile");
                // dojo.connect(this.tableStock, 'onChangeSelection', this, 'onSlapPile');
                dojo.connect($("player_hand_" + this.player_id), 'onclick', this, 'onSlapPile');

                dojo.connect(this.playerStocks[this.player_id], 'onChangeSelection', this, 'onPlayCard');

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
            },

            // onLeavingState: this method is called each time we are leaving a game state.
            //                 You can use this method to perform some user interface changes at this moment.
            //
            onLeavingState: function (stateName) {
                console.log('Leaving state: ' + stateName);

                switch (stateName) {
                    case 'validateTurn':
                        // reset hands position
                        for (let player_id in this.players) {
                            // position must match 'top' and 'left' values in css
                            this.slideToObjectPos("player_hand_" + player_id, "player_seat_" + player_id, 85, 35).play();
                        }
                        this.slapCounter = 0;
                        break;
                }
            },

            // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
            //                        action status bar (ie: the HTML links in the status bar).
            //
            onUpdateActionButtons: function (stateName, args) {
                console.log('onUpdateActionButtons: ' + stateName + ' : ' + args);

                if (this.isCurrentPlayerActive()) {
                    switch (stateName) {
                        case 'validateTurn':
                            this.addActionButton('validateTurn_button', _('Validate turn'), 'onValidateTurn');
                            break;
                    }
                }
            },

            ///////////////////////////////////////////////////
            //// Utility methods

            /*
                Here, you can defines some utility methods that you can use everywhere in your javascript
                script.
            */

            sleep: function(ms) {
                return new Promise(resolve => setTimeout(resolve, ms));
            },

            createTableStock: function() {
                this.tableStock = new ebg.stock();
                this.tableStock.image_items_per_row = 13;
                this.tableStock.setOverlap(10,5);
                this.tableStock.setSelectionMode(1);
                this.tableStock.setSelectionAppearance(null);
                // this.tableStock.centerItems = true;
                this.tableStock.create(this, $('pile'), this.cardwidth, this.cardheight);
                // Add back card type
                this.tableStock.addItemType(-1, 0, g_gamethemeurl + 'img/card_back.png');
                // Add visible card types
                for (var color = 1; color <= 4; color++) {
                    for (var value = 2; value <= 14; value++) {
                        // Build card type id
                        var card_type_id = this.getCardUniqueId(color, value);
                        this.tableStock.addItemType(card_type_id, 0, g_gamethemeurl + 'img/cards.jpg', card_type_id);
                    }
                }
            },

            initTableCards: function() {
                // Initialize table cards
                let cardsOnTable = this.gamedatas.cardsontable;
                for (let i in cardsOnTable) {
                    let card = cardsOnTable[i];
                    let card_id = this.getCardUniqueId(card.type, card.type_arg);
                    this.cardsOrder[card_id] = card.play_time;
                    this.tableStock.addToStockWithId(card_id, card_id);
                }
                // Sort cards to keep their play order
                this.tableStock.changeItemsWeight(this.cardsOrder);

                // Hack: -10 to keep stock order (there are maximum 4 hidden cards on table)
                for (let j = 0; j < this.gamedatas.hiddenCards; j++) {
                    this.tableStock.addToStockWithId(-1, j);
                }
            },

            createPlayerStock: function(player_id) {
                let target = new ebg.stock();
                target.image_items_per_row = 13;
                target.setOverlap(2,0);
                target.item_margin = 0;
                target.setSelectionMode(1);
                target.setSelectionAppearance(null);
                target.create(this, $('player_cards_' + player_id), this.cardwidth, this.cardheight);
                target.addItemType(-1, 0, g_gamethemeurl + 'img/card_back.png');

                this.playerStocks[player_id] = target;
            },

            addPlayerCards: function(player_id, nbr_cards) {
                for (let i = 0; i < nbr_cards; i++) {
                    this.playerStocks[player_id].addToStockWithId(-1, i);
                }
            },

            removePlayerCards: async function(player_id, nbr_cards) {
                for (let i = 0; i < nbr_cards; i++) {
                    this.playerStocks[player_id].removeFromStock(-1, 'pile');
                    await this.sleep(150);
                }
            },

            /**
             * Get card unique identifier based on its color and value
              */
            getCardUniqueId: function (color, value) {
                return (color - 1) * 13 + (value - 2);
            },

            moveCardToPile: function (player_id, card_id) {
                // Refresh cards order
                this.tableStock.changeItemsWeight(this.cardsOrder);

                // Slide the card from player to pile and delete it after animation
                let player_stock = this.playerStocks[player_id];
                // TODO top card not bottom
                player_stock.removeFromStock(-1, 'pile');
                // Add visible card to the pile
                setTimeout(() => {
                    this.tableStock.addToStockWithId(card_id, card_id);
                }, 500);
            },

            moveCardsToBottomPile: function(player_id, nbr_cards) {
                this.removePlayerCards(player_id, nbr_cards);
            },

            moveAllCardsFromPileToPlayer: function(player_id) {
                let nbr_cards = this.tableStock.count();
                this.tableStock.removeAllTo("player_cards_" + player_id);
                setTimeout(() => {
                    this.addPlayerCards(player_id, nbr_cards);
                    this.cardsOrder = { '-1': 0 };
                }, 500);
            },

            ///////////////////////////////////////////////////
            //// Player's action

            /*

                Here, you are defining methods to handle player's action (ex: results of mouse click on
                game objects).

                Most of the time, these methods:
                _ check the action is possible at this game state.
                _ make a call to the game server

            */

            onSlapPile: function (event) {
                // TODO client-side animation before receiving server confirmation?
                console.log("Pile slapped " + event);

                this.ajaxcall("/egyptianratscrew/egyptianratscrew/slapPile.html", {}, this, function (result) {}, function (is_error) {});
            },

            onPlayCard: function (event) {
                // TODO client-side animation before receiving server confirmation?
                console.log("Card played " + event);

                this.ajaxcall("/egyptianratscrew/egyptianratscrew/playCard.html", {}, this, function (result) {}, function (is_error) {});
            },

            onValidateTurn: function (event) {
                this.ajaxcall("/egyptianratscrew/egyptianratscrew/validateTurn.html", {}, this, function (result) {}, function (is_error) {});
            },

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
                dojo.subscribe('slapFailed', this, "notif_slapFailed");
                dojo.subscribe('slapWon', this, "notif_slapWon");
            },

            notif_playCard: function (notif) {
                let player_id = notif.args.player_id;
                let card_id = this.getCardUniqueId(notif.args.color, notif.args.value);
                this.cardsOrder[card_id] = notif.args.timestamp;
                this.moveCardToPile(player_id, card_id);
            },
            notif_slapPile: function (notif) {
                let player_id = notif.args.player_id;
                let hand_id = "player_hand_" + player_id;

                // adjust z-index to keep hand order
                dojo.style(hand_id, 'z-index', this.slapCounter + 100);
                dojo.style('player_seat_' + player_id, 'z-index', this.slapCounter + 100);
                this.slapCounter++;
                // animation
                this.slideToObject(hand_id, "pile", 100).play();
            },
            notif_slapFailed: function (notif) {
                let players_id = notif.args.players_id;
                let penalty = notif.args.penalty;
                players_id.forEach((player_id) => {
                    this.moveCardsToBottomPile(player_id, penalty);
                });
            },
            notif_slapWon: function (notif) {
                let player_id = notif.args.player_id;
                this.moveAllCardsFromPileToPlayer(player_id);
            },
        });
    });


