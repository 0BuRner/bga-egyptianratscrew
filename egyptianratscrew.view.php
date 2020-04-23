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
 * egyptianratscrew.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in emptygame_emptygame.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */

require_once(APP_BASE_PATH . "view/common/game.view.php");

class view_egyptianratscrew_egyptianratscrew extends game_view
{
    function getGameName()
    {
        return "egyptianratscrew";
    }

    function build_page($viewArgs)
    {
        // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count($players);

        /*********** Place your code below:  ************/
        // TODO: Arrange players so that I am on south

        $this->page->begin_block("egyptianratscrew_egyptianratscrew", "player");
        $i = 0;
        foreach (array_values($players) as $idx => $player) {
            $player_id = $player['player_id'];
            $this->page->insert_block("player",
                array(
                    "PLAYER_ID" => $player_id,
                    "PLAYER_NAME" => $players[$player_id]['player_name'],
                    "PLAYER_COLOR" => $players[$player_id]['player_color'],
                    "ROTATE_ANGLE" => (360 / $players_nbr) * $i,
                    "HAND_ROTATE_ANGLE" => ((360 / $players_nbr) * $i) + 180,
                    "SEAT_WIDTH" => 600 / $players_nbr + 50,
                )
            );
            $i++;
        }

        $this->tpl['MY_HAND'] = self::_("My hand");

        /*********** Do not change anything below this line  ************/
    }
}
  

