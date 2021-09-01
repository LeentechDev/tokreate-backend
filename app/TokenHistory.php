<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;

use App\Edition;
use App\User_profile;

class TokenHistory extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $fillable = [
        'token_id', 'edition_id', 'price', 'type', 'buyer_id', 'seller_id'
    ];

    protected $table = 'token_history';

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    protected $with = ['edition_details', 'owner', 'collector'];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function owner()
    {
        return $this->belongsTo(User_profile::class, 'seller_id', 'user_id');
    }

    public function collector()
    {
        return $this->belongsTo(User_profile::class, 'buyer_id', 'user_id');
    }


    public function token()
    {
        return $this->belongsTo(Token::class, 'token_id', 'token_id');
    }

    public function edition_details()
    {
        return $this->belongsTo(Edition::class, 'edition_id', 'edition_id');
    }
}
