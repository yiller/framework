<?php namespace YitOS\_G\Model;

use YitOS\_G\Database\Eloquent\Model;

class UserProfile extends Model {
  
  protected $table = 'user_profile';
  
  protected $model = 'profile';
  
  public function residenceAddress() {
    return $this->belongsTo('YitOS\_G\Model\Address', 'residence_address');
  }
  
}
