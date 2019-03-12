<?php

namespace App\Traits;


use Illuminate\Support\Facades\Cache;
use FatSecret;

trait CacheTrait
{
    public function cacheRecipe($recipe_id)
    {
        /*Cache::remember($recipe_id, 1440, function ($recipe_id) {
            return FatSecret::getRecipe($recipe_id)['recipe'];
        });*/

        if (Cache::has($recipe_id)) {
            return Cache::get($recipe_id);
        } else {
            $recipe = FatSecret::getRecipe($recipe_id)['recipe'];
            Cache::add($recipe['recipe_id'], $recipe, 1440);

            return $recipe;
        }
    }

    public function cacheIngredient($ingredient_id)
    {
        /*Cache::remember($ingredient_id, 1440, function ($ingredient_id) {
            return FatSecret::getRecipe($ingredient_id)['recipe'];
        });*/

        if (Cache::has($ingredient_id)) {
            return Cache::get($ingredient_id);
        } else {
            $ingredient = FatSecret::getIngredient($ingredient_id)['food'];
            Cache::add($ingredient['food_id'], $ingredient, 1440);

            return $ingredient;
        }
    }
}