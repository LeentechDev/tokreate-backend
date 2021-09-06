<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;

use App\TokenHistory;
use App\User;

class Edition extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $primaryKey = 'edition_id';
    protected $fillable = [
        'token_id', 'owner_id', 'current_price', 'edition_no', 'on_market'
    ];

    protected $with = ['owner'];

    protected $table = 'editions';

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

    public function history(){
        return $this->hasMany(TokenHistory::class, 'edition_id', 'edition_id');
    }

    public function owner(){
        return $this->belongsTo(User::class, 'owner_id', 'user_id');
    }
}
