<?php

require_once('rule.php');
require_once('rules/sandwich.rule.php');
require_once('rules/bigmac.rule.php');
require_once('rules/double.rule.php');
require_once('rules/value.rule.php');
require_once('rules/addition.rule.php');
require_once('rules/marriage.rule.php');

class SlapValidator {

    private $rules = array();

    /**
     * slapValidator constructor.
     */
    public function __construct()
    {
        $this->rules[] = new SandwichRule();
        $this->rules[] = new BigMacRule();
        $this->rules[] = new DoubleRule();
        $this->rules[] = new ValueRule(10);
        $this->rules[] = new AdditionRule(10);
        $this->rules[] = new MarriageRule();
    }

    public function isValid($cardsStack)
    {
        if (count($cardsStack) == 0) {
            return false;
        }

        $isValid = false;
        foreach ($this->rules as $rule) {
            $isValid &= $rule->isSatisfied($cardsStack);
        }
        return $isValid;
    }
}
