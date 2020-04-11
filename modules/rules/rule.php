<?php

interface Rule
{
    public function getName();
    public function getDescription();
    public function isSatisfied($cardsStack);
}