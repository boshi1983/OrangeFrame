<?php

/**
 * @Controller
 * @RequestMapping test2
 */
class Test2Controller extends BaseController
{
    /**
     * @param Request $request
     * @param int $a
     * @return void
     * @GetMapping /b
     */
    public function b(Request $request, int $a) {
        $rtStr = '当前请求为：' . $request->Server('PHP_SELF') . '<br>';
        $rtStr .= '当前函数的Mapping与Class的Mapping拼接后，得到\'test/b\'<br>';

        return $rtStr;
    }
}