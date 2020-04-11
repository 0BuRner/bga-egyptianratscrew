<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Egyptian Ratscrew implementation : 0BuRner <https://github.com/0BuRner>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * egyptianratscrew.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */


require_once(APP_GAMEMODULE_PATH . 'module/table/table.game.php');


class EgyptianRatscrew extends Table
{
    function __construct()
    {
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        // TODO
        self::initGameStateLabels(array(
            "slappingPlayers" => 11,
            "gameLength" => 100));

        $this->cards = self::getNew("module.common.deck");
        $this->cards->init("card");
    }

    protected function getGameName()
    {
        return "egyptianratscrew";
    }

    /*
        getGameProgression:

        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).

        This method is called each time we are in a game state with "updateGameProgression" property (see states.inc.php)
    */
    function getGameProgression()
    {
        $nbrPlayersOut = self::getUniqueValueFromDb("SELECT COUNT(*) FROM player WHERE out = 1");
        $totalPlayers = self::getUniqueValueFromDb("SELECT COUNT(*) FROM player");

        return intval(($nbrPlayersOut / $totalPlayers) * 100);   // Note: 0 => 100
    }

    protected function setupNewGame($players, $options = array())
    {
        // Set the colors of the players with HTML color code
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $default_color = array("152c81", "cf6402", "018944", "9d0000", "017071", "fff301", "400061", "a1d97d", "8d0073", "703301");

        // Create players
        $this->createPlayers($players, $default_color);

        /************ Start the game initialization *****/
        // Init game statistics
        $this->initStats();

        // Create cards
        $this->cards->createCards($this->createCards(), 'deck');

        // Active first player
        self::activeNextPlayer();
        /************ End of the game initialization *****/
    }

    /**
     * Gather all informations about current game situation (visible by the current player).<br>
     *
     * The method is called each time the game interface is displayed to a player, ie:
     * <ul>
     *  <li>when the game starts</li>
     *  <li>when a player refresh the game page (F5)</li>
     * </ul>
     *
     * @return array
     */
    protected function getAllDatas()
    {
        $result = array('players' => array());

        $player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        // Get information about players
        // Note: you can retrieve some extra field you add for "player" table in "dbmodel.sql" if you need it.
        $dbres = self::DbQuery("SELECT player_id id, player_score score, out, penalty FROM player WHERE 1");
        while ($player = mysql_fetch_assoc($dbres)) {
            $result['players'][$player['id']] = $player;
        }

        // Cards in player hand
        $result['hand'] = $this->cards->getPlayerHand($player_id);

        // Cards played on the table
        $result['cardsontable'] = $this->cards->getCardsInLocation('cardsontable');

        return $result;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        At this place, you can put any utility methods useful for your game logic
    */
    private function initStats()
    {
        self::initStat("table", "pileSlapOk", 0);
        self::initStat("table", "pileSlapMissed", 0);
        self::initStat("player", "challengeWon", 0);
        self::initStat("player", "challengeLost", 0);
        self::initStat("player", "pileSlapWon", 0);
        self::initStat("player", "pileSlapLost", 0);
        self::initStat("player", "pileSlapFailed", 0);
        //self::initStat("player", "turnFailed", 0);
    }

    private function createPlayers($players, $colors)
    {
        // Clean up
        self::DbQuery("DELETE FROM player WHERE 1");

        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialized it there.
        $sql = "INSERT INTO player (player_id, player_score, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach ($players as $player_id => $player) {
            $color = array_shift($colors);
            $values[] = "('" . $player_id . "','0','$color','" . $player['player_canal'] . "','" . addslashes($player['player_name']) . "','" . addslashes($player['player_avatar']) . "')";
        }
        $sql .= implode(',', $values);
        self::DbQuery($sql);

        self::reloadPlayersBasicInfos();
    }

    private function createCards()
    {
        $cards = array();
        foreach ($this->colors as $color_id => $color) // spade, heart, diamond, club
        {
            for ($value = 2; $value <= 14; $value++)   //  2, 3, 4, ... K, A
            {
                $cards[] = array('type' => $color_id, 'type_arg' => $value, 'nbr' => 1);
            }
        }
        return $cards;
    }

    private function processSlap()
    {
        $cardsOnTable = $this->cards->getCardsInLocation("cardsontable");
        // call every rules on cards stack
        $isSlappable = true;

        // check someone slap the pile
        $playersSlap = self::getGameStateValue('slappingPlayers');
        if (!empty($playersSlap))
        {
            if ($isSlappable)
            {
                // # check slap winner
                $winner_id = array_shift($playersSlap);
                // Move cards on the table to the bottom of the player's hand
                $this->cards->moveAllCardsInLocation('cardsontable', 'hand', null, $winner_id);
                self::incStat(1, "pileSlapWon", $winner_id);

                // # check slap losers
                foreach ($playersSlap as $loser_id)
                {
                    self::incStat(1, "pileSlapLost", $loser_id);
                }
                self::incStat(1, "pileSlapOk");
            }
            else
            {
                // # check slap fails
                foreach ($playersSlap as $fail_player_id)
                {
                    self::incStat(1, "pileSlapFailed", $fail_player_id);
                    // Move 3rd top of player's cards to the bottom of the board cards pile
                    $card_ids = array_slice($this->cards->getPlayerHand($fail_player_id), -3, 3);
                    $this->cards->moveCards($card_ids, 'cardsontable');
                }
            }
            self::setGameStateValue("slappingPlayers", array());
        }
        else if (empty($playersSlap) && $isSlappable)
        {
            self::incStat(1, "pileSlapMissed");
        }
    }

    private function processChallenge()
    {

    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of this method below is called.
        (note: each method below correspond to an input method in egyptianratscrew.action.php)
    */

    function playCard()
    {
        self::checkAction("playCard");
        // Check player is not out of the game

        $player_id = self::getActivePlayerId();

        $card_id = 0;// TODO find player's hand top card

        if ($this->cards->countCardInLocation('hand', $player_id) == 52) {
            throw new feException(self::_("You won the game"), true);
        } else if ($this->cards->countCardInLocation('hand', $player_id) == 0) {
            throw new feException(self::_("You lost the game"), true);
        }

        // Checks are done! now we can play our card
        $this->cards->moveCard($card_id, 'cardsontable', $player_id);

//        // And notify
//        self::notifyAllPlayers('playCard', clienttranslate('${player_name} plays ${value_displayed} ${color_displayed}'), array(
//            'i18n' => array('color_displayed', 'value_displayed'),
//            'card_id' => $card_id,
//            'player_id' => $player_id,
//            'player_name' => self::getActivePlayerName(),
//            'value' => $currentCard['type_arg'],
//            'value_displayed' => $this->values_label[$currentCard['type_arg']],
//            'color' => $currentCard['type'],
//            'color_displayed' => $this->colors[$currentCard['type']]['name']
//        ));

        // Next player
        // TODO wait predefined timeout before calling endTurn
        $this->gamestate->nextState('playCard');
    }

    function giveCards($card_ids)
    {
        self::checkAction("giveCards");

        // !! Here we have to get CURRENT player (= player who send the request) and not
        //    active player, cause we are in a multiple active player state and the "active player"
        //    correspond to nothing.
        $player_id = self::getCurrentPlayerId();

        if (count($card_ids) != 3)
            throw new feException(self::_("You must give exactly 3 cards"));

        // Check if these cards are in player hands
        $cards = $this->cards->getCards($card_ids);

        if (count($cards) != 3)
            throw new feException(self::_("Some of these cards don't exist"));

        foreach ($cards as $card) {
            if ($card['location'] != 'hand' || $card['location_arg'] != $player_id)
                throw new feException(self::_("Some of these cards are not in your hand"));
        }

        // To which player should I give these cards ?
        $player_to_give_cards = null;
        $player_to_direction = self::getPlayersToDirection();   // Note: current player is on the south
        $handType = self::getGameStateValue("currentHandType");
        if ($handType == 0)
            $direction = 'W';
        else if ($handType == 1)
            $direction = 'N';
        else if ($handType == 2)
            $direction = 'E';
        foreach ($player_to_direction as $opponent_id => $opponent_direction) {
            if ($opponent_direction == $direction)
                $player_to_give_cards = $opponent_id;
        }
        if ($player_to_give_cards === null)
            throw new feException(self::_("Error while determining to who give the cards"));

        // Allright, these cards can be given to this player
        // (note: we place the cards in some temporary location in order he can't see them before the hand starts)
        $this->cards->moveCards($card_ids, "temporary", $player_to_give_cards);

        // Notify the player so we can make these cards disapear
        self::notifyPlayer($player_id, "giveCards", "", array(
            "cards" => $card_ids
        ));

        // Make this player unactive now
        // (and tell the machine state to use transtion "giveCards" if all players are now unactive
        $this->gamestate->setPlayerNonMultiactive($player_id, "giveCards");
    }


    // Play a card from player hand

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defines as "game state arguments" (see "args" property in states.inc.php).
        These methods are returning some additional informations that are specific to the current
        game state.
    */


//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defines as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */

    function stInit() {
        $players = self::loadPlayersBasicInfos();

        // Take back all cards (from any location => null) to deck
        $this->cards->moveAllCardsInLocation(null, "deck");
        $this->cards->shuffle('deck');

        // Initial cards count
        $nbrCards = 52;
        $nbrPlayers = count($players);
        $nbrCardsByPlayer = intval($nbrCards / $nbrPlayers);
        $nbrCardsOnTable = $nbrCards - ($nbrCardsByPlayer * $nbrPlayers);

        // Deal cards to each player
        foreach ($players as $player_id => $player) {
            $this->cards->pickCards($nbrCardsByPlayer, 'deck', $player_id);
        }

        // Put undealt cards to the table
        $this->cards->pickCardsForLocation($nbrCardsOnTable, 'deck', 'cardsontable');

        // Notify all players about cards distribution
//        self::notifyAllPlayers('playCard', clienttranslate('${player_name} plays ${value_displayed} ${color_displayed}'), array(
//            'i18n' => array('color_displayed', 'value_displayed'),
//            'card_id' => $card_id,
//            'player_id' => $player_id,
//            'player_name' => self::getActivePlayerName(),
//            'value' => $currentCard['type_arg'],
//            'value_displayed' => $this->values_label[$currentCard['type_arg']],
//            'color' => $currentCard['type'],
//            'color_displayed' => $this->colors[$currentCard['type']]['name']
//        ));

        // Game ready to start
        $this->gamestate->nextState("playerTurn");
    }

    function stPlayerTurn()
    {
        // TODO
    }

    function stEndTurn()
    {
        // TODO

        // check slap was applicable
        $this->processSlap();

        // check end game

        // check and apply player penalty


        // check player eliminated and apply score
        $players = self::loadPlayersBasicInfos();
        $active_player_id = self::getActivePlayerId();
        foreach ($players as $player_id => $player)
        {
            $cards = $this->cards->getPlayerHand($player_id);
            $nbrCards = count($cards);
            if ($nbrCards == 0)
            {
                // Update winner score
                self::DbQuery("UPDATE player SET player_score=player_score+1 WHERE player_id='$active_player_id'");
                // Update players stats
                self::incStat(1, "playerEliminated", $active_player_id);
                // Set eliminated player as out of the table
                self::DbQuery("UPDATE player SET out=1 WHERE player_id='$player_id'");
            }
        }

        // update stats
//        self::incStat(1, "handNbr");
//        self::incStat(1, "getAllPointCards", $player_id);

        // nextState: playerTurn or endGame
//        $this->gamestate->nextState("");
    }

    function stNextPlayer()
    {
        // Active next player OR end the trick and go to the next trick OR end the hand

        if ($this->cards->countCardInLocation('cardsontable') == 4) {
            // This is the end of the trick
            // Who wins ?

            $cards_on_table = $this->cards->getCardsInLocation('cardsontable');
            $best_value = 0;
            $best_value_player_id = null;
            $currentTrickColor = self::getGameStateValue('trickColor');

            foreach ($cards_on_table as $card) {
                if ($card['type'] == $currentTrickColor)   // Note: type = card color
                {
                    if ($best_value_player_id === null) {
                        $best_value_player_id = $card['location_arg'];  // Note: location_arg = player who played this card on table
                        $best_value = $card['type_arg'];        // Note: type_arg = value of the card
                    } else if ($card['type_arg'] > $best_value) {
                        $best_value_player_id = $card['location_arg'];  // Note: location_arg = player who played this card on table
                        $best_value = $card['type_arg'];        // Note: type_arg = value of the card
                    }
                }
            }

            if ($best_value_player_id === null)
                throw new feException(self::_("Error, nobody wins the trick"));

            // Move all cards to "cardswon" of the given player
            $this->cards->moveAllCardsInLocation('cardsontable', 'cardswon', null, $best_value_player_id);

            // Notify
            // Note: we use 2 notifications here in order we can pause the display during the first notification
            //  before we move all cards to the winner (during the second)
            $players = self::loadPlayersBasicInfos();
            self::notifyAllPlayers('trickWin', clienttranslate('${player_name} wins the trick'), array(
                'player_id' => $best_value_player_id,
                'player_name' => $players[$best_value_player_id]['player_name']
            ));
            self::notifyAllPlayers('giveAllCardsToPlayer', '', array(
                'player_id' => $best_value_player_id
            ));

            // Active this player => he's the one who starts the next trick
            $this->gamestate->changeActivePlayer($best_value_player_id);

            if ($this->cards->countCardInLocation('hand') == 0) {
                // End of the hand
                $this->gamestate->nextState("endHand");
            } else {
                // End of the trick
                $this->gamestate->nextState("nextTrick");
            }
        } else {
            // Standard case (not the end of the trick)
            // => just active the next player

            $player_id = self::activeNextPlayer();
            self::giveExtraTime($player_id);

            $this->gamestate->nextState('nextPlayer');
        }
    }

    function stTakeCards()
    {
        // Take cards given by the other player

        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            // Each player takes cards in the "temporary" location and place it in his hand
            $cards = $this->cards->getCardsInLocation("temporary", $player_id);
            $this->cards->moveAllCardsInLocation("temporary", "hand", $player_id, $player_id);

            self::notifyPlayer($player_id, "takeCards", "", array(
                "cards" => $cards
            ));
        }

        // Note: club=4
        $twoClubCardOwner = self::getUniqueValueFromDb("SELECT card_location_arg FROM card
                                                         WHERE card_location='hand'
                                                         AND card_type='3' AND card_type_arg='2' ");
        if ($twoClubCardOwner !== null) {
            $this->gamestate->changeActivePlayer($twoClubCardOwner);
        } else {
            throw new feException(self::_("Cant find Club-2"));
        }

        $this->gamestate->nextState("startHand");  // For now
    }

    function stEndHand()
    {
        // Count and score points, then end the game or go to the next hand.

        $players = self::loadPlayersBasicInfos();

        // Gets all "egyptianratscrew" + queen of spades
        $player_with_queen_of_spades = null;
        $player_to_egyptianratscrew = array();
        $player_to_points = array();
        foreach ($players as $player_id => $player) {
            $player_to_egyptianratscrew[$player_id] = 0;
            $player_to_points[$player_id] = 0;
        }

        $cards = $this->cards->getCardsInLocation("cardswon");
        foreach ($cards as $card) {
            $player_id = $card['location_arg'];

            if ($card['type'] == 1 && $card['type_arg'] == 12)    // Note: 1 = spade && 12 = queen
            {
                // Queen of club => 13 points
                $player_to_points[$player_id] += 13;
                $player_with_queen_of_spades = $player_id;
            } else if ($card['type'] == 2)   // Note: 2 = heart
            {
                $player_to_egyptianratscrew[$player_id]++;
                $player_to_points[$player_id]++;
            }
        }

        // If someone gets all egyptianratscrew and the queen of club => 26 points for eveyone
        $nbr_nonzero_score = 0;
        foreach ($player_to_points as $player_id => $points) {
            if ($points != 0)
                $nbr_nonzero_score++;
        }

        $bOnePlayerGetsAll = ($nbr_nonzero_score == 1);

        if ($bOnePlayerGetsAll) {
            // Only 1 player score points during this hand
            // => he score 0 and everyone scores -26
            foreach ($player_to_egyptianratscrew as $player_id => $points) {
                if ($points != 0) {
                    $player_to_points[$player_id] = 0;

                    // Notify it!
                    self::notifyAllPlayers("onePlayerGetsAll", clienttranslate('${player_name} gets all egyptianratscrew and the Queen of Spades: everyone else loose 26 points!'), array(
                        'player_id' => $player_id,
                        'player_name' => $players[$player_id]['player_name']
                    ));

                    self::incStat(1, "getAllPointCards", $player_id);
                } else
                    $player_to_points[$player_id] = 26;
            }
        }

        // Apply scores to player
        foreach ($player_to_points as $player_id => $points) {
            if ($points != 0) {
                $sql = "UPDATE player SET player_score=player_score-$points
                        WHERE player_id='$player_id' ";
                self::DbQuery($sql);

                // Now, notify about the point lost.
                if (!$bOnePlayerGetsAll)  // Note: if one player gets all, we already notify everyone so there's no need to send additional notifications
                {
                    $heart_number = $player_to_egyptianratscrew[$player_id];
                    if ($player_id == $player_with_queen_of_spades) {
                        self::notifyAllPlayers("points", clienttranslate('${player_name} gets ${nbr} egyptianratscrew and the Queen of Spades and looses ${points} points'), array(
                            'player_id' => $player_id,
                            'player_name' => $players[$player_id]['player_name'],
                            'nbr' => $heart_number,
                            'points' => $points
                        ));
                    } else {
                        self::notifyAllPlayers("points", clienttranslate('${player_name} gets ${nbr} egyptianratscrew and looses ${nbr} points'), array(
                            'player_id' => $player_id,
                            'player_name' => $players[$player_id]['player_name'],
                            'nbr' => $heart_number
                        ));
                    }
                }
            } else {
                // No point lost (just notify)
                self::notifyAllPlayers("points", clienttranslate('${player_name} did not get any egyptianratscrew'), array(
                    'player_id' => $player_id,
                    'player_name' => $players[$player_id]['player_name']
                ));

                self::incStat(1, "getNoPointCards", $player_id);
            }
        }

        $newScores = self::getCollectionFromDb("SELECT player_id, player_score FROM player", true);
        self::notifyAllPlayers("newScores", '', array('newScores' => $newScores));

        //////////// Display table window with results /////////////////
        $table = array();

        // Header line
        $firstRow = array('');
        foreach ($players as $player_id => $player) {
            $firstRow[] = array('str' => '${player_name}',
                'args' => array('player_name' => $player['player_name']),
                'type' => 'header'
            );
        }
        $table[] = $firstRow;

        // Hearts
        $newRow = array(array('str' => clienttranslate('Hearts'), 'args' => array()));
        foreach ($player_to_egyptianratscrew as $player_id => $egyptianratscrew) {
            $newRow[] = $egyptianratscrew;

            if ($egyptianratscrew > 0)
                self::incStat($egyptianratscrew, "getHearts", $player_id);
        }
        $table[] = $newRow;

        // Queen of spades
        $newRow = array(array('str' => clienttranslate('Queen of Spades'), 'args' => array()));
        foreach ($player_to_egyptianratscrew as $player_id => $egyptianratscrew) {
            if ($player_id == $player_with_queen_of_spades) {
                $newRow[] = '1';
                self::incStat(1, "getQueenOfSpade", $player_id);
            } else
                $newRow[] = '0';
        }
        $table[] = $newRow;

        // Points
        $newRow = array(array('str' => clienttranslate('Points'), 'args' => array()));
        foreach ($player_to_points as $player_id => $points) {
            $newRow[] = $points;
        }
        $table[] = $newRow;


        $this->notifyAllPlayers("tableWindow", '', array(
            "id" => 'finalScoring',
            "title" => clienttranslate("Result of this hand"),
            "table" => $table
        ));

        // Change the "type" of the next hand
        $handType = self::getGameStateValue("currentHandType");
        self::setGameStateValue("currentHandType", ($handType + 1) % 4);

        ///// Test if this is the end of the game
        foreach ($newScores as $player_id => $score) {
            if ($score <= 0) {
                // Trigger the end of the game !
                $this->gamestate->nextState("endGame");
                return;
            }
        }

        // Otherwise... new hand !
        $this->gamestate->nextState("nextHand");
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player that quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player will end
        (ex: pass).
    */
    function zombieTurn($state, $active_player)
    {
        throw new feException("Zombie mode not supported for Egyptian Ratscrew");
    }
}
  

