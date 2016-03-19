<?php 
namespace JuaGuz\ApiGenerator\Clases;


class Controller extends Base
{
	
	private $path;

	private $model;

    const TEMPLATE = '../templates/controller.php';


	function __construct($connection,$model)
	{
		$this->model     = $model;
		parent::__construct($connection);	
		
	}

	public function generate(){
		$this->setFile(self::TEMPLATE);
		
		$this->set("NAMESPACE",$this->namespace);
		$this->set("NAME",$this->model);
		$this->set("MODEL",$this->model);
		$this->write($this->path);

	}



	public function generatePath(){
		$basePath  = "Http/Controllers/Api/".$this->namespace;
		if (!file_exists("app/".$basePath)) {
    		mkdir("app/".$basePath, 0777, true);
		}
		$this->path = $basePath."/$this->model"."Controller";
		return $this->path;
	}



}