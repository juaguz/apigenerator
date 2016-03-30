<?php echo "<?php" ?> namespace App\Http\Controllers\Api\<?php echo $NAMESPACE ?>;

use App\Api\Entities\<?php echo $NAMESPACE ?>\<?php echo $MODEL ?> as Model;
use App\Api\Transformers\<?php echo $MODEL ?>Transformer as Transformer;
use JuaGuz\ApiGenerator\Api;


class <?php echo $NAME?>Controller extends Api
{
    protected $model;
    protected $tranformer;
    public function __construct(Model $model,Transformer $transformer)
    {
        parent::__construct($model,$transformer);
        
    }

    public function getRelacionesValidas(){
        return [];
    }

    
}

