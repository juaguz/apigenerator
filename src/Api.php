<?php
namespace JuaGuz\ApiGenerator;

use ErrorException;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use JuaGuz\ApiGenerator\Clases\Filters;
use JuaGuz\ApiGenerator\Clases\Relations;


abstract class Api extends ApiController
{

    protected $model;

    protected $transformer;

    protected $itemSave;

    private $relations;

    private $filters;


    public function __construct(
        ApiModelInterface $model,
        BaseTransformer $transformer)
    {
        parent::__construct();
        $this->model = $model;
        $this->transformer = $transformer;
        $this->relations = new Relations();
        $this->filters = new Filters();
    }

    public function __destruct(){}

    public function index()
    {
        $model = null;
        $filter     = Input::except(["page","paginado","cantPage","relaciones","orderBy","filters"]);

        $filters   = Input::get("filters",[]);
        if(!empty($filters))
            if(!$this->filters($filters)) return $this->respondInternalError();

        $relaciones   = Input::get("relaciones",[]);
        if(!empty($relaciones))
            if(!$this->relations($relaciones)) return $this->respondInternalError();

        $orderBy   = Input::get("orderBy",[]);
        if(!empty($orderBy))
            if(!$this->orderBy($orderBy)) return $this->respondInternalError();

        $model = $this->filter($filter);
        if(empty($model)) return $this->respondNotFound();

        $data = [
            'data' => $model,
            'error' => false
        ];

        return $this->respond($data);
    }

    public function show($id)
    {
        $filters   = Input::get("filters",[]);
        if(!empty($filters))
            if(!$this->filters($filters_A)) return $this->respondInternalError();

        $relaciones   = Input::get("relaciones",[]);
        if(!empty($relaciones))
            if(!$this->relations($relaciones)) return $this->respondInternalError();

        $orderBy   = Input::get("orderBy",[]);
        if(!empty($orderBy))
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

    protected function whereLike($key,$value){
        return $this->model->where($key,'like',"%".$value."%");
    }

    protected function whereEquals($key,$value){
        return $this->model->where($key, "=",$value);
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

    protected function relations(&$relations){

        $relacionesValidas = $this->getValidRelations();

        try {

            foreach ($relations as $relation) {

                $relation  = $this->relations->checkRelationWithFilters($relation);
                $in_array = in_array($relation[0], $relacionesValidas);
                $in_array1 = in_array($relation, $relacionesValidas);

                if( $in_array or $in_array1){

                    if(is_array($relation))
                    {
                        if(isset($relation[2])){
                            $this->model  = $this->relations->searchRelations($relation[0],$relation[1].".".$relation[2],$this->model);
                        }else{
                            $this->model  = $this->relations->searchRelations($relation[0],$relation[1],$this->model);
                        }

                    }else{
                        $this->model = $this->model->with($relation);
                    }
                }
            }

            return true;

        } catch( ErrorException $e) {
            $this->setMessage($e->getMessage());
        }

        return false;
    }

    abstract function getValidRelations();

    protected function orderBy(&$orderBy)
    {
        try {
            foreach ($orderBy as $field=>$order) $this->model = $this->model->orderBy($field, $order);
            return true;
        } catch( ErrorException $e) {
            $this->setMessage($e->getMessage());
        }

        return false;
    }

    private function filters(&$filters)
    {
        try {
            foreach ($filters as $filter) $this->model  = $this->filters->processFilter($filter,$this->model);
            return true;
        } catch( ErrorException $e) {
            $this->setMessage($e->getMessage());
        }

        return false;
    }

}
