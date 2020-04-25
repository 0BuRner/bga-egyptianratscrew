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
 * stats.inc.php
 *
 * Hearts game statistics description
 *
 */

/*
    In this file, you are describing game statistics, that will be displayed at the end of the
    game.
    
    There are 2 types of statistics:
    _ table statistics, that are not associated to a specific player (ie: 1 value for each game).
    _ player statistics, that are associated to each players (ie: 1 value for each player in the game).

    Statistics types can be "int" for integer, and "float" for floating point values.
    
    Once you defined your statistics there, you can start using "initStat", "setStat" and "incStat" method
    in your game logic, using statistics names defined below.
*/

//   !! It is not a good idea to modify this file when a game is running !!


$stats_type = array(

    // Statistics global to table
    "table" => array(
        "pileSlapOk" => array("id" => 10,
            "name" => totranslate("Number of slap success"),
            "type" => "int"),
        "pileSlapMissed" => array("id" => 11,
            "name" => totranslate("Number of slap missed"),
            "type" => "int")
    ),

    // Statistics existing for each player
    "player" => array(
        "challengeWon" => array("id" => 10,
            "name" => totranslate("Number of challenge won"),
            "type" => "int"),
        "challengeLost" => array("id" => 11,
            "name" => totranslate("Number of challenge lost"),
            "type" => "int"),
        "pileSlapWon" => array("id" => 12,
            "name" => totranslate("Number of times you won by slapping the pile"),
            "type" => "int"),
        "pileSlapLost" => array("id" => 13,
            "name" => totranslate("Number of times you were not the fastest to slap the pile"),
            "type" => "int"),
        "pileSlapFailed" => array("id" => 14,
            "name" => totranslate("Number of times you wrongly slapped the pile"),
            "type" => "int"),
        "playerEliminated" => array("id" => 15,
            "name" => totranslate("Number of players eliminated"),
            "type" => "int"),
        "playCardFailed" => array("id" => 16,
            "name" => totranslate("Number of times you played a card not in your turn"),
            "type" => "int")
    )
);


