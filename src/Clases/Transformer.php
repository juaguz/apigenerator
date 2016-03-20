<?php 
namespace JuaGuz\ApiGenerator\Clases;


class Transformer extends Base
{
	
	private $path;

	private $model;

	private $array = null;

	private $fields;


    private $template;

	function __construct($connection,$model,$fields)
	{
		$this->model     = $model;
		$this->fields    = $fields;
		$this->template  = sprintf("%s/templates/transformers.php",dirname(__DIR__));
		parent::__construct($connection);	
		
	}

	public function generate(){
		$this->setFile($this->template);
		$this->genArray();
		$this->set("NAME",$this->model);
		$this->set("ARRAY",$this->array);
		$this->write($this->path);

	}

	public function genArray(){
		
		foreach ($this->fields as  $value) {
			if($value->Key == "PRI") {
				$this->array.="'id'=>$"."item['".$value->Field."']".",\n";
			} else {
				$this->array.="'$value->Field'=>$"."item['".$value->Field."']".",\n";
			}
		}


	}

	public function generatePath(){
		$basePath = "Api/Transformers/";
		if (!file_exists("app/".$basePath)) {
    		mkdir("app/".$basePath, 0777, true);
		}
		
		
		$this->path = $basePath.$this->model."Transformer";
		return $this->path;
	}



}