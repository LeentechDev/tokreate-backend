<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class Gas_fee extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    const CREATED_AT = 'gas_fee_created_at';
    const UPDATED_AT = 'gas_fee_updated_at';
    protected $primaryKey = 'gas_fee_id';
    protected $fillable = [
        'gas_fee_id', 'gas_fee_name','gas_fee_amount', 'gas_fee_updated_by', 'gas_fee_commission_rate'
    ];
    protected $table = 'gas_fees';


    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
    ];
}
