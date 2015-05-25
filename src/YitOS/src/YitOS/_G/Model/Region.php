<?php namespace YitOS\_G\Model;

use YitOS\_G\Database\Eloquent\Model;

class Region extends Model {
  
  protected $fillable = ['id', 'name', 'level', 'areacode', 'parent_id', 'user_id'];

}
