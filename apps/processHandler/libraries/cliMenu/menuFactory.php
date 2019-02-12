<?php

namespace mpcmf\apps\processHandler\libraries\cliMenu;

class menuFactory
{
    public static function getMenu($filterType = '')
    {
        return new menu(new sorting(), new filter($filterType));
    }
}