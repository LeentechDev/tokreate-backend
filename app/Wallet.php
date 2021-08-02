<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;

use App\User_profile;
use App\User;

class Wallet extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    const CREATED_AT = 'wallet_created_at';
    const UPDATED_AT = 'wallet_updated_at';
    protected $primaryKey = 'wallet_id';
    protected $fillable = [
        'user_id', 'wallet_address','wallet_status'
    ];

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

    public function profile(){
        return $this->belongsTo(User_profile::class, 'user_id', 'user_id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
   

}
