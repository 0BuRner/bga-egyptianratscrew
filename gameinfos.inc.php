<?php

$gameinfos = array(

// Name of the game in English (will serve as the basis for translation)
'game_name' => 'Egyptian Ratscrew',

// Game designer (or game designers, separated by commas)
'designer' => '',

// Game artist (or game artists, separated by commas)
'artist' => '',         

// Year of FIRST publication of this game. Can be negative.
'year' => 0,                 

// Game publisher
'publisher' => '',                     

// Url of game publisher website
'publisher_website' => '',   

// Board Game Geek ID of the publisher
'publisher_bgg_id' => 171,

// Board Game Geek ID of the game
'bgg_id' => 15712,


// Players configuration that can be played (ex: 2 to 4 players)
'players' => array(2, 3, 4, 5, 6, 7, 8, 9, 10),

// Suggest players to play with this number of players. Must be null if there is no such advice, or if there is only one possible player configuration.
'suggest_player_number' => 4,

// Discourage players to play with this number of players. Must be null if there is no such advice.
'not_recommend_player_number' => array(),



// Estimated game duration, in minutes (used only for the launch, afterward the real duration is computed)
'estimated_duration' => 30,

// Time in second add to a player when "giveExtraTime" is called (speed profile = fast)
'fast_additional_time' => 5,

// Time in second add to a player when "giveExtraTime" is called (speed profile = medium)
'medium_additional_time' => 10,

// Time in second add to a player when "giveExtraTime" is called (speed profile = slow)
'slow_additional_time' => 15,


// Game is "beta". A game MUST set is_beta=1 when published on BGA for the first time, and must remains like this until all bugs are fixed.
'is_beta' => 0,                     

// Is this game cooperative (all players wins together or loose together)
'is_coop' => 0,

// If in the game, all losers are equal (no score to rank them or explicit in the rules that losers are not ranked between them), set this to true
// The game end result will display "Winner" for the 1st player and "Loser" for all other players
'losers_not_ranked' => true,

// Complexity of the game, from 0 (extremely simple) to 5 (extremely complex)
'complexity' => 1,

// Luck of the game, from 0 (absolutely no luck in this game) to 5 (totally luck driven)
'luck' => 2,

// Strategy of the game, from 0 (no strategy can be setup) to 5 (totally based on strategy)
'strategy' => 1,

// Diplomacy of the game, from 0 (no interaction in this game) to 5 (totally based on interaction and discussion between players)
'diplomacy' => 0,


// Games categories
//  You can attribute any number of "tags" to your game.
//  Each tag has a specific ID (ex: 22 for the category "Prototype", 101 for the tag "Science-fiction theme game")
'tags' => array( 1 )
);
