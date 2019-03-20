<?php

namespace App\Jobs;


use FatSecret;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Predis\Client;

class Recache extends Job
{
    use InteractsWithQueue, SerializesModels;

    protected $redis;

    protected $id;

    protected $type;

    public $tries=1;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id, $type)
    {
        $this->redis = new Client();
        $this->$id = $id;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::debug('test-2');
        if ($this->type === 'r') {
            $recipe = FatSecret::getRecipe($this->id)['recipe'];
            Log::debug($recipe);
            $this->redis->set($this->id, $recipe, 14400);
        } else {
            $ingredient = FatSecret::getIngredient($this->id)['food'];
            Log::debug($ingredient);
            $this->redis->set($this->id, $ingredient, 14400);
        }
    }
}
