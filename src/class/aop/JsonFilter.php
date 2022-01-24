<?php


class JsonFilter extends BaseFilter
{
    /**
     * @param String $json
     * @param FilterChain $link
     * @return mixed
     */
    function doFilter($json)
    {
        //解包
        $clientData = json_decode($json, true);

        $rt = $this->next->doFilter($clientData);

        //打包
        return json_encode($rt);
    }
}