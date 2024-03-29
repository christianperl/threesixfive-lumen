<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Grocery extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'fk_user_id', 'serving', 'measurement', 'checked', 'generated', 'day'
    ];
    
    protected $hidden = [
        'created_at', 'updated_at'
    ];
    
    protected $primaryKey = 'pk_grocery_id';
    
    public function user() {
        return $this->belongsTo('App\User');
    }
}
