<?php

namespace mpcmf\apps\processHandler\libraries\cliMenu;

abstract class controlItem
{
    protected $keyboardEventNumber;
    protected $buttonName;
    protected $title;

    public function __construct($keyboardEventNumber, $buttonName, $title)
    {
        $this->keyboardEventNumber = $keyboardEventNumber;
        $this->buttonName = $buttonName;
        $this->title = $title;
    }

    abstract public function execute(menu $menu);

    public function getKeyboardEventNumber()
    {
        return $this->keyboardEventNumber;
    }

    public function setKeyboardEventNumber($keyboardEventNumber)
    {
        return $this->keyboardEventNumber = $keyboardEventNumber;
    }

    public function getButtonName()
    {
        return $this->buttonName;
    }
    public function setButtonName($buttonName)
    {
        return $this->buttonName = $buttonName;
    }
    public function getTitle()
    {
        return $this->title;
    }
    public function setTitle($title)
    {
        return $this->title = $title;
    }
}