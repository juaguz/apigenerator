<?php
/**
 * Created by PhpStorm.
 * User: damian
 * Date: 15/06/16
 * Time: 15:23
 */

namespace JuaGuz\ApiGenerator\Clases;

class Helper {

    const SEARCH_DEFAULT_OPERATOR = "=";

    public function parseOperatorAndValue($value, $searchType)
    {
        $searchOperator = self::SEARCH_DEFAULT_OPERATOR;
        $indexOperator = $value[0];
        $existeElOperadorDeBusqueda = array_key_exists($indexOperator, $searchType);

        if ($existeElOperadorDeBusqueda) {
            $searchOperator = $searchType[$indexOperator];
            $value = $this->getStringAfterPattern($indexOperator, $value);
        }

        return array($searchOperator, $value);
    }

    private function getStringAfterPattern($string, $inthat)
    {
        if (!is_bool(strpos($inthat, $string)))
            return substr($inthat, strpos($inthat,$string)+strlen($string));

        return $string;
    }

}