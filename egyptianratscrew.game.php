<?php
/**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * Hearts implementation : © Gregory Isabelli <gisabelli@boardgamearena.com>
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
  

require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class Hearts extends Table
{
	function __construct( )
	{
        	

        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();self::initGameStateLabels( array( 
                         "currentHandType" => 10, 
                         "trickColor" => 11,
                         "alreadyPlayedHearts" => 12,
                         "gameLength" => 100 ) );

        $this->cards = self::getNew( "module.common.deck" );
        $this->cards->init( "card" );
	}
	
    protected function getGameName( )
    {
        return "egyptianratscrew";
    }	

    /*
        setupNewGame:
        
        This method is called 1 time when a new game is launched.
        In this method, you must setup the game according to game rules, in order
        the game is ready to be played.    
    
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        $sql = "DELETE FROM player WHERE 1 ";
        self::DbQuery( $sql ); 
 
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/yellow
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $default_color = array( "ff0000", "008000", "0000ff", "ffa500" );

        $start_points = self::getGameStateValue( 'gameLength' ) == 1 ? 75 : 100;

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialized it there.
        $sql = "INSERT INTO player (player_id, player_score, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_color );
            $values[] = "('".$player_id."','$start_points','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/
        // Init global values with their initial values

        // Note: hand types: 0 = give 3 cards to player on the left
        //                   1 = give 3 cards to player on the right
        //                   2 = give 3 cards to player on tthe front
        //                   3 = keep cards
        self::setGameStateInitialValue( 'currentHandType', 0 );
        
        // Set current trick color to zero (= no trick color)
        self::setGameStateInitialValue( 'trickColor', 0 );
        
        // Mark if we already played some heart during this hand
        self::setGameStateInitialValue( 'alreadyPlayedHearts', 0 );

        // Init game statistics
        // (note: statistics are defined in your stats.inc.php file)
        self::initStat( "table", "handNbr", 0 );
        self::initStat( "player", "getQueenOfSpade", 0 );
        self::initStat( "player", "getHearts", 0 );
        self::initStat( "player", "getAllPointCards", 0 );
        self::initStat( "player", "getNoPointCards", 0 );

        // Create cards
        $cards = array();
        foreach( $this->colors as  $color_id => $color ) // spade, heart, diamond, club
        {
            for( $value=2; $value<=14; $value++ )   //  2, 3, 4, ... K, A
            {
                $cards[] = array( 'type' => $color_id, 'type_arg' => $value, 'nbr' => 1);
            }
        }

        $this->cards->createCards( $cards, 'deck' );

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refresh the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array( 'players' => array() );

        $player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        // Get information about players
        // Note: you can retrieve some extra field you add for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score ";
        $sql .= "FROM player ";
        $sql .= "WHERE 1 ";
        $dbres = self::DbQuery( $sql );
        while( $player = mysql_fetch_assoc( $dbres ) )
        {
            $result['players'][ $player['id'] ] = $player;
        }
        
  
        // Cards in player hand      
        $result['hand'] = $this->cards->getCardsInLocation( 'hand', $player_id );
  
        // Cards played on the table
        $result['cardsontable'] = $this->cards->getCardsInLocation( 'cardsontable' );
  
        return $result;
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
        // Game progression: get player minimum score
        
        $minimumScore = self::getUniqueValueFromDb( "SELECT MIN( player_score ) FROM player" );
        
        return max( 0, min( 100, 100-$minimumScore ) );   // Note: 0 => 100
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        At this place, you can put any utility methods useful for your game logic
    */

    // Return players => direction (N/S/E/W) from the point of view
    //  of current player (current player must be on south)
    function getPlayersToDirection()
    {
        $result = array();
    
        $players = self::loadPlayersBasicInfos();
        $nextPlayer = self::createNextPlayerTable( array_keys( $players ) );

        $current_player = self::getCurrentPlayerId();
        
        $directions = array( 'S', 'W', 'N', 'E' );
        
        if( ! isset( $nextPlayer[ $current_player ] ) )
        {
            // Spectator mode: take any player for south
            $player_id = $nextPlayer[0];
            $result[ $player_id ] = array_shift( $directions );
        }
        else
        {
            // Normal mode: current player is on south
            $player_id = $current_player;
            $result[ $player_id ] = array_shift( $directions );
        }
        
        while( count( $directions ) > 0 )
        {
            $player_id = $nextPlayer[ $player_id ];
            $result[ $player_id ] = array_shift( $directions );
        }
        return $result;
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of this method below is called.
        (note: each method below correspond to an input method in egyptianratscrew.action.php)
    */


    // Play a card from player hand
    function playCard( $card_id )
    {
        self::checkAction( "playCard" );
        
        $player_id = self::getActivePlayerId();
        
        // Get all cards in player hand
        // (note: we must get ALL cards in player's hand in order to check if the card played is correct)
        
        $playerhands = $this->cards->getCardsInLocation( 'hand', $player_id );

        $bFirstCard = ( count( $playerhands ) == 13 );
                
        $currentTrickColor = self::getGameStateValue( 'trickColor' ) ;
                
        // Check that the card is in this hand
        $bIsInHand = false;
        $currentCard = null;
        $bAtLeastOneCardOfCurrentTrickColor = false;
        $bAtLeastOneCardWithoutPoints = false;
        $bAtLeastOneCardNotHeart = false;
        foreach( $playerhands as $card )
        {
            if( $card['id'] == $card_id )
            {
                $bIsInHand = true;
                $currentCard = $card;
            }
            
            if( $card['type'] == $currentTrickColor )
                $bAtLeastOneCardOfCurrentTrickColor = true;

            if( $card['type'] != 2 )
                $bAtLeastOneCardNotHeart = true;
                
            if( $card['type'] == 2 || ( $card['type'] == 1 && $card['type_arg'] == 12  ) )
            {
                // This is a card with point
            }
            else
                $bAtLeastOneCardWithoutPoints = true;
        }
        if( ! $bIsInHand )
            throw new feException( "This card is not in your hand" );
            
        if( $this->cards->countCardInLocation( 'hand' ) == 52 )
        {
            // If this is the first card of the hand, it must be 2-club
            // Note: first card of the hand <=> cards on hands == 52

            if( $currentCard['type'] != 3 || $currentCard['type_arg'] != 2 ) // Club 2
                throw new feException( self::_("You must play the Club-2"), true );                
        }
        else if( $currentTrickColor == 0 )
        {
            // Otherwise, if this is the first card of the trick, any cards can be played
            // except a Heart if:
            // _ no heart has been played, and
            // _ player has at least one non-heart
            if( self::getGameStateValue( 'alreadyPlayedHearts')==0
             && $currentCard['type'] == 2   // this is a heart
             && $bAtLeastOneCardNotHeart )
            {
                throw new feException( self::_("You can't play a heart to start the trick if no heart has been played before"), true );
            }
        }
        else
        {
            // The trick started before => we must check the color
            if( $bAtLeastOneCardOfCurrentTrickColor )
            {
                if( $currentCard['type'] != $currentTrickColor )
                    throw new feException( sprintf( self::_("You must play a %s"), $this->colors[ $currentTrickColor ]['nametr'] ), true );
            }
            else
            {
                // The player has no card of current trick color => he can plays what he want to
                
                if( $bFirstCard && $bAtLeastOneCardWithoutPoints )
                {
                    // ...except if it is the first card played by this player during this hand
                    // (it is forbidden to play card with points during the first trick)
                    // (note: if player has only cards with points, this does not apply)
                    
                    if( $currentCard['type'] == 2 || ( $currentCard['type'] == 1 && $currentCard['type_arg'] == 12  ) )
                    {
                        // This is a card with point                  
                        throw new feException( self::_("You can't play cards with points during the first trick"), true );
                    }
                }
            }
        }
        
        // Checks are done! now we can play our card
        $this->cards->moveCard( $card_id, 'cardsontable', $player_id );
        
        // Set the trick color if it hasn't been set yet
        if( $currentTrickColor == 0 )
            self::setGameStateValue( 'trickColor', $currentCard['type'] );
        
        if( $currentCard['type'] == 2 )
            self::setGameStateValue( 'alreadyPlayedHearts', 1 );
        
        // And notify
        self::notifyAllPlayers( 'playCard', clienttranslate('${player_name} plays ${value_displayed} ${color_displayed}'), array(
            'i18n' => array( 'color_displayed', 'value_displayed' ),
            'card_id' => $card_id,
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'value' => $currentCard['type_arg'],
            'value_displayed' => $this->values_label[ $currentCard['type_arg'] ],
            'color' => $currentCard['type'],
            'color_displayed' => $this->colors[ $currentCard['type'] ]['name']
        ) );
        
        // Next player
        $this->gamestate->nextState( 'playCard' );
    }
    
    // Give some cards (before the hands begin)
    function giveCards( $card_ids )
    {
        self::checkAction( "giveCards" );
        
        // !! Here we have to get CURRENT player (= player who send the request) and not
        //    active player, cause we are in a multiple active player state and the "active player"
        //    correspond to nothing.
        $player_id = self::getCurrentPlayerId();
        
        if( count( $card_ids ) != 3 )
            throw new feException( self::_("You must give exactly 3 cards") );
    
        // Check if these cards are in player hands
        $cards = $this->cards->getCards( $card_ids );
        
        if( count( $cards ) != 3 )
            throw new feException( self::_("Some of these cards don't exist") );
        
        foreach( $cards as $card )
        {
            if( $card['location'] != 'hand' || $card['location_arg'] != $player_id )
                throw new feException( self::_("Some of these cards are not in your hand") );
        }
        
        // To which player should I give these cards ?
        $player_to_give_cards = null;
        $player_to_direction = self::getPlayersToDirection();   // Note: current player is on the south
        $handType = self::getGameStateValue( "currentHandType" );
        if( $handType == 0 )
            $direction = 'W';
        else if( $handType == 1 )
            $direction = 'N';
        else if( $handType == 2 )
            $direction = 'E';
        foreach( $player_to_direction as $opponent_id => $opponent_direction )
        {
            if( $opponent_direction == $direction )
                $player_to_give_cards = $opponent_id;
        }
        if( $player_to_give_cards === null )
            throw new feException( self::_("Error while determining to who give the cards") );
        
        // Allright, these cards can be given to this player
        // (note: we place the cards in some temporary location in order he can't see them before the hand starts)
        $this->cards->moveCards( $card_ids, "temporary", $player_to_give_cards );

        // Notify the player so we can make these cards disapear
        self::notifyPlayer( $player_id, "giveCards", "", array(
            "cards" => $card_ids
        ) );

        // Make this player unactive now
        // (and tell the machine state to use transtion "giveCards" if all players are now unactive
        $this->gamestate->setPlayerNonMultiactive( $player_id, "giveCards" );
    }
    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defines as "game state arguments" (see "args" property in states.inc.php).
        These methods are returning some additional informations that are specific to the current
        game state.
    */

    function argGiveCards()
    {
        $handType = self::getGameStateValue( "currentHandType" );
        $direction = "";
        if( $handType == 0 )
            $direction = clienttranslate( "the player on the left" );
        else if( $handType == 1 )
            $direction = clienttranslate( "the player accros the table" );
        else if( $handType == 2 )
            $direction = clienttranslate( "the player on the right" );

        return array(
            "i18n" => array( 'direction'),
            "direction" => $direction
        );
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defines as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */

    function stNewHand()
    {
        self::incStat( 1, "handNbr" );
    
        // Take back all cards (from any location => null) to deck
        $this->cards->moveAllCardsInLocation( null, "deck" );
        $this->cards->shuffle( 'deck' );
    
        // Deal 13 cards to each players
        // Create deck, shuffle it and give 13 initial cards
        $players = self::loadPlayersBasicInfos();
        foreach( $players as $player_id => $player )
        {
            $cards = $this->cards->pickCards( 13, 'deck', $player_id );
            
            // Notify player about his cards
            self::notifyPlayer( $player_id, 'newHand', '', array( 
                'cards' => $cards
            ) );
        }        
        
        self::setGameStateValue( 'alreadyPlayedHearts', 0 );

        $this->gamestate->nextState( "" );
    }

    function stGiveCards()
    {
        $handType = self::getGameStateValue( "currentHandType" );
        
        // If we are in hand type "3" = "keep cards", skip this step
        if( $handType == 3 )
        {
            $this->gamestate->nextState( "skip" );
        }
        else
        {
            // Active all players (everyone has to choose 3 cards to give)
            $this->gamestate->setAllPlayersMultiactive();
        }
    }
    function stTakeCards()
    {
        // Take cards given by the other player
        
        $players = self::loadPlayersBasicInfos();
        foreach( $players as $player_id => $player )
        {
            // Each player takes cards in the "temporary" location and place it in his hand
            $cards = $this->cards->getCardsInLocation( "temporary", $player_id );
            $this->cards->moveAllCardsInLocation( "temporary", "hand", $player_id, $player_id );
            
            self::notifyPlayer( $player_id, "takeCards", "", array(
                "cards" => $cards
            ) );
        }
        
        // Note: club=4
        $twoClubCardOwner = self::getUniqueValueFromDb( "SELECT card_location_arg FROM card
                                                         WHERE card_location='hand'
                                                         AND card_type='3' AND card_type_arg='2' " );
        if( $twoClubCardOwner !== null )
        {
            $this->gamestate->changeActivePlayer( $twoClubCardOwner );
        }
        else
        {
            throw new feException( self::_("Cant find Club-2") );
        }        
        
        $this->gamestate->nextState( "startHand" );  // For now
    }
    function stNewTrick()
    {
        // New trick: active the player who wins the last trick, or the player who own the club-2 card

        // Reset trick color to 0 (= no color)
        self::setGameStateInitialValue( 'trickColor', 0 );
        
        
        $this->gamestate->nextState();

    }
    function stNextPlayer()
    {
        // Active next player OR end the trick and go to the next trick OR end the hand
        
        if( $this->cards->countCardInLocation( 'cardsontable' ) == 4 )
        {
            // This is the end of the trick
            // Who wins ?
            
            $cards_on_table = $this->cards->getCardsInLocation( 'cardsontable' );
            $best_value = 0;
            $best_value_player_id = null;
            $currentTrickColor = self::getGameStateValue( 'trickColor' );
            
            foreach( $cards_on_table as $card )
            {
                if( $card['type'] == $currentTrickColor )   // Note: type = card color
                {
                    if( $best_value_player_id === null )
                    {
                        $best_value_player_id = $card['location_arg'];  // Note: location_arg = player who played this card on table
                        $best_value = $card['type_arg'];        // Note: type_arg = value of the card
                    }
                    else if( $card['type_arg'] > $best_value )
                    {
                        $best_value_player_id = $card['location_arg'];  // Note: location_arg = player who played this card on table
                        $best_value = $card['type_arg'];        // Note: type_arg = value of the card
                    }
                }
            }
            
            if( $best_value_player_id === null )
                throw new feException( self::_("Error, nobody wins the trick") );
            
            // Move all cards to "cardswon" of the given player
            $this->cards->moveAllCardsInLocation( 'cardsontable', 'cardswon', null, $best_value_player_id );

            // Notify
            // Note: we use 2 notifications here in order we can pause the display during the first notification
            //  before we move all cards to the winner (during the second)
            $players = self::loadPlayersBasicInfos();
            self::notifyAllPlayers( 'trickWin', clienttranslate('${player_name} wins the trick'), array(
                'player_id' => $best_value_player_id,
                'player_name' => $players[ $best_value_player_id ]['player_name']
            ) );            
            self::notifyAllPlayers( 'giveAllCardsToPlayer','', array(
                'player_id' => $best_value_player_id
            ) );

            // Active this player => he's the one who starts the next trick
            $this->gamestate->changeActivePlayer( $best_value_player_id );
            
            if( $this->cards->countCardInLocation( 'hand' ) == 0 )
            {
                // End of the hand
                $this->gamestate->nextState( "endHand" );
            }
            else
            {
                // End of the trick
                $this->gamestate->nextState( "nextTrick" );
            }
        }
        else
        {
            // Standard case (not the end of the trick)
            // => just active the next player
        
            $player_id = self::activeNextPlayer();
            self::giveExtraTime( $player_id );

            $this->gamestate->nextState( 'nextPlayer' );        
        }
    }
    function stEndHand()
    {
        // Count and score points, then end the game or go to the next hand.
                
        $players = self::loadPlayersBasicInfos();
        
        // Gets all "egyptianratscrew" + queen of spades
        $player_with_queen_of_spades = null;
        $player_to_egyptianratscrew = array();
        $player_to_points = array();
        foreach( $players as $player_id => $player )
        {
            $player_to_egyptianratscrew[ $player_id ] = 0;
            $player_to_points[ $player_id ] = 0;
        }   
        
        $cards = $this->cards->getCardsInLocation( "cardswon" );
        foreach( $cards as $card )
        {
            $player_id = $card['location_arg'];
            
            if( $card['type'] == 1 && $card['type_arg'] == 12 )    // Note: 1 = spade && 12 = queen
            {
                // Queen of club => 13 points
                $player_to_points[ $player_id ] += 13;
                $player_with_queen_of_spades = $player_id;
            }
            else if( $card['type'] == 2 )   // Note: 2 = heart
            {
                $player_to_egyptianratscrew[ $player_id ] ++;                    
                $player_to_points[ $player_id ] ++; 
            }
        }
        
        // If someone gets all egyptianratscrew and the queen of club => 26 points for eveyone
        $nbr_nonzero_score = 0;
        foreach( $player_to_points as $player_id => $points )
        {
            if( $points != 0 )
                $nbr_nonzero_score ++;
        }

        $bOnePlayerGetsAll = ( $nbr_nonzero_score == 1 );

        if( $bOnePlayerGetsAll )
        {
            // Only 1 player score points during this hand
            // => he score 0 and everyone scores -26
            foreach( $player_to_egyptianratscrew as $player_id => $points )
            {
                if( $points != 0 )
                {
                    $player_to_points[ $player_id ] = 0;

                    // Notify it!
                    self::notifyAllPlayers( "onePlayerGetsAll", clienttranslate( '${player_name} gets all egyptianratscrew and the Queen of Spades: everyone else loose 26 points!' ), array(
                        'player_id' => $player_id,
                        'player_name' => $players[ $player_id ]['player_name']
                    ) );
                    
                    self::incStat( 1, "getAllPointCards", $player_id );
                }
                else
                    $player_to_points[ $player_id ] = 26;
            }                
        }
        
        // Apply scores to player
        foreach( $player_to_points as $player_id => $points )
        {
            if( $points != 0 )
            {
                $sql = "UPDATE player SET player_score=player_score-$points
                        WHERE player_id='$player_id' " ;
                self::DbQuery( $sql );

                // Now, notify about the point lost.                
                if( ! $bOnePlayerGetsAll )  // Note: if one player gets all, we already notify everyone so there's no need to send additional notifications
                {
                    $heart_number = $player_to_egyptianratscrew[ $player_id ];
                    if( $player_id == $player_with_queen_of_spades )
                    {
                        self::notifyAllPlayers( "points", clienttranslate( '${player_name} gets ${nbr} egyptianratscrew and the Queen of Spades and looses ${points} points' ), array(
                            'player_id' => $player_id,
                            'player_name' => $players[ $player_id ]['player_name'],
                            'nbr' => $heart_number,
                            'points' => $points
                        ) );
                    }
                    else
                    {
                        self::notifyAllPlayers( "points", clienttranslate( '${player_name} gets ${nbr} egyptianratscrew and looses ${nbr} points' ), array(
                            'player_id' => $player_id,
                            'player_name' => $players[ $player_id ]['player_name'],
                            'nbr' => $heart_number
                        ) );
                    }
                }
            }
            else
            {
                // No point lost (just notify)
                self::notifyAllPlayers( "points", clienttranslate( '${player_name} did not get any egyptianratscrew' ), array(
                    'player_id' => $player_id,
                    'player_name' => $players[ $player_id ]['player_name']
                ) );
                
                self::incStat( 1, "getNoPointCards", $player_id );
            }
        }

        $newScores = self::getCollectionFromDb( "SELECT player_id, player_score FROM player", true );
        self::notifyAllPlayers( "newScores", '', array( 'newScores' => $newScores ) );
        
        //////////// Display table window with results /////////////////
        $table = array();

        // Header line
        $firstRow = array( '' );
        foreach( $players as $player_id => $player )
        {
            $firstRow[] = array( 'str' => '${player_name}',
                                 'args' => array( 'player_name' => $player['player_name'] ),
                                 'type' => 'header'
                               );
        }
        $table[] = $firstRow;

        // Hearts
        $newRow = array( array( 'str' => clienttranslate('Hearts'), 'args' => array() ) );
        foreach( $player_to_egyptianratscrew as $player_id => $egyptianratscrew )
        {
            $newRow[] = $egyptianratscrew;
            
            if( $egyptianratscrew > 0 )
                self::incStat( $egyptianratscrew, "getHearts", $player_id );
        }
        $table[] = $newRow;

        // Queen of spades
        $newRow = array( array( 'str' => clienttranslate('Queen of Spades'), 'args' => array() ) );
        foreach( $player_to_egyptianratscrew as $player_id => $egyptianratscrew )
        {
            if( $player_id == $player_with_queen_of_spades )
            {
                $newRow[] = '1';
                self::incStat( 1, "getQueenOfSpade", $player_id );
            }
            else
                $newRow[] = '0';
        }
        $table[] = $newRow;

        // Points
        $newRow = array( array( 'str' => clienttranslate('Points'), 'args' => array() ) );
        foreach( $player_to_points as $player_id => $points )
        {
            $newRow[] = $points;
        }
        $table[] = $newRow;

        
        $this->notifyAllPlayers( "tableWindow", '', array(
            "id" => 'finalScoring',
            "title" => clienttranslate("Result of this hand"),
            "table" => $table
        ) ); 
        
        // Change the "type" of the next hand
        $handType = self::getGameStateValue( "currentHandType" );
        self::setGameStateValue( "currentHandType", ($handType+1)%4 );
        
        ///// Test if this is the end of the game
        foreach( $newScores as $player_id => $score )
        {
            if( $score <= 0 )
            {
                // Trigger the end of the game !
                $this->gamestate->nextState( "endGame" );
                return ;
            }
        }

        // Otherwise... new hand !
        $this->gamestate->nextState( "nextHand" );
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

    function zombieTurn( $state, $active_player )
    {
        // Note: zombie mode has not be realized for Hearts, as it is an example game and
        //       that it can be complex to choose a right card to play.
        throw new feException( "Zombie mode not supported for Hearts" );
    }
   
   
}
  

