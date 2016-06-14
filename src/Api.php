<?php
namespace JuaGuz\ApiGenerator;

use ErrorException;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;


abstract class Api extends ApiController{

    protected $model;

    private $modelSearch;

    protected $transformer;

    private $tiposBusquedas = [
        "~"=>"LIKE",
        "-"=>"!=",
        ")"=>">",
        "]"=>">=",
        "("=>"<",
        "["=>"<=",
    ];

    protected $itemSave;

    const EXPLODE_PARSE_FIELDS_VALUES = "|";

    const EXPLODE_PARSE_FIELD_VALUE = ":";

    const SEARCH_DEFAULT_OPERATOR = "=";

    public function __construct(ApiModelInterface $model,BaseTransformer $transformer){
        parent::__construct();
        $this->model = $model;
        $this->transformer = $transformer;
        $relacionesValidas = $this->getRelacionesValidas();
    }

    public function __destruct(){}

    protected function whereLike($key,$value){
        return $this->model->where($key,'like',"%".$value."%");
    }

    protected function whereEquals($key,$value){
        return $this->model->where($key, self::SEARCH_DEFAULT_OPERATOR,$value);
    }

    protected function whereMinor($key,$value){
        return $this->model->where($key,'<',$value);
    }

    protected function whereLessequals($key,$value){
        return $this->model->where($key,'<=',$value);
    }

    protected function whereHigher($key,$value){
        return $this->model->where($key,'>',$value);
    }

    protected function whereMoreEquals($key,$value){
        return $this->model->where($key,'>=',$value);
    }

    protected function whereDiff($key,$value){
        return $this->model->where($key,'!=',$value);
    }

    protected function search($filters,$filterName){

        foreach ($filters as $key => $value) {
            $method = "where" . ucfirst($filterName);
            $this->model = call_user_func_array([$this, $method],array($key,$value));
        }

    }

    protected function filter(&$filters){
        $model = null;
        array_walk($filters,[$this,"search"]);
        $this->model = $this->model->distinct();
        return $this->model->get();
    }


    protected function busquedaRelaciones($relacion,$fields){

        $fieldsAndValues = explode(self::EXPLODE_PARSE_FIELDS_VALUES, $fields);

        foreach($fieldsAndValues as $value)
        {
            $data = explode(self::EXPLODE_PARSE_FIELD_VALUE, $value);
            $searchField = $data[0];
            $value = $data[1];

            list($searchOperator, $searchValue) = $this->parseOperatorAndValue($value);

            $this->model = $this->model->whereHas($relacion,function($q) use($searchValue,$searchOperator,$searchField){
                if($searchOperator =="LIKE") $searchValue = "%$searchValue%";
                $q->where($searchField,$searchOperator,$searchValue);
            });
        }

        return $this->model->with($relacion);
    }


    protected function parseRelacion($relacion){
        $relacion =  explode(".", $relacion);

        return $relacion;
    }

    protected function relaciones($relaciones){
        $relacionesValidas = $this->getRelacionesValidas();
        try {
            foreach ($relaciones as $relacion) {
                $relacion  = $this->checkRelacionWithFilters($relacion);

                $in_array = in_array($relacion[0], $relacionesValidas);
                $in_array1 = in_array($relacion, $relacionesValidas);

                if( $in_array or $in_array1){

                    if(is_array($relacion))
                    {
                        if(isset($relacion[2])){
                            $this->model  = $this->busquedaRelaciones($relacion[0],$relacion[1].".".$relacion[2]);
                        }else{
                            $this->model  = $this->busquedaRelaciones($relacion[0],$relacion[1]);
                        }

                    }else{
                        $this->model = $this->model->with($relacion);
                    }
                }
            }
            return true;
        } catch( ErrorException $e) {
            $this->setMessage($e->getMessage());
        }
        return false;
    }

    abstract function getRelacionesValidas();

    public function index()
    {
        $model = null;
        $filters     = Input::except(["page","paginado","cantPage","relaciones","orderBy"]);

        $relaciones   = Input::get("relaciones",[]);
        if(!$this->relaciones($relaciones)) return $this->respondInternalError();

        $orderBy   = Input::get("orderBy",[]);
        if(!$this->orderBy($orderBy)) return $this->respondInternalError();

        $model = $this->filter($filters);
        if(empty($model)) return $this->respondNotFound();

        $data = [
            'data' => $model,
            'error' => false
        ];

        return $this->respond($data);
    }

    public function store(Request $request)
    {

        DB::beginTransaction();

        $relationships = "relationships";
        $rules = $this->model->getRules();
        $data  = $request->all();
        #if(isset($data[$relationships]) and is_array($data[$relationships])) $this->saveRelationships($data[$relationships]);
        $valid = Validator::make($data,$rules);
        if($valid->fails()) return $this->respondInvalidEntity($valid->errors()->all());
        $this->itemSave = $this->model->create($data);
        if(isset($data[$relationships]) and is_array($data[$relationships])){
            $saveRelationships = $this->saveRelationships($data[$relationships]);
            if(!$saveRelationships["success"]){
                DB::rollback();
                return $this->respondInvalidEntity($saveRelationships["errors"]);
            }
        }

        DB::commit();

        return $this->respondCreated('Se ha creado con exito.',$this->itemSave->getKey());
    }

    private function saveRelationships($relaciones){
        foreach ($relaciones as $key => $values) {
            foreach ($values as $keyRelation=>$value) {
                foreach ($value as $value2) {
                    $relation = $this->itemSave->{$keyRelation}();
                    $className = get_class($relation->getRelated());
                    $instanceOfClass = new $className($value2);
                    $valid = Validator::make($value2,$instanceOfClass->getRules(),$instanceOfClass->getErrorMessage());
                    if($valid->fails()) return ["success"=>false,"errors"=>$valid->errors()->all()];
                    $relation->save($instanceOfClass);
                }
            }
        }
        return ["success"=>true];
    }

    public function show($id)
    {
        $relaciones   = Input::get("relaciones",[]);
        if(!$this->relaciones($relaciones)) return $this->respondInternalError();

        $orderBy   = Input::get("orderBy",[]);
        if(!$this->orderBy($orderBy)) return $this->respondInternalError();

        $model = $this->model->find($id);
        if(!$model) return $this->respondNotFound();
        //$configuracionesTransformer = $this->transformer->transform($model->toArray());
        $data = [
            'data' => $model,
            'error' => false
        ];
        return $this->respond($data);
    }

    public function update(Request $request,$id)
    {
        $model = $this->model->find($id);

        if(!$model) return $this->respondNotFound();

        $rules = $this->model->getRules();

        $data  = $request->all();

        $valid = Validator::make($data,$rules,$this->model->getErrorMessage());

        if($valid->fails()) return $this->respondInvalidEntity($valid->errors()->all());

        if(!$model->fill($request->all())->save()) return $this->respondWithError('Hubo un error al actualizar!');

        $data = [
            'data'=>[
                'message'=>'Exito al guardar!'
            ],
            'error'=>false,
        ];
        return $this->respond($data);
    }


    public function destroy($id){
        $data = [];
        $model = $this->model->find($id);

        if(!$model) return $this->respondNotFound();

        try{
            $model->delete();
            $data['data'] = 'Registro eliminado correctamente';
            $data['error']= false;
        }
        catch(Illuminate\Database\QueryException $e){
            $data['data'] = 'Hubo un error al eliminar el registro';
            $data['error']= $e;
        }

        return $data;

    }

    /**
     * @param $relacion
     * @return array
     */
    protected function checkRelacionWithFilters($relacion)
    {
        $existeFiltrarEnCampo = strpos($relacion, self::EXPLODE_PARSE_FIELD_VALUE) !== false;
        $existeCampo = strpos($relacion, ".") !== false;

        return ($existeFiltrarEnCampo and $existeCampo) ? $this->parseRelacion($relacion) : $relacion;
    }

    protected function parseOperatorAndValue($value)
    {
        $searchOperator = self::SEARCH_DEFAULT_OPERATOR;
        $indexOperator = $value[0];
        $existeElOperadorDeBusqueda = array_key_exists($indexOperator, $this->tiposBusquedas);

        if ($existeElOperadorDeBusqueda) {
            $searchOperator = $this->tiposBusquedas[$indexOperator];
            $value = $this->getStringAfterPattern($indexOperator, $value);
        }

        return array($searchOperator, $value);
    }

    private function getStringAfterPattern($string, $inthat)
    {
        if (!is_bool(strpos($inthat, $string)))
            return substr($inthat, strpos($inthat,$string)+strlen($string));
    }

    private function containsFilters($relacion)
    {
        return explode(self::EXPLODE_PARSE_FIELDS_VALUES,$relacion);
    }

    protected function orderBy($orderBy)
    {
        try {
            foreach ($orderBy as $field=>$order) $this->model = $this->model->orderBy($field, $order);
            return true;
        } catch( ErrorException $e) {
            $this->setMessage($e->getMessage());
        }
        return false;
    }

}
