<?php


interface iMyTableDao extends iBaseDao
{
    function getListByIn(array $ids):array;
    function getList(int $page, int $pagecount):array;
    function getPersonById(int $id):my_table;
    function getPersonByFirstName(string $firstName):array;
    function getCount():int;
    function getCountByGender(string $gender):int;

    function insert(my_table $my_table);
    function updateLastNameById(int $id, string $lastName);
    function deleteById(int $id);
}