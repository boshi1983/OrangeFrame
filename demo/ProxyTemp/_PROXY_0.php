<?php
final class _PROXY_0 implements BaseFilter {
private $target;
private $handler;
function __destruct(){$this->target = null;$this->handler=null;}private static $m0;public function doFilter($data,$link){try {
    return $this->handler->invoke($this, $this->target, self::$m0, func_get_args());
} catch (Exception $e) {
    throw $e;
} finally {
}}public function __construct($target, $handler){
$this->target = $target;$this->handler = $handler;$classname = get_class($target);
$reflection = null;
try {$reflection = new \ReflectionClass($classname);} 
catch (\Exception $exception) {}self::$m0 = $reflection->getMethod('doFilter');}}