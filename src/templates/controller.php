<?php echo "<?php" ?> namespace App\Http\Controllers\Api\<?php echo $NAMESPACE ?>;

use App\Microvoz\Entities\<?php echo $NAMESPACE ?>\<?php echo $MODEL ?> as Model;
use App\Microvoz\Transformers\<?php echo $MODEL ?>Transformer as Transformer;
use App\Microvoz\Clases\Api;


class <?php echo $NAME?>Controller extends Api
{
    protected $model;
    protected $tranformer;
    public function __construct(Model $model,Transformer $transformer)
    {
        parent::__construct($model,$transformer);
        
    }

    
}

