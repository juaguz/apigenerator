<?php namespace JuaGuz\ApiGenerator\Clases;

use \Exception;

class Template
{
    protected $_file;
    protected $_data = array();

    public function setFile($file){
        $this->_file = $file;
    }

    public function set($key, $value)
    {
        $this->_data[$key] = $value;
        return $this;
    }

    public function render()
    {
        extract($this->_data);
        ob_start();
        include($this->_file);
        return ob_get_clean();
    }

    public function write($path){
        $path = $path.".php";
        $path = app_path($path);
        try{
            if(file_exists($path)) throw new Exception("El archivo $path ya existe"); 
        
            $stream = $this->render();
            file_put_contents($path,$stream);
        
        }catch(Exception $e){
             \Log::error($e->getMessage());
             print $e->getMessage();
             print "\n";
        }
        
        
        
        
        
    }

}
?>