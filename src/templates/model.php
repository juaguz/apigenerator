<?php echo "<?php" ?> namespace App\Api\Entities\<?php echo $NAMESPACE ?>;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use JuaGuz\ApiGenerator\ApiModelInterface;

class <?php echo $NAME ?> extends Model implements ApiModelInterface{
	use SoftDeletes;
	protected $primaryKey = "<?php echo $PK ?>";
	protected $fillable = [<?php echo $FIELDS ?>];
	protected $dates = ['deleted_at'];
	protected $table = '<?php echo $TABLENAME ?>';
	protected $connection = '<?php echo $CONN ?>';

		
	public function getRules(){
		return [];
	}
    public function getErrorMessage(){
    	return [];
    }


}
