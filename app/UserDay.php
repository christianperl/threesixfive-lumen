<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserDay extends Model
{
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'pk_fk_user_id', 'weekday', 'breakfast', 'lunch', 'main_dish', 'snack',
    ];

    protected $primaryKey = 'pk_fk_user_id';

    public function user() {
        return $this->belongsTo('App\User');
    }
}
