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
    private $slappingPlayers;

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

        $this->slappingPlayers = array();

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
        while ($player = mysql_fetch_assoc($dbres)) {
            $result['players'][$player['id']] = $player;
            $result['players'][$player['id']]['cards'] = count($this->cards->getPlayerHand($player['id']));
        }

        // Cards in player hand
        $player_id = self::getCurrentPlayerId();
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
        // TODO call every rules on cards stack
        $isSlappable = false;

        // check someone slap the pile
        if (!empty($this->slappingPlayers)) {
            if ($isSlappable) {
                // # check slap winner
                $winner_id = array_shift($this->slappingPlayers);
                // Move cards on the table to the bottom of the player's hand
                $this->cards->moveAllCardsInLocation('cardsontable', 'hand', null, $winner_id);
                self::incStat(1, "pileSlapWon", $winner_id);

                // # check slap losers
                foreach ($this->slappingPlayers as $loser_id) {
                    self::incStat(1, "pileSlapLost", $loser_id);
                }
                self::incStat(1, "pileSlapOk");
            } else {
                // # check slap fails
                foreach ($this->slappingPlayers as $fail_player_id) {
                    self::incStat(1, "pileSlapFailed", $fail_player_id);
                    // Move 3rd top of player's cards to the bottom of the board cards pile
                    $card_ids = array_slice($this->cards->getPlayerHand($fail_player_id), -3, 3, true);
                    $this->cards->moveCards($card_ids, 'cardsontable');
                }
            }
            $this->slappingPlayers = array();
        } else if (empty($this->slappingPlayers) && $isSlappable) {
            self::incStat(1, "pileSlapMissed");
        }
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

        // Update slapping player list
        if (!in_array($player_id, $this->slappingPlayers)) {
            array_push($this->slappingPlayers, $player_id);
            self::notifyAllPlayers('slapPile', clienttranslate('${player_name} slapped the pile ! ${slaps}'), array(
                'player_name' => self::getCurrentPlayerName(),
                'slaps' => implode(';', $this->slappingPlayers)
            ));
        }
    }

    function playCard()
    {
        self::debug("Card played");

        $player_id = self::getCurrentPlayerId();

        // Penalty for playing when it wasn't his turn
        if ($player_id != self::getActivePlayerId()) {
            $result = $this->processPenalty($player_id, 3);
            if ($result == 0) {
                throw new feException(self::_("No cards remaining. Bye bye !"), true);
            }
            throw new feException(self::_("It was not your turn to play ! You got a penalty of " . $result . " cards !"), true);
        }

        if ($this->cards->countCardInLocation('hand', $player_id) == 52) {
            throw new feException(self::_("You won the game"), true);
        } else if ($this->cards->countCardInLocation('hand', $player_id) == 0) {
            throw new feException(self::_("You lost the game"), true);
        }

        // Checks are done! Play the top card of the current player
        $player_cards = $this->cards->getPlayerHand($player_id);
        $top_card = array_values($player_cards)[count($player_cards)-1];
        $top_card_id = $top_card['id'];
        $this->cards->moveCard($top_card_id, 'cardsontable');

        self::notifyAllPlayers('playCard', clienttranslate('${player_name} plays ${value_displayed} ${color_displayed}'), array(
            'i18n' => array('color_displayed', 'value_displayed'),
            'card_id' => $top_card_id,
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'value' => $top_card['type_arg'],
            'value_displayed' => $this->values_label[$top_card['type_arg']],
            'color' => $top_card['type'],
            'color_displayed' => $this->colors[$top_card['type']]['name']
        ));

        // TODO go to temporary state and wait predefined timeout before calling endTurn
        $this->gamestate->nextState('endTurn');
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

        // Put undealt cards to the table
        $this->cards->pickCardsForLocation($nbrCardsOnTable, 'deck', 'cardsontable');

        // Active first player
        $this->activeNextPlayer();

        // Game ready to start
        $this->gamestate->nextState("playerTurn");
    }

    function stPlayerTurn()
    {
        self::debug("state: playerTurn");
    }

    function stEndTurn()
    {
        self::debug("state: endTurn");
        // check slap was applicable
        $this->processSlap();

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
  

