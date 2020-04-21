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
 * material.inc.php
 *
 * Hearts game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */


$this->colors = array(
    1 => array( 'name' => "\u{2660}",
                'nametr' => "\u{2660}"),
    2 => array( 'name' => "\u{2665}",
                'nametr' => "\u{2665}"),
    3 => array( 'name' => "\u{2663}",
                'nametr' => "\u{2663}"),
    4 => array( 'name' => "\u{2666}",
                'nametr' => "\u{2666}")
);

$this->values_label = array(
    2 =>'2',
    3 => '3',
    4 => '4',
    5 => '5',
    6 => '6',
    7 => '7',
    8 => '8',
    9 => '9',
    10 => '10',
    11 => clienttranslate('J'),
    12 => clienttranslate('Q'),
    13 => clienttranslate('K'),
    14 => clienttranslate('A')
);

$this->challenge_values = array(
    11 => 1,
    12 => 2,
    13 => 3,
    14 => 4
);
