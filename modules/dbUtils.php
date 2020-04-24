<?php

class DbUtils
{
    public static function getCards($location, $location_arg = null, $order = "ASC")
    {
        $cards = array();

        $query =
            "SELECT card_id id, card_type type, card_type_arg type_arg, card_location location, card_location_arg location_arg, hidden, play_time " .
            "FROM card " .
            "WHERE card_location = '" . $location . "' ";

        if ($location_arg != null) {
            $query .= "AND card_location_arg = '" . $location_arg . "' ";
        }

        if ($order != null) {
            $query .= "ORDER BY play_time " . $order;
        }

        $result = Table::DbQuery($query);
        while ($card = mysqli_fetch_assoc($result)) {
            $cards[$card['id']] = $card;
        }

        return $cards;
    }

    public static function getPlayersState()
    {
        $players = array();

        $query =
            "SELECT player_id id, player_score score, eliminated, penalty " .
            "FROM player " .
            "WHERE 1";

        $result = Table::DbQuery($query);
        while ($player = mysqli_fetch_assoc($result)) {
            $players[$player['id']] = $player;
            // Players only know their number of cards in hand
            $players[$player['id']]['cards'] = DbUtils::countPlayerCards($player['id']);
        }

        return $players;
    }

    public static function countPlayerCards($player_id)
    {
        $query = "SELECT COUNT(*) cnt FROM card WHERE card_location = 'hand' AND card_location_arg = '" . $player_id . "'";
        $result = Table::DbQuery($query);
        return mysqli_fetch_assoc($result)['cnt'];
    }

    public static function cleanPlayerTable()
    {
        Table::DbQuery("DELETE FROM player WHERE 1");
    }

    public static function createPlayers($players, $colors)
    {
        $sql = "INSERT INTO player (player_id, player_score, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach ($players as $player_id => $player) {
            $color = array_shift($colors);
            $values[] = "('" . $player_id . "','0','$color','" . $player['player_canal'] . "','" . addslashes($player['player_name']) . "','" . addslashes($player['player_avatar']) . "')";
        }
        $sql .= implode(',', $values);
        Table::DbQuery($sql);
    }

    public static function updateCardPlayTime($card_id, $timestamp)
    {
        Table::DbQuery("UPDATE card SET play_time=".$timestamp." WHERE card_id='$card_id'");
    }

    public static function initCardsPlayTime($timestamp)
    {
        Table::DbQuery("UPDATE card SET play_time=".$timestamp." WHERE 1");
    }

    public static function incrementScore($player_id)
    {
        Table::DbQuery("UPDATE player SET player_score=player_score+1 WHERE player_id='$player_id'");
    }
}
