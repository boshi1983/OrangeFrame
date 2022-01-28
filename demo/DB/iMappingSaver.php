<?php

interface iMappingSaver
{
    function read();
    function write($data);
}