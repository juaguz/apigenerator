<?php namespace JuaGuz\ApiGenerator;

class Facade extends \Illuminate\Support\Facades\Facade
{
    /**
     * {@inheritDoc}
     */
    protected static function getFacadeAccessor()
    {
        return 'apigenerator';
    }
}
