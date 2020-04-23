<?php

class ValueRule implements Rule
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getName()
    {
        return "Value";
    }

    public function getDescription()
    {
        return "A card of the specified value";
    }

    public function isSatisfied($cardsStack)
    {
        if (count($cardsStack) < 1) {
            return false;
        }

        $c0 = CardHelper::getCardAt($cardsStack, 0);
        return intval($c0['type_arg']) == $this->value;
    }
}
