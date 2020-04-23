<?php

class BigMacRule implements Rule
{
    public function getName()
    {
        return "Big-Mac";
    }

    public function getDescription()
    {
        return "A pair but with two cards in between";
    }

    public function isSatisfied($cardsStack)
    {
        if (count($cardsStack) < 4) {
            return false;
        }

        $c0 = CardHelper::getCardAt($cardsStack, 0);
        $c3 = CardHelper::getCardAt($cardsStack, 3);
        return $c0['type_arg'] == $c3['type_arg'];
    }
}
