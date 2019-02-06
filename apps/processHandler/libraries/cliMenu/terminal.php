<?php

namespace mpcmf\apps\processHandler\libraries\cliMenu;

class terminal
{
    const KEY_UP = 0x1b5b41;
    const KEY_DOWN = 0x1b5b42;
    const KEY_LEFT = 0x1b5b44;
    const KEY_RIGHT = 0x1b5b43;
    const KEY_SPACE = 0x20;
    const KEY_F2 = 0x1b4f51;
    const KEY_F3 = 0x1b4f52;
    const KEY_F4 = 0x1b4f53;
    const KEY_F5 = 0x1b5b31357e;
    const KEY_F6 = 0x1b5b31377e;
    const KEY_F7 = 0x1b5b31387e;
    const KEY_F8 = 0x1b5b31397e;
    const KEY_F9 = 0x1b5b32307e;
    const KEY_F10 = 0x1b5b32317e;
    const KEY_F12 = 0x1b5b32347e;
    const KEY_ENTER = 0x0a;
    const KEY_DELETE = 0x1b5b337e;
    const KEY_INSERT = 0x1b5b327e;
    const KEY_PAGE_UP = 0x1b5b357e;
    const KEY_PAGE_DOWN = 0x1b5b367e;
    const KEY_HOME = 0x1b5b48;
    const KEY_END = 0x1b5b46;
    const KEY_SLASH = 0x2f;
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
        $key = fgetc($stdin);

        while (stream_get_meta_data($stdin)['unread_bytes'] !== 0) {
            $key .= fgetc($stdin);
        }

        return hexdec(bin2hex($key));
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