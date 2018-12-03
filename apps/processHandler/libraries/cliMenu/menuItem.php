<?php

namespace mpcmf\apps\processHandler\libraries\cliMenu;

class menuItem
{
    protected $key;
    protected $value;
    protected $title;
    protected $selected = false;
    protected $enabled = true;


    public function __construct($key, $value, $title)
    {
        $this->key = $key;
        $this->value = $value;
        $this->title = $title;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setKey($key)
    {
        $this->key = $key;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @param $selected bool
     */
    public function setSelected($selected)
    {
        $this->selected = $selected;
    }

    /**
     * @return bool
     */
    public function isSelected()
    {
        return $this->selected;
    }

    public function toggleSelected()
    {
        $this->selected = $this->selected ? false : true;
    }

    public function disable()
    {
        $this->enabled = false;
    }

    public function enable()
    {
        $this->enabled = true;
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

}