<?php 
namespace JuaGuz\ApiGenerator\Clases;

class Controller extends Base
{
	
	private $path;

	private $model;

    private $template;



	function __construct($connection,$model)
    {
        $this->model = $model;

        $this->template = sprintf("%s/templates/controller.php",dirname(__DIR__));

		parent::__construct($connection);	
		
	}

	public function generate(){
		$this->setFile($this->template);
		
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