<?php 
namespace JuaGuz\ApiGenerator\Clases;

use DB;

class Model extends Base
{
	
	private $path;

	

	private $model;

	private $table;

	private $fields;

	private $fillables = null;

	public $namespace;

	protected $db;

	private $template;




	function __construct($connection,$model,$table)
	{
		$this->connection = $connection;
		$this->lConnection = strtolower($connection);
		$this->model     = $model;
		$this->table     = $table;

		$this->template  = $this->template = sprintf("%s/templates/model.php",dirname(__DIR__));

		$this->db 		 =  DB::connection($this->lConnection);

		parent::__construct($connection);

		$this->generatePath();
		
			
	}

	public function generate(){
		$this->genFields();
		$this->generateFillable();
		$this->setFile($this->template);
		$this->set("NAMESPACE",$this->namespace);
		$this->set("NAME",$this->model);
		$this->set("MODEL",$this->model);
		$this->set("FIELDS",$this->fillables);
		$this->set("TABLENAME",$this->table);
		$this->set("CONN",$this->lConnection);
		$this->set("PK",$this->fields[0]->Field);
		$this->write($this->path);
		$this->addTimeStamps();

	}
	public function getFields(){
		return $this->fields;
	}
	private function genFields(){
		
     	$this->fields = $this->db->select("DESCRIBE $this->table");
     	return $this->fields;
     	
	}

	private function addTimeStamps(){
		 
		try{
			$this->db->transaction(function ()  {
				$deleted  = "ALTER TABLE $this->table ADD COLUMN `deleted_at` datetime DEFAULT NULL";
				$this->db->statement($deleted);
			});
		}catch(\Illuminate\Database\QueryException $e){

		}
		try{
			$this->db->transaction(function ()  {
				$created = "ALTER TABLE $this->table ADD COLUMN `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP";
				$this->db->statement($created);
			});
		}catch(\Illuminate\Database\QueryException $e){

		}

		try{
			$this->db->transaction(function () {
				$updated  = "ALTER TABLE $this->table ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
				$this->db->statement($updated);
			});
		}catch(\Illuminate\Database\QueryException $e){

		}

  			
  

	}


	private function generateFillable(){

		foreach ($this->fields as $value) {
			$this->fillables.= "'$value->Field',";
		}

		

	}


	public function generatePath(){
		$basePath = "Api/Entities/".$this->namespace;
		if (!file_exists("app/".$basePath)) {
    		mkdir("app/".$basePath, 0777, true);
		}
		$this->path = $basePath."/$this->model";
		return $this->path;
	}



}