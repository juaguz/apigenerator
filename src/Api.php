<?php 
namespace JuaGuz\ApiGenerator;

use App\Api\Entities\Cinotecnia\AltasOperativasxolores;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;


abstract class Api extends ApiController{
    protected $model;
    
    private $modelSearch;
    
    protected $transformer;
    
    private $relacionesValidas = [];

    protected $itemSave;

    public function __construct(ApiModelInterface $model,BaseTransformer $transformer){
        parent::__construct();
        $this->model = $model;
        $this->transformer = $transformer;
        $relacionesValidas = $this->getRelacionesValidas();
        

    }

    public function __destruct(){
        

    }




    protected function whereLike($key,$value){
        return $this->model->where($key,'like',$value."%");
    }

    protected function whereEquals($key,$value){
        return $this->model->where($key,'=',$value);
    }

    protected function whereMinor($key,$value){
        return $this->model->where($key,'<',$value);
    }

    protected function whereHigher($key,$value){
        return $this->model->where($key,'>',$value);
    }

    protected function whereDiff($key,$value){
        return $this->model->where($key,'!=',$value);
    }

    protected function search($filters,$filterName){
        
        foreach ($filters as $key => $value) {

            $this->model = call_user_func_array([$this,"where".ucfirst($filterName)],array($key,$value));
        }
        
    }

    protected function filter(&$filters){
        $model = null;
        
        array_walk($filters,[$this,"search"]);

        
        return $this->model->get();

    }

    protected function relaciones($relaciones){
        $relacionesValidas = $this->getRelacionesValidas();
        
        foreach ($relaciones as $relacion) {
            if(in_array($relacion,$relacionesValidas)){
                $this->model = $this->model->with($relacion);
            }
        }

        
    }

    public function getRelacionesValidas(){
        
    }

    public function index()
    {
        
        $model = null;
        $filters     = Input::except(["page","paginado","cantPage","relaciones","orderBy"]);
        $relaciones   = Input::get("relaciones",[]);
        $orderBy   = Input::get("orderBy",[]);
        $this->relaciones($relaciones);
        $this->orderBy($orderBy);
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
        $this->relaciones($relaciones);
        $orderBy   = Input::get("orderBy",[]);
        $this->orderBy($orderBy);
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

    private function containsFilters($relacion)
    {
        return explode('|',$relacion);
    }
    
    protected function orderBy($orderBy)
    {
        foreach ($orderBy as $field=>$order) $this->model = $this->model->orderBy($field, $order);
    }


}
