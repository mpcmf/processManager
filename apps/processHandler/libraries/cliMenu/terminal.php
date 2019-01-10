<?php

namespace mpcmf\apps\processHandler\libraries\cliMenu;

class terminal
{
    const KEY_UP = 65;
    const KEY_DOWN = 66;
    const KEY_LEFT = 68;
    const KEY_RIGHT = 67;
    const KEY_SPACE = 32;
    const KEY_F2 = 81;
    const KEY_F3 = 82;
    const KEY_F4 = 83;
    const KEY_F5 = 53;
    const KEY_F6 = 55;
    const KEY_F7 = 56;
    const KEY_F8 = 57;
    const KEY_F9 = 48;
    const KEY_F10 = 49;
    const KEY_F12 = 52;
    const KEY_ENTER = 10;
    const KEY_DELETE = 51;
    const KEY_INSERT = 126;
    const KEY_SLASH = 47;
    const KEY_UNKNOWN = 0;

    /**
     * @var int
     */
    private $width;

    /**
     * @var int
     */
    private $height;


    public function getInput()
    {
        shell_exec('stty -icanon');

        $stdin = fopen('php://stdin', 'r');

        $key = ord(fgetc($stdin));

        if (27 === $key) {
            fgetc($stdin);
            $key = ord(fgetc($stdin));
            if ($key === 49 || $key === 50) {
                $key = ord(fgetc($stdin));
            }
        }

        switch ($key) {
            case 65:
                return self::KEY_UP;
                break;
            case 66:
                return self::KEY_DOWN;
                break;
            case 68:
                return self::KEY_LEFT;
                break;
            case 67:
                return self::KEY_RIGHT;
                break;
            case 32:
                return self::KEY_SPACE;
                break;
            case 50:
                return self::KEY_F9;
            case 83:
                return self::KEY_F4;
            case 10:
                return self::KEY_ENTER;
                break;
            case 0:
            case ord(''):
                return self::KEY_UNKNOWN;
                break;
        }
        return $key;

    }


    /**
     * Move the cursor to the top left of the window
     *
     * @return void
     */
    public function moveCursorToTop()
    {
        echo "\033[H";
    }

    /**
     * Get the available width of the terminal
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->width ?: $this->width = (int) exec('tput cols');
    }

    /**
     * Get the available height of the terminal
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->height ?: $this->height = (int) exec('tput lines');
    }

    /**
     * Clear the current cursors line
     *
     * @return void
     */
    public function clearLine()
    {
        echo sprintf("\033[%dD\033[K", $this->getWidth());
    }

    /**
     * Move the cursor to the start of a specific column
     *
     * @param int $column
     */
    public function moveCursorToColumn($column)
    {
        echo sprintf("\033[%dC", $column);
    }

    /**
     * Move the cursor to the start of a specific row
     *
     * @param int $rowNumber
     */
    public function moveCursorToRow($rowNumber)
    {
        echo sprintf("\033[%d;0H", $rowNumber);
    }

    /**
     * Clean the whole console without jumping the window
     */
    public function clean()
    {
        foreach (range(0, $this->getHeight()) as $rowNum) {
            $this->moveCursorToRow($rowNum);
            $this->clearLine();
        }
    }
}