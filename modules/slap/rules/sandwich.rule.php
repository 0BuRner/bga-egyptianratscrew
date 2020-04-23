<?php

class SandwichRule implements Rule
{
    public function getName()
    {
        return "Sandwich";
    }

    public function getDescription()
    {
        return "A pair but with an interval card";
    }

    public function isSatisfied($cardsStack)
    {
        if (count($cardsStack) < 3) {
            return false;
        }

        $c0 = CardHelper::getCardAt($cardsStack, 0);
        $c2 = CardHelper::getCardAt($cardsStack, 2);
        return $c0['type_arg'] == $c2['type_arg'];
    }
}
