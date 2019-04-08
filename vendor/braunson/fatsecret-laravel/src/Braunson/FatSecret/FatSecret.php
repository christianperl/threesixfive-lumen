<?php

namespace Braunson\FatSecret;

use Carbon\Carbon;
use Config, Exception, App, Log;

class FatSecret
{
    static public $base = 'http://platform.fatsecret.com/rest/server.api?format=json&';

    /* Private Data */

    private $_consumerKey;
    private $_consumerSecret;

    /* Constructors */

    function __construct($consumerKey, $consumerSecret)
    {
        $this->_consumerKey = $consumerKey;
        $this->_consumerSecret = $consumerSecret;

        return $this;
    }

    /* Properties */

    function GetKey()
    {
        return $this->_consumerKey;
    }

    function SetKey($consumerKey)
    {
        $this->_consumerKey = $consumerKey;
    }

    function GetSecret()
    {
        return $this->_consumerSecret;
    }

    function SetSecret($consumerSecret)
    {
        $this->_consumerSecret = $consumerSecret;
    }

    /* Public Methods */

    /**
     * Search ingredients by phrase, page and max results
     *
     * @param  string $search_phrase The phrase you want to search for
     * @param  integer $page The page number of results you want to return (default 0)
     * @param  integer $maxresults The number of results you want returned (default 50)
     * @return json
     */
    public function searchIngredients($search_phrase, $page = 0, $maxresults = 50)
    {
        $url = static::$base . 'method=foods.search&include_sub_categories=true&page_number=' . $page . '&max_results=' . $maxresults . '&search_expression=' . $search_phrase;

        $oauth = new OAuthBase();

        $normalizedUrl;
        $normalizedRequestParameters;

        $signature = $oauth->GenerateSignature($url, $this->_consumerKey, $this->_consumerSecret, null, null, $normalizedUrl, $normalizedRequestParameters);
        $response = $this->GetQueryResponse($normalizedUrl, $normalizedRequestParameters . '&' . OAuthBase::$OAUTH_SIGNATURE . '=' . urlencode($signature));

        return $response;
    }

    /**
     * Search recipes by phrase, page and max results
     *
     * @param  string $search_phrase The phrase you want to search for
     * @param  integer $page The page number of results you want to return (default 0)
     * @param  integer $maxresults The number of results you want returned (default 50)
     * @return json
     */
    public function searchRecipes($search_phrase, $page = 0, $maxresults = 50, $recipe_type = false)
    {
        $url = static::$base . 'method=recipes.search&include_sub_categories=true&page_number=' . $page . '&max_results=' . $maxresults . '&search_expression=' . $search_phrase . '&recipe_type=' . $recipe_type;

        $oauth = new OAuthBase();

        $normalizedUrl;
        $normalizedRequestParameters;

        $signature = $oauth->GenerateSignature($url, $this->_consumerKey, $this->_consumerSecret, null, null, $normalizedUrl, $normalizedRequestParameters);
        $response = $this->GetQueryResponse($normalizedUrl, $normalizedRequestParameters . '&' . OAuthBase::$OAUTH_SIGNATURE . '=' . urlencode($signature));

        return $response;
    }

    /**
     * Reqtrieve an ingredient by ID
     *
     * @param  integer $ingredient_id The ingredient ID
     * @return json
     */
    function getIngredient($ingredient_id)
    {
        $url = static::$base . 'method=food.get&include_sub_categories=true&food_id=' . $ingredient_id;

        $oauth = new OAuthBase();

        $normalizedUrl;
        $normalizedRequestParameters;

        $signature = $oauth->GenerateSignature($url, $this->_consumerKey, $this->_consumerSecret, null, null, $normalizedUrl, $normalizedRequestParameters);
        $response = $this->GetQueryResponse($normalizedUrl, $normalizedRequestParameters . '&' . OAuthBase::$OAUTH_SIGNATURE . '=' . urlencode($signature));

        return $response;
    }

    /**
     * Reqtrieve a recipe by ID
     *
     * @param  integer $recipe_id The recipe ID
     * @return json
     */
    function getRecipe($recipe_id)
    {
        $url = static::$base . 'method=recipe.get&include_sub_categories=true&recipe_id=' . $recipe_id;

        $oauth = new OAuthBase();


        $normalizedUrl;
        $normalizedRequestParameters;

        $signature = $oauth->GenerateSignature($url, $this->_consumerKey, $this->_consumerSecret, null, null, $normalizedUrl, $normalizedRequestParameters);
        $response = $this->GetQueryResponse($normalizedUrl, $normalizedRequestParameters . '&' . OAuthBase::$OAUTH_SIGNATURE . '=' . urlencode($signature));

        return $response;
    }

    /* Private Methods */

    /**
     * Call the url and return the resonse
     *
     * @param string $requestUrl The url we want to call
     * @param array $postString The array of fields passed in the call
     */
    private function GetQueryResponse($requestUrl, $postString)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        curl_close($ch);

        $response = json_decode($response, true);

        $this->ErrorCheck($response);

        return $response;
    }

    /**
     * Checking for any errors, if so we throw a fatal Laravel error
     *
     * @param array $exception
     */
    private function ErrorCheck($exception)
    {
        if (isset($exception['error'])) {
            \Log::error($exception['error']['message']);
            $backtrace = debug_backtrace();
            throw new \ErrorException($exception['error']['message'], 0, $exception['error']['code'], __FILE__, $backtrace[0]['line']);
        }
    }
}

