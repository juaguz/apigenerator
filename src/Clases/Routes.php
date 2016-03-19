<?php
namespace JuaGuz\ApiGenerator\Clases;

class Routes
{
	protected $nombre_tabla;
	protected $nombre_conexion;
	protected $nombre_controller;
	function __construct($nombre_tabla,$nombre_conexion,$nombre_controller)
	{
		$this->nombre_tabla      = $nombre_tabla;
		$this->nombre_conexion   = $nombre_conexion;	
		$this->nombre_controller = $nombre_controller;
	}

	public function generateRoute(){
		$ruta = "Route::resource('$this->nombre_tabla','Api\\$this->nombre_conexion\\$this->nombre_controller'); \n";	
		//$path = "/var/www/html/microvoz/api/app/Http/routes.php";
		$path = "app/Http/routes.php";
		file_put_contents($path, $ruta, FILE_APPEND);

	}
	
}