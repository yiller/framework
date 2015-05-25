<?php namespace YitOS\_G\Model;

use YitOS\_G\Database\Eloquent\Model;

class UserProfile extends Model {
  
  protected $model = 'profile';
  
  public function residenceAddress() {
    return $this->belongsTo('YitOS\Model\Address', 'residence_address');
  }
  
}
