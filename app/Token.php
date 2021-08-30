<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;

use App\Transaction;
use App\Edition;
use App\TokenHistory;
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

    protected $with = ['owner', 'creator', 'editions'];
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

    public function editions(){
        return $this->hasMany(Edition::class, 'token_id', 'token_id');
    }

    public function history(){
        return $this->hasMany(TokenHistory::class, 'token_id', 'token_id');
    }


    public function owner(){
        return $this->belongsTo(User::class, 'token_owner', 'user_id');   
    }

    public function creator(){
        return $this->belongsTo(User::class, 'user_id', 'user_id');   
    }
}
