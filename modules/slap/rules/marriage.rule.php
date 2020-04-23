<?php

class MarriageRule implements Rule
{
    public function getName()
    {
        return "Marriage";
    }

    public function getDescription()
    {
        return "A king and a queen of the same color follow each other";
    }

    public function isSatisfied($cardsStack)
    {
        if (count($cardsStack) < 2) {
            return false;
        }

        $c0 = CardHelper::getCardAt($cardsStack, 0);
        $c1 = CardHelper::getCardAt($cardsStack, 1);

        $c0_isQueenOrKing = $c0['type_arg'] == 12 || $c0['type_arg'] == 13;
        $c1_isQueenOrKing = $c1['type_arg'] == 12 || $c1['type_arg'] == 13;
        $sameColor = $c0['type'] == $c1['type'];

        return $sameColor && $c0['type_arg'] != $c1['type_arg'] && $c0_isQueenOrKing && $c1_isQueenOrKing;
    }
}
