<?php


class JsonFilter extends BaseFilter
{
    /**
     * @param Request $request
     * @return mixed
     */
    function doFilter(Request $request)
    {
        $json = $request->_get($_REQUEST, 'data');
        //解包
        $clientData = json_decode($json, true);
        $request->setClientData($clientData);

        $rt = $this->next->doFilter($request);

        //打包
        return json_encode($rt);
    }
}