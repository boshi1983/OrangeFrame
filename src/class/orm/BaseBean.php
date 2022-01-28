<?php


class BaseBean implements JsonSerializable
{
    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->genDataMap();
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
                if (gettype($value) == 'array') {

                    $arr = [];
                    foreach ($value as $key => $unit) {
                        if ($unit instanceof BaseBean) {
                            $arr[$key] = $unit->genDataMap();
                        } else {
                            $arr[$key] = $unit;
                        }
                    }
                    $rt[$name] = $arr;

                } elseif ($value instanceof BaseBean) {
                    $rt[$name] = $value->genDataMap();
                } else {
                    $rt[$name] = $value;
                }
            }
        }
        return $rt;
    }
}