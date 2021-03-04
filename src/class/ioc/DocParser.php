<?php
/**
 * Parses the PHPDoc comments for metadata. Inspired by Documentor code base
 * @category   Framework
 * @package    restler
 * @subpackage helper
 * @author     Murray Picton <info@murraypicton.com>
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.gnu.org/licenses/ GNU General Public License
 * @link       https://github.com/murraypicton/Doqumentor
 */

class DocParser
{
    /**
     * @var array
     */
    private $params = [];

    /**
     * @param string $doc
     * @return array
     */
    function parse($doc = '')
    {
        $this->params = [];
        if (empty($doc)) {
            return [];
        }
        // Get the comment
        if (preg_match('#^/\*\*(.*)\*/#s', $doc, $comment) === false)
            return $this->params;
        $comment = trim($comment [1]);
        // Get all the lines and strip the * from the first character
        if (preg_match_all('#^\s*\*(.*)#m', $comment, $lines) === false)
            return $this->params;
        $this->parseLines($lines[1]);
        return $this->params;
    }

    /**
     * @param $lines
     */
    private function parseLines($lines)
    {
        $desc = [];
        foreach ($lines as $line) {
            $parsedLine = $this->parseLine($line); // Parse the line

            if ($parsedLine === false && !isset ($this->params ['description'])) {
                if (isset ($desc)) {
                    // Store the first line in the short description
                    $this->params ['description'] = implode(PHP_EOL, $desc);
                }
            } elseif ($parsedLine !== false) {
                $desc [] = $parsedLine; // Store the line in the long description
            }
        }
        $desc = implode(' ', $desc);
        if (!empty ($desc)) {
            $this->params['long_description'] = $desc;
        }
    }

    /**
     * @param $line
     * @return bool|string
     */
    private function parseLine($line)
    {
        // trim the whitespace from the line
        $line = trim($line);

        if (empty ($line))
            return false; // Empty line

        if (strpos($line, '@') === 0) {
            if (strpos($line, ' ') > 0) {
                // Get the parameter name
                $param = substr($line, 1, strpos($line, ' ') - 1);
                $value = substr($line, strlen($param) + 2); // Get the value
            } else {
                $param = substr($line, 1);
                $value = '';
            }
            // Parse the line and return false if the parameter is valid
            if ($this->setParam($param, $value))
                return false;
        }

        return $line;
    }

    /**
     * @param $param
     * @param $value
     * @return bool
     */
    private function setParam($param, $value)
    {
        if ($param == 'param' || $param == 'return') {
            $value = $this->formatParamOrReturn($value);
        }
        if ($param == 'class') {
            list ($param, $value) = $this->formatClass($value);
        }

        if (empty ($this->params [$param])) {
            $this->params [$param] = $value;
        } else if ($param == 'param') {
            if (is_array($this->params [$param])) {
                $this->params [$param][] = $value;
            } else {
                $this->params [$param] = [$this->params [$param], $value];
            }
        } else {
            $this->params [$param] = $value + $this->params [$param];
        }
        return true;
    }

    /**
     * @param $value
     * @return array
     */
    private function formatClass($value)
    {
        $r = explode('|', $value);
        if (is_array($r)) {
            $param = $r [0];
            parse_str($r [1], $value);
            foreach ($value as $key => $val) {
                $val = explode(',', $val);
                if (count($val) > 1)
                    $value [$key] = $val;
            }
        } else {
            $param = 'Unknown';
        }
        return [$param, $value];
    }

    /**
     * @param $string
     * @return string
     */
    private function formatParamOrReturn($string)
    {
        $arr = explode(' ', $string);
        switch (count($arr)) {
            case 2:
            {
                /**
                 * @var boolean
                 */
                $type = $arr[0];
                switch ($type) {
                    case 'int':
                    case 'integer':
                        $type = 'intval';
                        break;
                    case 'float':
                        $type .= 'val';break;
                    case 'double':
                        $type .= 'val';break;
                    case 'bool':
                    case 'boolean':
                        $type = 'boolval';break;
                    case 'string':
                        $type = 'strval';break;
                }
                $value = $type($arr[1]);
                return defined($value)?constant($value):$value;
            }
            default:
            {
                $pos = strpos($string, ' ');

                $type = substr($string, 0, $pos);
                return '(' . $type . ')' . substr($string, $pos + 1);
            }
        }
    }
}