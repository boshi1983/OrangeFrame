<?php

class VariateNode implements BaseNode
{
    private $attribute;

    /**
     * @param $attribute
     */
    public function __construct($attribute)
    {
        $this->attribute = $attribute;
    }

    private function getAttribute($key)
    {
        if (empty($key)) {
            return $this->attribute;
        }

        return $this->attribute[strtoupper($key)] ?? '';
    }

    /**
     * @return string
     */
    function getString($idx): string
    {
        $rtString = '';

        $type = $this->getAttribute('type');
        switch ($type) {
            case 'field':

                $collection = $this->getAttribute('collection');
                $rtString .= 'list($fields, $values, $datas, $length) = $this->getInsertInfo('.$collection.');' . PHP_EOL;

                $rtString .= 'foreach ($datas as $key => $value) {' . PHP_EOL;
                $rtString .= '$this->bindParam($key, $value);' . PHP_EOL;
                $rtString .= '}' . PHP_EOL;

                $rtString .= '$sql .= $fields;' . PHP_EOL;
                break;
            case 'value':
                $rtString .= '$sql .= $values;' . PHP_EOL;
                break;
            case 'set':
                $name = $this->getAttribute('name');
                $rtString .= $name . ' = [];' . PHP_EOL;

                $collection = $this->getAttribute('collection');
                $rtString .= 'list($fields, $values, $datas, $length) = $this->getInsertInfo('.$collection.');' . PHP_EOL;
                $rtString .= 'foreach ($datas as $key => $value) {' . PHP_EOL;
                $rtString .= '$this->bindParam($key, $value);' . PHP_EOL;

                $rtString .= $name . '[] = \'`\' . $key . \'`\' . \'=:\' . $key;' . PHP_EOL;

                $rtString .= '}' . PHP_EOL;

                $rtString .= '$sql .= join(\',\', ' . $name . ');' . PHP_EOL;
                break;
        }

        return $rtString;
    }

}