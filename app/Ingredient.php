<?php

namespace App;


use App\Services\Api;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Ingredient
{
    // Ingredient ID
    private $id;

    // Serving identifier
    private $serving_id;

    // Ingredient name
    private $name;

    // Ingredient sub categories
    private $sub_categories;

    // Nutrient values for each ingredient
    private $servings;

    // The unit of measure of this ingredient used in the recipe
    private $measurement;

    //  The unit of measure of this ingredient used in the recipe for the grocery list
    private $grocery_measurement;

    // The number of units of this ingredient used in the recipe
    private $units;

    // The number of units of this ingredient used in the recipe for the grocery list
    private $grocery_unit;

    public function __construct($ingredient, $number_of_servings)
    {
        $this->id = $ingredient['food_id'];
        $this->serving_id = $ingredient['serving_id'];
        $this->name = $ingredient['food_name'];
        $this->sub_categories = [];
        $persons = DB::table('users')
            ->where('pk_user_id', Auth::id())
            ->value('persons');
        $this->grocery_unit = (double)($ingredient['number_of_units'] / $number_of_servings) * $persons;
        $this->grocery_measurement = $ingredient['measurement_description'];

        // Just now
        $this->units = $ingredient['number_of_units'];
        $this->measurement = $ingredient['measurement_description'];

        $fat = Api::Ingredient($this->id);

        if (isset($fat['food_sub_categories'])) {
            $this->sub_categories = count($fat['food_sub_categories']) > 1 ? $fat['food_sub_categories']['food_sub_category'] : [$fat['food_sub_categories']['food_sub_category']];
        }

        $this->servings = isset(($serving = $fat['servings']['serving'])['serving_id']) ? [$serving] : $serving;

        if (in_array($ingredient['measurement_description'], ['large', 'medium', 'small', 'serving', 'g', 'ml', 'l', 'clove'])) {
            if ($ingredient['measurement_description'] === 'serving') {

            } else {
                $this->units = (double)($ingredient['number_of_units'] / $number_of_servings) * $persons;
            }
        } else {

        }
    }

    public function __invoke()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'unit' => $this->units,
            'measurement' => $this->measurement,
            'sub_categories' => $this->sub_categories,
        ];
    }

    /**
     * @return mixed
     */
    public function getServingId()
    {
        return $this->serving_id;
    }

    /**
     * @return mixed
     */
    public function getGroceryMeasurement()
    {
        return $this->grocery_measurement;
    }

    /**
     * @return mixed
     */
    public function getGroceryUnit()
    {
        return $this->grocery_unit;
    }

    public function hasSubCategory($category)
    {
        return in_array($category, $this->sub_categories);
    }

    /**
     * @return Integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return String
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return String
     */
    public function getMeasurement()
    {
        return $this->measurement;
    }

    /**
     * @return mixed
     */
    public function getUnits()
    {
        return $this->units;
    }

    /**
     * @return mixed
     */
    public function getSubCategories()
    {
        return $this->sub_categories;
    }

    /**
     * @return mixed
     */
    public function getServings()
    {
        return $this->servings;
    }
}