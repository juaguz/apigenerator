<?php 
namespace JuaGuz\ApiGenerator;

use Illuminate\Http\Request;

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
        return [];
    }

    public function index()
    {
        
        $model = null;
        $filters     = Input::except(["page","paginado","cantPage","relaciones"]);
        $relaciones   = Input::get("relaciones",[]);
        $this->relaciones($relaciones);
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

        $relationships = "relationships";

        $rules = $this->model->getRules();

        $data  = $request->all();

        $valid = Validator::make($data,$rules,$this->model->getErrorMessage());

        if($valid->fails()) return $this->respondInvalidEntity($valid->errors()->all());


        $this->itemSave = $this->model->create($data);

        if(isset($data[$relationships]) and is_array($data[$relationships])) $this->saveRelationships($data[$relationships]);


        return $this->respondCreated('Se ha creado con exito.',$this->itemSave->getKey());
    }


    private function saveRelationships(){
        
    }

    
    public function show($id)
    {
        $relaciones   = Input::get("relaciones",[]);
        $this->relaciones($relaciones);
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


}