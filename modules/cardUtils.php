<?php

class CardHelper
{
    static $ORDER = array("BOTTOM" => 0, "TOP" => 1);

    public static function getCards($cards, $nbr, $order)
    {
        return array_slice($cards, $nbr, -$nbr, true);
    }

    /**
     * @param $cards array the cards stack
     * @param $index int the index in array of cards
     * @param $order int the order to search the card from (default is from top of the stack)
     * @return mixed card data from the cards stack
     */
    public static function getCardAt($cards, $index, $order = 1)
    {
        if ($order == CardHelper::$ORDER['BOTTOM']) {
            return array_values($cards)[count($cards) - 1 - $index];
        } else {
            return array_values($cards)[$index];
        }
    }
}
