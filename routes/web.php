<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api'], function () use ($router) {

    // Only for test purposes
    $router->get('test', ['uses' => 'ExampleController@test']);


    // Get all users with http://localhost:8000/api/users
    $router->get('users', ['uses' => 'UsersController@showAllUsers']);

    // Get user who is requesting with http://localhost:8000/api/users
    $router->get('user', ['uses' => 'UsersController@showOneUser']);

    // Login with http://localhost:8000/api/login
    $router->post('user/login', ['uses' => 'UsersController@login']);

    // Logout with http://localhost:8000/api/login
    $router->post('user/logout', ['uses' => 'UsersController@logout']);

    // Create user with http://localhost:8000/api/users
    $router->post('user/register', ['uses' => 'UsersController@create']);

    // Delete user with http://localhost:8000/api/users/id
    $router->delete('user', ['uses' => 'UsersController@delete']);

    // Update user with http://localhost:8000/api/users/id
    $router->put('user', ['uses' => 'UsersController@update']);

    // Get preferences of one specific user with http://localhost:8000/api/user/preference
    $router->get('user/preference', ['uses' => 'AlgorithmController@getUserPreferences']);

    // Get preferences of one specific user with http://localhost:8000/api/user/preference
    $router->put('user/preference', ['uses' => 'AlgorithmController@changeUserPreferences']);


    // Get menu (week) from one specific user (will be generated if not generated yet) with http://localhost:8000/api/menu/userid
    $router->get('week/{year}/{week}', ['uses' => 'MenuController@getMenuWeek']);

    // Get menu (day) from one specific user with http://localhost:8000/api/menu/userid
    $router->get('day/{date}', ['uses' => 'MenuController@getMenuDay']);

    // Generate first menu for one specific user with http://localhost:8000/api/algorithm/generate/userid
    $router->post('form', ['uses' => 'AlgorithmController@createAlgorithm']);

    // Regenerate one specific
    $router->post('regen', ['uses' => 'AlgorithmController@regenerateType']);


    // Create grocery list for one specific user with http://localhost:8000/api/grocerylist
    $router->post('groceries', ['uses' => 'GroceryListController@createIndividualGroceryList']);

    // Get grocery list (not checked) from one specific user with http://localhost:8000/api/grocerylist/userid
    $router->get('groceries', ['uses' => 'GroceryListController@getCurrentGroceryList']);

    // Get grocery list (checked) from one specific user with http://localhost:8000/api/grocerylist/userid
    $router->get('groceries/checked', ['uses' => 'GroceryListController@getCurrentGroceryList']);

    // Get grocery list from one specific user with http://localhost:8000/api/grocerylist/userid
    $router->get('groceries/next', ['uses' => 'GroceryListController@getNextGroceryList']);


    // Generate Pdf file of one week for one user with http://localhost:8000/api/pdf/userid/week
    $router->get('pdf/{year}/{week}', ['uses' => 'PdfController@generateWeekPlan']);


    // Get all allergens with http://localhost:8000/api/allergens
    $router->get('allergens', ['uses' => 'AllergensController@getAllAllergens']);

    // Get one allergens with http://localhost:8000/api/allergens/id
    $router->get('allergens/{id}', ['uses' => 'AllergensController@getAllergen']);

    // Get all categories with http://localhost:8000/api/categories
    $router->get('categories', ['uses' => 'CategoryController@getAllCategories']);

    // Get one categories with http://localhost:8000/api/categories/id
    $router->get('categories/{id}', ['uses' => 'CategoryController@getCategory']);

    // Get all diets with http://localhost:8000/api/diets
    $router->get('diets', ['uses' => 'DietController@getAllDiets']);

    // Get one diets with http://localhost:8000/api/diets/id
    $router->get('diets/{id}', ['uses' => 'DietController@getDiet']);

});
