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

    /**
     * 获取对象的数据map
     * @return array
     */
    public function genDataMap()
    {
        $rt = [];
        foreach($this as $name => $value) {
            if (!is_null($value)) {
                $rt[$name] = $value;
            }
        }
        return $rt;
    }
}