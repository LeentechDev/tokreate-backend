<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class User_profile extends Model implements
    AuthenticatableContract,
    AuthorizableContract
{
    use Authenticatable, Authorizable;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    const CREATED_AT = 'user_profile_created_at';
    const UPDATED_AT = 'user_profile_updated_at';
    protected $primaryKey = 'user_profile_id';
    protected $fillable = [
        'user_id', 'user_profile_full_name', 'user_profile_birthday','user_profile_contactno','user_profile_address','user_profile_avatar', 'user_profile_created_at', 'user_profile_updated_at'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
    ];

}
