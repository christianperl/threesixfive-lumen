<?php

namespace App\Services;

use App\Allergen;
use App\Category;
use App\Diet;
use App\Nogo;
use App\Recipe;
use App\User;
use App\UserDay;
use App\UserDiet;
use Carbon\Carbon;
use App\Plan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Algorithm
{
    private $recipe_types;
    private $diets;
    private $allergens;
    private $categories;
    private $plan;
    private $persons;

    public function __construct($plan = null, $allergens = null, $categories = null, $diets = null, $persons = null)
    {
        if ($allergens === null) {
            $allergens = DB::table('allergens')
                ->join('nogos', 'pk_allergen_id', '=', 'fk_object')
                ->where([
                    ['fk_n_user_id', Auth::id()],
                    ['which', 'allergen']
                ])
                ->select('description')
                ->get();

            foreach ($allergens as $allergen) {
                $this->allergens[] = $allergen->description;
            }
        } else {
            $this->allergens = $allergens;
        }

        if ($categories === null) {
            $categories = DB::table('categories')
                ->join('nogos', 'pk_category_id', '=', 'fk_object')
                ->where([
                    ['fk_n_user_id', Auth::id()],
                    ['which', 'category']
                ])
                ->select('name')
                ->get();

            foreach ($categories as $category) {
                $this->categories[] = $category->name;
            }
        } else {
            $this->categories = $categories;
        }

        if ($diets === null) {
            $diets = DB::table('diets')
                ->join('user_diets', 'pk_diet_id', '=', 'pk_fk_u_diets_id')
                ->where('pk_fk_d_user_id', Auth::id())
                ->select('description')
                ->get();

            foreach ($diets as $diet) {
                $this->diets[] = $diet->description;
            }
        } else {
            $this->diets = $diets;
        }

        if ($persons === null) {
            $this->persons = DB::table('users')
                ->where('pk_user_id', Auth::id())
                ->value('persons');
        } else {
            $this->persons = $persons;
        }

        if ($plan === null) {
            $select = DB::table('user_days')
                ->where('pk_fk_user_id', Auth::id())
                ->get([
                    'weekday',
                    'breakfast',
                    'lunch',
                    'main_dish',
                    'snack'
                ]);

            $plan = [];

            foreach ($select as $day) {
                $meals = [];
                foreach (['breakfast', 'lunch', 'main_dish', 'snack'] as $type) {
                    if ($day->$type) {
                        $meals[] = $type === 'main_dish' ? 'main dish' : $type;
                    }
                }

                $plan[] = [
                    'weekday' => $day->weekday,
                    'meals' => $meals
                ];
            }
        } else {
            $this->plan = $plan;
        }

        $this->recipe_types = [];

        foreach ($plan as $weekday) {
            foreach ($weekday['meals'] as $meal) {
                $this->recipe_types[$meal]['days'][] = $weekday['weekday'];
            }
        }

        foreach ($this->recipe_types as $type => $type_info) {
            $this->recipe_types[$type]['totalResults'] = (int)Api::Search(0, 1, $type)['total_results'];
        }
    }

    public function generateWeek()
    {
        $weekPlan = null;
        $visitedPages = [];
        $possibleRecipes = [];

        foreach ($this->recipe_types as $type => $type_info) {
            for ($i = 0, $num = 0, $new_page = true, $days = count($type_info['days']); $i < $days; $i++, $num++) {
                if ($new_page) {
                    do {
                        $current_page = random_int(1, (int)($type_info['totalResults'] / 50));
                    } while (in_array($current_page, $visitedPages, true));

                    $recipes = Api::Search($current_page, 50, $type)['recipe'];
                    $numbers = range(0, count($recipes) - 1);
                    shuffle($numbers);
                    $new_page = false;
                }

                if ($i === count($recipes) - 1) {
                    $new_page = true;
                } elseif ($recipe = $this->checkRecipe((int)$recipes[$numbers[$i]]['recipe_id'], $this->allergens, $this->categories, $this->diets)) {
                    $weekPlan[$type_info['days'][$i]][$type] = $recipe();
                } else {
                    $i--;
                }

                sleep(0.75);
            }
        }

        return $weekPlan;
    }

    public function generateOneType($type)
    {
        $result = null;
        $visitedPages = [];
        $possibleRecipes = [];
        $totalResults = $this->recipe_types[$type]['totalResults'];

        for ($i = 0, $new_page = true; $i < $totalResults; $i++) {
            if ($new_page) {
                do {
                    $current_page = random_int(1, (int)($totalResults / 50));
                } while (in_array($current_page, $visitedPages, true));

                $recipes = Api::Search($current_page, 50, $type)['recipe'];
                $numbers = range(0, count($recipes) - 1);
                shuffle($numbers);
                $new_page = false;
            }

            if ($i === count($recipes) - 1) {
                $new_page = true;
                $i = 0;
            } elseif ($recipe = $this->checkRecipe((int)$recipes[$numbers[$i]]['recipe_id'], $this->allergens, $this->categories, $this->diets)) {
                $result = $recipe->getId();
                break;
            } else {
                $i--;
            }

            sleep(0.75);
        }

        return $result;
    }

    public function saveWeek($weekPlan, $year, $weekNumber)
    {
        $date = Carbon::now();
        $date->setISODate($year, $weekNumber);

        $week = [
            ['Monday', $date->copy()->startOfWeek()->format('Y-m-d')],
            ['Tuesday', $date->copy()->startOfWeek()->addDay()->format('Y-m-d')],
            ['Wednesday', $date->copy()->startOfWeek()->addDays(2)->format('Y-m-d')],
            ['Thursday', $date->copy()->startOfWeek()->addDays(3)->format('Y-m-d')],
            ['Friday', $date->copy()->startOfWeek()->addDays(4)->format('Y-m-d')],
            ['Saturday', $date->copy()->startOfWeek()->addDays(5)->format('Y-m-d')],
            ['Sunday', $date->copy()->endOfWeek()->format('Y-m-d')]
        ];

        foreach ($week as $day) {
            if (array_key_exists($day[0], $weekPlan)) {
                $input = [
                    'pk_date' => $day[1],
                    'pk_fk_user_id' => (int)Auth::id(),
                    'weekday' => $day[0],
                ];

                foreach (['breakfast', 'lunch', ['main_dish', 'main dish'], 'snack'] as $type) {
                    if (is_array($type) ? isset($weekPlan[$day[0]][$type[1]]) : isset($weekPlan[$day[0]][$type])) {
                        if (is_array($type)) {
                            $input[$type[0]] = (int)$weekPlan[$day[0]][$type[1]]['id'];
                        } else {
                            $input[$type] = (int)$weekPlan[$day[0]][$type]['id'];
                        }
                    } else {
                        if (is_array($type)) {
                            $input[$type[0]] = null;
                        } else {
                            $input[$type] = null;
                        }
                    }
                }

                Plan::create($input);
            } else {
                Plan::create([
                    'pk_date' => $day[1],
                    'pk_fk_user_id' => (int)Auth::id(),
                    'weekday' => $day[0],
                    'breakfast' => null,
                    'lunch' => null,
                    'main_dish' => null,
                    'snack' => null
                ]);
            }
        }

        return true;
    }

    public function saveUserPreferences()
    {
        // Persons
        $user = User::findOrFail(Auth::id());
        $user->update(['persons' => $this->persons]);

        // Diets
        foreach ($this->diets as $diet) {
            $diet_id = Diet::where('description', $diet)->value('pk_diet_id');

            UserDiet::create([
                'pk_fk_d_user_id' => Auth::id(),
                'pk_fk_u_diets_id' => $diet_id
            ]);
        }

        // Allergens
        foreach ($this->allergens as $allergen) {
            $allergen_id = Allergen::where('description', $allergen)->value('pk_allergen_id');

            Nogo::create([
                'fk_n_user_id' => Auth::id(),
                'fk_object' => $allergen_id,
                'which' => 'allergen'
            ]);
        }

        // NoGos
        foreach ($this->categories as $category) {
            $category_id = Category::where('name', $category)->value('pk_category_id');

            Nogo::create([
                'fk_n_user_id' => Auth::id(),
                'fk_object' => $category_id,
                'which' => 'category'
            ]);
        }

        // Plan
        foreach ($this->plan as $day) {
            UserDay::create([
                'pk_fk_user_id' => Auth::id(),
                'weekday' => $day['weekday'],
                'breakfast' => in_array('breakfast', $day['meals'], true) ? true : false,
                'lunch' => in_array('lunch', $day['meals'], true) ? true : false,
                'main_dish' => in_array('main dish', $day['meals'], true) ? true : false,
                'snack' => in_array('snack', $day['meals'], true) ? true : false,
            ]);
        }

        return true;
    }

    private function checkRecipe($recipe_id, $allergens, $categories, $diets)
    {
        $recipe = new Recipe(Api::Recipe($recipe_id), true);

        // Check allergens
        foreach ($allergens as $allergen) {
            if ($recipe->hasAllergen(Allergen::where('description', $allergen)->value('shortcut'))) {
                return false;
            }
        }

        // Check categories
        foreach ($categories as $category) {
            if ($recipe->hasNoGo($category)) {
                return false;
            }
        }

        // Check diets
        foreach ($diets as $diet) {
            if ($recipe->hasDiet($diet)) {
                return false;
            }
        }

        return $recipe;
    }

    public function checkHistory($recipe_id)
    {
        $today = Carbon::today()->format('Y-m-d');
        $pastWeek = Carbon::today()->subWeek()->format('Y-m-d');

        $select = DB::table('plans')
            ->where('pk_fk_user_id', '=', Auth::id())
            ->where('pk_date', '>=', $today)
            ->where('pk_date', '<=', $pastWeek)->get();

        foreach ($select as $item) {
            if ($item->beakfast === $recipe_id | $item->lunch === $recipe_id | $item->main_dish === $recipe_id | $item->snack === $recipe_id) {
                return false;
            }
        }

        return true;
    }
}