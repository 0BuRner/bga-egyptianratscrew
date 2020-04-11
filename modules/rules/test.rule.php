<?php

class TestRule implements Rule {
    public function getName()
    {
        return "Test";
    }

    public function getDescription()
    {
        return "Test rule";
    }

    public function isSatisfied($cardsStack) {
        return false;
    }
}
