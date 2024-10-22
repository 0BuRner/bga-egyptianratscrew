{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- Egyptian Ratscrew implementation : 0BuRner
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    egyptianratscrew_egyptianratscrew.tpl
    
    This is the HTML template of your game.
    
    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.
    
    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format
    
    See your "view" PHP file to check how to set variables and control blocks
-->

<div id="wrapper">
    <div id="game_table">
        <div id="pile"></div>
        <!-- BEGIN player -->
        <div id="player_seat_{PLAYER_ID}" class="player_seat" style="transform:rotate({ROTATE_ANGLE}deg) translate(0, -300px) rotate(-{ROTATE_ANGLE}deg);">
            <div id="player_hand_{PLAYER_ID}" class="player_hand">
                <div style="transform: rotate({HAND_ROTATE_ANGLE}deg);"></div>
            </div>
            <div class="player_name" style="color:#{PLAYER_COLOR}; border-bottom:5px solid #{PLAYER_COLOR};">
                {PLAYER_NAME}
            </div>
            <div id="player_cards_{PLAYER_ID}" class="player_cards"></div>
        </div>
        <!-- END player -->
    </div>
</div>

{OVERALL_GAME_FOOTER}
