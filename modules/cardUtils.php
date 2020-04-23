<?php

class CardHelper
{
    static $ORDER = array("BOTTOM" => 0, "TOP" => 1);

    /**
     * @param $cards array the cards stack
     * @return mixed the cards stack order by playing time
     */
    public static function sortCards($cards)
    {
        usort($cards, 'CardHelper::comparator');
        return $cards;
    }

    /**
     * @param $cards array the cards stack
     * @param $nbr int the number of cards to extract
     * @param $order int the order to search the card from (default is from top of the stack)
     * @return mixed card data from the cards stack
     */
    public static function getCards($cards, $nbr, $order = 1)
    {
        $cards = self::sortCards($cards);
        if ($order == self::$ORDER['BOTTOM']) {
            return array_slice($cards, $nbr, -$nbr, true);
        } else {
            return array_slice($cards, 0, $nbr, true);
        }
    }

    /**
     * @param $cards array the cards stack
     * @param $index int the index in array of cards
     * @param $order int the order to search the card from (default is from top of the stack)
     * @return mixed card data from the cards stack
     */
    public static function getCardAt($cards, $index, $order = 1)
    {
        $cards = self::sortCards($cards);
        if ($order == self::$ORDER['TOP']) {
            return array_values($cards)[count($cards) - 1 - $index];
        } else {
            return array_values($cards)[$index];
        }
    }

    static function comparator($card1, $card2)
    {
        return $card1['location_arg'] > $card2['location_arg'];
    }
}
