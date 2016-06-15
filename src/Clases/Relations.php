<?php
/**
 * Created by PhpStorm.
 * User: damian
 * Date: 15/06/16
 * Time: 15:22
 */

namespace JuaGuz\ApiGenerator\Clases;

use JuaGuz\ApiGenerator\Contracts\RelationsInterface;

class Relations implements RelationsInterface
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

    public function searchRelations($relations, $fields, &$model)
    {

        $fieldsAndValues = explode(self::EXPLODE_PARSE_FIELDS_VALUES, $fields);

        foreach($fieldsAndValues as $value)
        {
            $data = explode(self::EXPLODE_PARSE_FIELD_VALUE, $value);
            $searchField = $data[0];
            $value = $data[1];
            $searchType = $this->searchType;

            list($searchOperator, $searchValue) = $this->helper->parseOperatorAndValue($value, $searchType);

            $model = $model->whereHas($relations,function($q) use($searchValue,$searchOperator,$searchField){
                if($searchOperator =="LIKE") $searchValue = "%$searchValue%";
                $q->where($searchField,$searchOperator,$searchValue);
            });
        }

        return $model->with($relations);
    }

    public function checkRelationWithFilters($relation)
    {
        $filterExits = strpos($relation, self::EXPLODE_PARSE_FIELD_VALUE) !== false;
        $fieldExits = strpos($relation, ".") !== false;

        return ($filterExits and $fieldExits) ? $this->parseRelation($relation) : $relation;
    }

    private function parseRelation($relation){
        return explode(".", $relation);
    }

}