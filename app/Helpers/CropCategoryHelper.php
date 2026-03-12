<?php

namespace App\Helpers;

class CropCategoryHelper {
    public static function calculateNutrientCategory($nutrientValues) {
        $totalNutrients = array_sum($nutrientValues);
        $numNutrients = count($nutrientValues);
        $midpoint = $totalNutrients / $numNutrients;

        if ($midpoint < 20) {
            return 'Low';
        } elseif ($midpoint >= 20 && $midpoint < 50) {
            return 'Medium';
        } else {
            return 'High';
        }
    }
}

?>