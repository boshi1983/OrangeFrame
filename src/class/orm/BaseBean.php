<?php


class BaseBean implements JsonSerializable
{
    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $data = [];
        foreach ($this as $key => $val){
            if ($val !== null) $data[$key] = $val;
        }
        return $data;
    }
}