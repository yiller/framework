<?php namespace YitOS\Model;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends Model implements AuthenticatableContract, CanResetPasswordContract {
  use Authenticatable, CanResetPassword;
  
  protected $table = 'users';
  
  protected $fillable = ['username', 'email', 'phone', 'password', 'active'];
  
  protected $hidden = ['password', 'remember_token'];
  
}
