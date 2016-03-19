<?php

namespace JuaGuz\ApiGenerator;


abstract class BaseTransformer {

    public function transformCollection(array $items){
        return array_map([$this, 'transform'],$items);
    }

    abstract public function transform($item);

}