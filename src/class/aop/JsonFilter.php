<?php


class JsonFilter implements BaseFilter
{
    /**
     * @param String $json
     * @param FilterChain $link
     * @return mixed
     */
    function doFilter($json, $link)
    {
        //解包
        $clientData = json_decode($json, true);

        $rt = $link->doFilter($clientData);

        //打包
        return json_encode($rt);
    }
}