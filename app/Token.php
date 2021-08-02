<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;

use App\Transaction;
use App\User;

class Token extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    const CREATED_AT = 'token_created_at';
    const UPDATED_AT = 'token_updated_at';
    protected $primaryKey = 'token_id';
    protected $fillable = [
        'user_id', 'token_collectible','token_collectible_count','token_title','token_description','token_starting_price','token_royalty','token_properties','token_filename','token_saletype','token_status', 'token_owner', 'token_creator'
    ];

    protected $with = ['owner','creator'];
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
    ];
    public function getJWTIdentifier(){
        return $this->getKey();
    }
    public function getJWTCustomClaims(){
        return [];
    }

    public function transactions(){
        return $this->hasMany(Transaction::class, 'transaction_token_id', 'token_id');   
    }

    public function owner(){
        return $this->belongsTo(User::class, 'token_owner', 'user_id');   
    }

    public function creator(){
        return $this->belongsTo(User::class, 'token_creator', 'user_id');   
    }
}
