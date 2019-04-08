<?php

namespace App;


class Recipe
{
    // Recipe ID
    private $id;

    // Recipe name
    private $name;

    // Recipe description
    private $description;

    // Recipe types (array)
    private $types;

    // Recipe categories (array)
    private $categories;

    // The number of servings the recipe is intended for
    private $serving;

    // Recipe ingredients (array)
    private $ingredients;

    // The directions/steps involved in creating the recipe
    private $direction;

    // Nutrient values for each recipe
    private $serving_sizes;

    // The time in minutes to prepare the recipe
    private $prep_time;

    // The time in minutes to cook the recipe
    private $cook_time;

    // The overall average rating of a recipe
    private $rating;

    const ALL_ALLERGENS = [
        'A' => ['Bread', 'Muesli', 'Cereal'],
        'B' => ['Crab', 'Lobster', 'Shrimp'],
        'C' => ['Eggs', 'Egg Whites', 'Fried Eggs'],
        'D' => ['Fish', 'Flounder', 'Catfish', 'Crawfish', 'Sardines'],
        'E' => ['Peanuts'],
        'F' => ['Soy Nuts'],
        'G' => ['Milk', 'Buttermilk', 'Cheese', 'Butter', 'Margarine', 'Yogurt'],
        'H' => ['Almonds', 'Nuts', 'Mixed Nuts', 'Cashews', 'Chestnuts'],
        'I' => ['Celery'],
        'K' => ['Mustard'],
        'L' => ['Seeds'],
        'M' => ['Wine', 'White Wine', 'Red Wine'],
        'N' => ['Seeds'],
        'O' => ['Oysters']
    ];

    public function __construct($recipe, $loadIngredients = true)
    {
        $this->id = $recipe['recipe_id'];
        $this->name = $recipe['recipe_name'];
        $this->description = $recipe['recipe_description'];
        $this->types = $recipe['recipe_types']['recipe_type'];
        $this->categories = $recipe['recipe_categories']['recipe_category'] ?? [];
        $this->serving = $recipe['number_of_servings'];
        $this->direction = $recipe['directions']['direction'];
        $this->prep_time = $recipe['preparation_time_min'] ?? 0;
        $this->cook_time = $recipe['cooking_time_min'] ?? 0;
        $this->rating = $recipe['rating'] ?? 0;


        $this->serving_sizes = [];

        if (isset( $recipe['serving_sizes']['serving'])) {
            foreach (['calories', 'carbohydrate', 'fat', 'protein', 'sugar'] as $nutrition) {
                $this->serving_sizes[$nutrition] = $recipe['serving_sizes']['serving'][$nutrition];
            }
        }

        $this->ingredients = [];

        if ($loadIngredients) {
            if (isset($recipe['ingredients']['ingredient']['food_id'])) {
                $this->ingredients = [new Ingredient($recipe['ingredients']['ingredient'], $this->serving)];
            } else {
                foreach ($recipe['ingredients']['ingredient'] as $ingredient) {
                    $this->ingredients[] = new Ingredient($ingredient, $this->serving);
                }
            }
        }
    }

    public function __invoke()
    {
        if (!($this->ingredients === [])) {
            $ingredients = [];
            foreach ($this->ingredients as $ingredient) {
                $ingredients[] = $ingredient();
            }

            return [
                'id' => (int)$this->id,
                'name' => $this->name,
                'description' => $this->description,
                'ingredients' => $ingredients,
                'directions' => $this->direction,
                'nutrition' => $this->serving_sizes
            ];
        } else {
            return $this->name;
        }
    }

    public function hasAllergen($allergen)
    {
        foreach ($this->ingredients as $ingredient) {
            foreach (Recipe::ALL_ALLERGENS[$allergen] as $subCategory) {
                if ($ingredient->hasSubCategory($subCategory)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function hasNoGo($nogo)
    {
        foreach ($this->ingredients as $ingredient) {
            if ($ingredient->hasSubCategory($nogo)) {
                return true;
            }
        }

        return false;
    }

    public function hasDiet($diet)
    {
        foreach ($this->categories as $category) {
            if ($category === $diet) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function getServingSizes()
    {
        return $this->serving_sizes;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @return mixed
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @return mixed
     */
    public function getServing()
    {
        return $this->serving;
    }

    /**
     * @return mixed
     */
    public function getIngredients()
    {
        return $this->ingredients;
    }

    /**
     * @return mixed
     */
    public function getDirection()
    {
        return $this->direction;
    }
}
