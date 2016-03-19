<?php 
namespace JuaGuz\ApiGenerator\Clases;


class Transformer extends Base
{
	
	private $path;

	private $model;

	private $array = null;

	private $fields;

    const TEMPLATE = '../templates/transformers.php';


	function __construct($connection,$model,$fields)
	{
		$this->model     = $model;
		$this->fields    = $fields;
		parent::__construct($connection);	
		
	}

	public function generate(){
		$this->setFile(self::TEMPLATE);
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
		

		$this->path = "Microvoz/Transformers/".$this->model."Transformer";
		return $this->path;
	}



}