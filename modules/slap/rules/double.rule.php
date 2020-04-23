<?php

class DoubleRule implements Rule
{
    public function getName()
    {
        return "Double";
    }

    public function getDescription()
    {
        return "A pair of cards of the same value";
    }

    public function isSatisfied($cardsStack)
    {
        if (count($cardsStack) < 2) {
            return false;
        }

        $c0 = CardHelper::getCardAt($cardsStack, 0);
        $c1 = CardHelper::getCardAt($cardsStack, 1);
        return $c0['type_arg'] == $c1['type_arg'];
    }
}
