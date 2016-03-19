<?php

namespace JuaGuz\ApiGenerator\Console;

use Illuminate\Console\Command;

use JuaGuz\ApiGenerator\Clases\Controller;
use JuaGuz\ApiGenerator\Clases\Model;
use JuaGuz\ApiGenerator\Clases\Transformer;
use JuaGuz\ApiGenerator\Clases\Routes;



class GenerateApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:generate {conn} {table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea nuevas apis';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $connection   =  $this->argument('conn');
        
        $table        =  $this->argument('table');

        $model        =  studly_case($table);

        $controller = new Controller($connection,$model);

        $controller->generate();

        $gModel = new Model($connection,$model,$table);
        
        $gModel->generate();
        
        $fields  = $gModel->getFields();
        
        $transformer = new Transformer($connection,$model,$fields);
        $transformer->generate();

        //$nombre_tabla,$nombre_conexion,$nombre_controller

        $ruta = new Routes($table,ucfirst($connection),$model."Controller");
        $ruta->generateRoute();


        
        
    }






}
