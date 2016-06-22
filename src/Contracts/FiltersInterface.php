<?php

namespace JuaGuz\ApiGenerator\Contracts;

interface FiltersInterface
{
    public function processFilter($fields, &$model);
}
