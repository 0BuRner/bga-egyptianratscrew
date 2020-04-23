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
        self::initGameStateLabels(array(
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
        $nbrPlayersOut = self::getUniqueValueFromDb("SELECT COUNT(*) FROM player WHERE eliminated = 1");
        $totalPlayers = self::getUniqueValueFromDb("SELECT COUNT(*) FROM player");

        return intval(($nbrPlayersOut / $totalPlayers) * 100);   // Note: 0 => 100
    }

    protected function setupNewGame($players, $options = array())
    {
        // Set the colors of the players with HTML color code
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $default_color = array("152c81", "cf6402", "1d860a", "9d0000", "017071", "656565", "400061", "000000", "a55796", "703301");

        // Create players
        $this->createPlayers($players, $default_color);

        /************ Start the game initialization *****/
        // Init game statistics
        $this->initStats();

        // Init game variables

        // Create cards
        $this->cards->createCards($this->createCards(), 'deck');
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

        // Get information about players
        // Note: you can retrieve some extra field you add for "player" table in "dbmodel.sql" if you need it.
        $dbres = self::DbQuery("SELECT player_id id, player_score score, eliminated, penalty FROM player WHERE 1");
        while ($player = mysqli_fetch_assoc($dbres)) {
            $result['players'][$player['id']] = $player;
            // Players only know their number of cards in hand
            $result['players'][$player['id']]['cards'] = count($this->cards->getPlayerHand($player['id']));
        }

        // Cards played on the table
        $cards = array();
        $dbres = self::DbQuery("SELECT card_id id, card_type type, card_type_arg type_arg, card_location location, card_location_arg location_arg, hidden FROM card WHERE card_location = 'cardsontable'");
        while ($card = mysqli_fetch_assoc($dbres)) {
            $cards[$card['id']] = $card;
        }

        $result['cardsontable'] = $this->getCardsOnTableVisible($cards);

        // Cards on the table not visible (first turn)
        $result['hiddenCards'] = count($this->getCardsOnTableHidden($cards));

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

    private function getCardsOnTableVisible($cards) {
        return array_filter($cards, function($card) {
            return $card['hidden'] == 0;
        });
    }

    private function getCardsOnTableHidden($cards) {
        return array_filter($cards, function($card) {
            return $card['hidden'] == 1;
        });
    }

    // TODO on first hand win, make table card not hidden anymore

    private function getPlayerName($players, $player_id) {
        return $players[$player_id]['player_name'];
    }

    private function getSlappingPlayersName($slappingPlayers)
    {
        $result = array();
        $players = self::loadPlayersBasicInfos();
        foreach ($slappingPlayers as $player_id) {
            array_push($result, $this->getPlayerName($players, $player_id));
        }
        return $result;
    }

    private function getSlappingPlayers()
    {
        $result = array();

        $dbres = self::DbQuery("SELECT player_id id, slap_time FROM player WHERE slap_time IS NOT NULL ORDER BY slap_time ASC");
        while ($player = mysqli_fetch_assoc($dbres)) {
            array_push($result, $player['id']);
        }

        return $result;
    }

    private function updateSlappingPlayer($player_id)
    {
        self::DbQuery("UPDATE player SET slap_time='".(microtime(true) * 10000)."' WHERE player_id='$player_id'");
    }

    private function resetSlappingPlayers()
    {
        self::DbQuery("UPDATE player SET slap_time=NULL WHERE 1");
    }

    private function processSlap()
    {
        $cardsOnTable = $this->cards->getCardsInLocation("cardsontable");
        // TODO call every rules on cards stack
        $isSlappable = false;

        $slappingPlayers = $this->getSlappingPlayers();
        // check someone slap the pile
        if (!empty($slappingPlayers)) {
            if ($isSlappable) {
                $this->processSlapWinner($slappingPlayers);
                $this->processSlapLosers($slappingPlayers);
            } else {
                $this->processSlapFail($slappingPlayers);
            }
            $this->resetSlappingPlayers();
        } else if (empty($slappingPlayers) && $isSlappable) {
            self::incStat(1, "pileSlapMissed");
        }
    }

    private function processSlapWinner($slappingPlayers)
    {
        // Winner is the fastest player who slap the pile, so the first in the array
        $winner_id = array_shift($slappingPlayers);
        // Move cards on the table to the bottom of the player's hand
        $this->cards->moveAllCardsInLocation('cardsontable', 'hand', null, $winner_id);
        // Notify all players about the winner
        self::notifyAllPlayers('slapWon', clienttranslate('${player_name} won the pile !'), array(
            'player_id' => $winner_id,
            'player_name' => $this->getPlayerName(self::loadPlayersBasicInfos(), $winner_id),
        ));
        // Increment stats counter
        self::incStat(1, "pileSlapWon", $winner_id);
        self::incStat(1, "pileSlapOk");
    }

    private function processSlapLosers($slappingPlayers)
    {
        // # check slap losers
        foreach ($slappingPlayers as $loser_id) {
            self::incStat(1, "pileSlapLost", $loser_id);
        }
    }

    private function processSlapFail($slappingPlayers)
    {
        // # check slap fails
        foreach ($slappingPlayers as $fail_player_id) {
            // TODO use game variable/constant for nbr of cards instead of hardcoded
            // Move 3rd top of player's cards to the bottom of the board cards pile
            $card_ids = array_column(array_slice($this->cards->getPlayerHand($fail_player_id), -3, 3, true), 'id');
            $this->cards->moveCards($card_ids, 'cardsontable');

            self::incStat(1, "pileSlapFailed", $fail_player_id);
        }
        // Notify all about wrong slap to apply penalty
        self::notifyAllPlayers('slapFailed', clienttranslate('Penalty for ${players_name} who wrongly slapped the pile !'), array(
            'players_id' => $slappingPlayers,
            'players_name' => implode(', ', $this->getSlappingPlayersName($slappingPlayers)),
            'penalty' => 3
        ));
    }

    private function processChallenge()
    {
//        // Pick "$nbr" cards from a "pile" location (ex: "deck") and place them in the "hand" of specified player.
//        $this->cards->pickCards($nbr, $location, $player_id);

    }

    private function processPenalty($player_id, $nbrOfCards)
    {
        // TODO in this method: notify/All

        $player_cards = $this->cards->getPlayerHand($player_id);
        if (count($player_cards) - $nbrOfCards <= 0) {
            $this->eliminatePlayerCustom($player_id);
            return count($player_cards);
        }
        $cards_id = array_column(array_slice($player_cards, 0, $nbrOfCards, true), 'id');
        $this->cards->moveCards($cards_id, 'cardsontable');

        return count($player_cards);
    }

    private function eliminatePlayerCustom($player_id)
    {
        self::DbQuery("UPDATE player SET eliminated=1 WHERE player_id='$player_id'");
        self::eliminatePlayer($player_id);

        // TODO check last player standing is winner
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of this method below is called.
        (note: each method below correspond to an input method in egyptianratscrew.action.php)
    */

    function slapPile()
    {
        self::debug("Pile slapped");

        $player_id = self::getCurrentPlayerId();

        $slappingPlayers = $this->getSlappingPlayers();
        // Update slapping player list
        if (!in_array($player_id, $slappingPlayers)) {
            $this->updateSlappingPlayer($player_id);
            self::notifyAllPlayers('slapPile', clienttranslate('${player_name} slapped the pile !'), array(
                'player_name' => self::getCurrentPlayerName()
            ));
        }
    }

    function playCard()
    {
        self::debug("Card played");

        $current_player_id = self::getCurrentPlayerId();
        $active_player_id = self::getActivePlayerId();
        $state = $this->gamestate->state();

        // Avoid multiple play from active player
        if ($state['name'] == 'validateTurn') {
            if ($current_player_id == $active_player_id) {
                throw new feException(self::_("You already played a card"), true);
            }
        }

        // Penalty for playing when it wasn't his turn
        // TODO improve by keeping list of players having already played (like slap) to avoid abusive multiple penalties
        if ($current_player_id != $active_player_id) {
//            $result = $this->processPenalty($player_id, 3);
//            if ($result == 0) {
//                throw new feException(self::_("No cards remaining. Bye bye !"), true);
//            }
            throw new feException(self::_("It was not your turn to play ! You got a penalty !"), true);
        }

        // Check the penalty didn't result to end of game for the player
        // TODO call processEndGame instead
        if ($this->cards->countCardInLocation('hand', $current_player_id) == 52) {
            throw new feException(self::_("You won the game"), true);
        } else if ($this->cards->countCardInLocation('hand', $current_player_id) == 0) {
            throw new feException(self::_("You lost the game"), true);
        }

        // Play the top card of the current player
        $player_cards = $this->cards->getPlayerHand($current_player_id);
        $top_card = array_values($player_cards)[count($player_cards)-1];
        $top_card_id = $top_card['id'];
        $this->cards->moveCard($top_card_id, 'cardsontable');

        // Update card location to keep card order (in case of refresh)
        // TODO time() is not accurate enough
        self::DbQuery("UPDATE card SET card_location_arg=".time()." WHERE card_id='$top_card_id'");

        // Notify all players
        self::notifyAllPlayers('playCard', clienttranslate('${player_name} plays ${value_displayed} ${color_displayed}'), array(
            'i18n' => array('value_displayed'),
            'card_id' => $top_card_id,
            'player_id' => $current_player_id,
            'player_name' => self::getActivePlayerName(),
            'value' => $top_card['type_arg'],
            'value_displayed' => $this->values_label[$top_card['type_arg']],
            'color' => $top_card['type'],
            'color_displayed' => $this->colors[$top_card['type']]['name'],
            'timestamp' => time()
        ));

        $this->gamestate->nextState('validateTurn');
    }

    function validateTurn()
    {
        if (self::getCurrentPlayerId() == self::getActivePlayerId()) {
            $this->gamestate->nextState('endTurn');
        }
    }

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

    function stInit()
    {
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

        // Put undealt cards to the table as hidden cards
        $this->cards->pickCardsForLocation($nbrCardsOnTable, 'deck', 'cardsontable');
        $cardsOnTable = $this->cards->getCardsInLocation('cardsontable');
        foreach ($cardsOnTable as $card_id => $cardOnTable) {
            $cardOnTable['hidden'] = true;
            self::DbQuery("UPDATE card SET hidden=1, card_location_arg=". time() ." WHERE card_id='$card_id'");
        }

        // Active first player
        $this->activeNextPlayer();

        // Game ready to start
        $this->gamestate->nextState("playerTurn");
    }

    function stPlayerTurn()
    {
        self::debug("state: playerTurn");
    }

    function stValidateTurn()
    {
        self::debug("state: validateTurn");
    }

    function stEndTurn()
    {
        self::debug("state: endTurn");

        // check slap was applicable
        $this->processSlap();

        // check challenge

        // check and apply player penalty

        // check end game


        // check player eliminated and apply score
        $players = self::loadPlayersBasicInfos();
        $active_player_id = self::getActivePlayerId();
        foreach ($players as $player_id => $player) {
            $cards = $this->cards->getPlayerHand($player_id);
            $nbrCards = count($cards);
            if ($nbrCards == 0) {
                // Update winner score
                self::DbQuery("UPDATE player SET player_score=player_score+1 WHERE player_id='$active_player_id'");
                // Update players stats
                self::incStat(1, "playerEliminated", $active_player_id);
                // Set eliminated player as out of the table
                $this->eliminatePlayerCustom($player_id);
            } else if ($nbrCards == 52) {
                $this->gamestate->nextState("endGame");
                return;
            }
        }

        // update stats
        // TODO?

        $this->activeNextPlayer();

        $this->gamestate->nextState("playerTurn");
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
  

