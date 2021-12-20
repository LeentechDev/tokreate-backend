<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;

use Illuminate\Notifications\Notifiable;
use App\Traits\MustVerifyEmail;

use App\User_profile;
use App\Wallet;
use App\Token;
use App\Fund;
use App\Payout;
use App\Edition;
use App\Notifications;


class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable, MustVerifyEmail, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    const CREATED_AT = 'user_created_at';
    const UPDATED_AT = 'user_last_login';
    protected $primaryKey = 'user_id';
    protected $fillable = [
        'user_name', 'user_bio', 'user_email', 'user_role_id', 'email_verified_at', 'user_last_login', 'user_status', 'user_notification_settings'
    ];

    // protected $with = ['profile'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    protected $with = [
        'profile',
        'payout_details'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function profile()
    {
        return $this->belongsTo(User_profile::class, 'user_id', 'user_id');
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class, 'user_id', 'user_id');
    }

    public function payout_details()
    {
        return $this->hasOne(Payout::class, 'user_id', 'user_id');
    }

    public function token_editions()
    {
        return $this->hasMany(Edition::class, 'owner_id', 'user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notifications::class, 'notification_to', 'user_id');
    }

    public function minting_requests()
    {
        return $this->hasMany(Token::class, 'user_id', 'user_id');
    }

    public function fund()
    {
        return $this->hasOne(Fund::class, 'user_id', 'user_id');
    }
}
