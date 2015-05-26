<?php namespace YitOS\_G\Model;

use YitOS\_G\Database\Eloquent\Model;

class Enterprise extends Model {
  
  public function founder() {
    return $this->belongsTo('YitOS\Model\User', 'user_id');
  }

}
