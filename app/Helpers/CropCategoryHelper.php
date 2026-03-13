<?php

namespace App\Helpers;

class CropCategoryHelper {
   
   publi static function classify($value,$min,$max)
   {
       // code...
    if ($value < $min) {
        // code...
        return 'Low';
    }
    if ($value > $max) {
        // code...
        return 'High';
    }
    if ($value <= $max) {
        // code...
        return 'Neutral';
    }
   }
   public static function score($soilLevel,$cropLevel){

    return match($soilLevel){
        $cropLevel => 1,
        'Neutral'=> 0.66,
        default => 0.33
    }
   }

   public static function overAllScore($scores)
   {
       // code...
    return round(array_sum($scores) /count($scores) * 100,2 );
   }
}

?>