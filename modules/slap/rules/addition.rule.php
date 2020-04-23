<?php

class AdditionRule implements Rule
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getName()
    {
        return "Addition";
    }

    public function getDescription()
    {
        return "Sum of the last two cards is " . $this->value;
    }

    public function isSatisfied($cardsStack)
    {
        if (count($cardsStack) < 2) {
            return false;
        }

        $c0 = CardHelper::getCardAt($cardsStack, 0);
        $c1 = CardHelper::getCardAt($cardsStack, 1);
        return (intval($c0['type_arg']) + intval($c1['type_arg'])) == $this->value;
    }
}
