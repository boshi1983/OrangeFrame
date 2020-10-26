<?php


interface iBaseDao
{
    function query(string $sql);
    function execute(string $sql);

    function begin();
    function commit();

    function getLastSql();
}