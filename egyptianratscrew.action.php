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
 * egyptianratscrew.action.php
 *
 * Egyptian Ratscrew main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/emptygame/emptygame/myAction.html", ...)
 *
 */

class action_egyptianratscrew extends APP_GameAction
{
    public function __default()
    {
        if (self::isArg('notifwindow')) {
            $this->view = "common_notifwindow";
            $this->viewArgs['table'] = self::getArg("table", AT_posint, true);
        } else {
            $this->view = "egyptianratscrew_egyptianratscrew";
            self::trace("Complete reinitialization of board game");
        }
    }

    public function playCard()
    {
        self::setAjaxMode();
        $this->game->playCard();
        self::ajaxResponse();
    }

    // TODO implement JS
    public function slapPile()
    {
        self::setAjaxMode();
        $this->game->slapPile();
        self::ajaxResponse();
    }
}
  

