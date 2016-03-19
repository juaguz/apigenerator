<?php echo "<?php namespace App\Microvoz\Transformers;" ?>


class <?php echo $NAME?>Transformer extends BaseTransformer
{

    /**
     * @param $item
     * @return array
     */
    public function transform($item)
    {
        return  [
            <?php echo $ARRAY."\n" ?>
        ];
    }
}