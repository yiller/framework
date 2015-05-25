<?php namespace YitOS\_G\Model;

use Illuminate\Auth\Authenticatable;
use YitOS\_G\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends Model implements AuthenticatableContract, CanResetPasswordContract {
  use Authenticatable, CanResetPassword;
  
  protected $hidden = ['password', 'remember_token'];
  
  public function getEnterpriseAttribute() {
    $resume = UserResume::where('user_id', $this->id)->where('to', '0000-00-00')->orderBy('from', 'desc')->first();
    return $resume ? $resume->enterprise : null;
  }
  
  public function getAvatarAttribute() {
    $avatar = 'upload/users/0/avatar.png';
    $this->profile && $this->profile->photo && ($avatar = $this->profile->photo);
    return asset($avatar);
  }
  
  /*public function getAttribute($key) {
    $inAttributes = array_key_exists($key, $this->attributes);
    if ($inAttributes || $this->hasGetMutator($key)) return $this->getAttributeValue($key);
    if (array_key_exists($key, $this->relations)) return $this->relations[$key];
    try { return $this->getRelationshipFromMethod($key); }
    catch(BadMethodCallException $e) {}
    if ($this->profile && $this->profile->{$key}) return $this->profile->{$key}; 
  }*/
  
}
