<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;


class Payout extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $primaryKey = 'id';
    protected $fillable = [
        'user_id',
        'payout_first_name',
        'payout_middle_name',
        'payout_last_name',
        'payout_proc_id',
        'payout_proc_details',
        'payout_email_address',
        'payout_mobile_no',
        'payout_birth_date',
        'payout_street1',
        'payout_street2',
        'payout_barangay',
        'payout_city',
        'payout_province',
        'payout_country',
        'payout_currency',
        'payout_nationality'
    ];

    protected $table = "payout_details";

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }
}
