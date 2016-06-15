<?php
/**
 * Created by PhpStorm.
 * User: damian
 * Date: 15/06/16
 * Time: 15:22
 */

namespace JuaGuz\ApiGenerator\Clases;

use JuaGuz\ApiGenerator\Contracts\FiltersInterface;

class Filters implements FiltersInterface
{

    const EXPLODE_PARSE_FIELDS_VALUES = "|";

    const EXPLODE_PARSE_FIELD_VALUE = ":";

    private $helper;

    private $searchType = [
        "~"=>"LIKE",
        "-"=>"!=",
        ")"=>">",
        "]"=>">=",
        "("=>"<",
        "["=>"<=",
    ];

    function __construct(){
        $this->helper = new Helper();
    }

    public function processFilter($field, &$model)
    {
        $data = explode(self::EXPLODE_PARSE_FIELD_VALUE, $field);
        $searchField = $data[0];
        $value = $data[1];
        $searchType = $this->searchType;

        list($searchOperator, $searchValue) = $this->helper->parseOperatorAndValue($value, $searchType);

        return $model->where($searchField,$searchOperator,$searchValue);
    }

} 