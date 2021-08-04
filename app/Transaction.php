<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;

use App\Token;

class Transaction extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    const CREATED_AT = 'transaction_created_at';
    const UPDATED_AT = 'transaction_updated_at';
    protected $primaryKey = 'transaction_id';
    protected $fillable = [
        'user_id','transaction_token_id', 'transaction_type','transaction_payment_method', 'transaction_details','transaction_service_fee', 'transaction_gas_fee', 'transaction_allowance_fee', 'transaction_token_price', 'transaction_grand_total', 'transaction_status', 'transaction_urgency'
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

    public function token(){
        return $this->belongsTo(Token::class, 'transaction_token_id', 'token_id'); 
    }
}
