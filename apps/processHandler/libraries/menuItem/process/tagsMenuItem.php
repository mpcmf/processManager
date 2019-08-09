<?php

namespace mpcmf\apps\processHandler\libraries\menuItem\process;

use mpcmf\apps\processHandler\libraries\cliMenu\helper;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;
use mpcmf\apps\processHandler\libraries\menuItem\arrayEditableMenuItem;

/**
 * @author Gadel Raymanov <raymanovg@gmail.com>
 */
class tagsMenuItem extends menuItem implements arrayEditableMenuItem
{
    public function __construct(array $tags, $isVisible = true)
    {
        $menuItems = [];
        foreach ($tags as $tag) {
            $menuItems[] = new menuItem($tag, $tag, $tag, $isVisible);
        }

        parent::__construct('tags', $menuItems, helper::formTitle('tags', $tags), $isVisible);
    }

    public function export()
    {
        $result = [];
        /** @var menuItem $menuItem */
        foreach ($this->value as $menuItem) {
            $result[] = $menuItem->export();
        }

        return$result;
    }
}
