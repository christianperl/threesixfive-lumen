<?php

namespace App\Services;


use App\Jobs\Recache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Predis\Client;
use FatSecret;

class Api
{
    public static function Ingredient($id)
    {
        if (Cache::has($id)) {
            if (($redis = new Client())->ttl('lumen_cache:' . $id) <= 3600) {
                dispatch(new Recache($id, 'i'));
            }

            return Cache::get($id);
        } else {
            $ingredient = FatSecret::getIngredient($id)['food'];
            Cache::put($ingredient['food_id'], $ingredient, 1440);

            return $ingredient;
        }
    }

    public static function Recipe($id)
    {
        if (Cache::has($id)) {
            if (($redis = new Client())->ttl('lumen_cache:' . $id) <= 3600) {
                dispatch(new Recache($id, 'r'));
            }

            return Cache::get($id);
        } else {
            $recipe = FatSecret::getRecipe($id)['recipe'];
            Cache::add($recipe['recipe_id'], $recipe, 1440);

            return $recipe;
        }
    }

    public static function Search($page, $max, $type)
    {
        return FatSecret::searchRecipes('', $page, $max, $type)['recipes'];
    }
}