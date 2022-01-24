<?php



class ChooseNode implements BaseNode
{
    private $when = [];
    private $otherwise = '';

    /**
     * @return array
     */
    public function getWhen(): array
    {
        return $this->when;
    }

    /**
     * @param array $when
     */
    public function setWhen(array $when): void
    {
        $this->when = $when;
    }

    public function addWhen($node): void
    {
        $this->when[] = $node;
    }

    /**
     * @return string
     */
    public function getOtherwise(): string
    {
        return $this->otherwise;
    }

    /**
     * @param string $otherwise
     */
    public function setOtherwise(string $otherwise): void
    {
        $this->otherwise = $otherwise;
    }

    /**
     * @return string
     */
    public function getString($idx): string
    {
        $content = '';
        $whens = $this->getWhen();
        /**
         * @var CompareNode $when
         */
        foreach ($whens as $index => $when) {
            if ($index == 0) {
                $content .= 'if(' . $when->getTest() . ')' . PHP_EOL;
            } else {
                $content .= 'elseif(' . $when->getTest() . ')' . PHP_EOL;
            }
            $content .= '{$sql .= \' ' . $when->getContent() . '\';';
            if (!empty($when->getInclude())) {
                $content .= '$this->bindParam(\'' . $when->getInclude() . '\', $' . $when->getInclude() . ');' . PHP_EOL;
            }
            $content .= '}' . PHP_EOL;
        }
        $otherwise = $this->getOtherwise();
        if (!empty($otherwise)) {
            $content .= 'else{$sql .= \' ' . $otherwise . '\';}' . PHP_EOL;
        }

        return $content;
    }


}