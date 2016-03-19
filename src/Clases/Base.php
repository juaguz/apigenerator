<?php 
namespace JuaGuz\ApiGenerator\Clases;

abstract class Base extends Template {

	public $connection;
	public $namespace;
	
	public function __construct($connection){
		$this->connection = $connection;
		$this->generateNamespace();
		$this->generatePath();


	}

	public function generateNamespace(){
		$this->namespace =  ucfirst(strtolower($this->connection));	
	}



	abstract function generatePath();
	abstract function generate();
	

}