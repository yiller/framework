<?php namespace YitOS\_G\Database\Eloquent;

use Illuminate\Database\Eloquent\Model as BaseModel;

class DefModelColumn extends BaseModel {

	protected $table = 'def_model_columns';
  
  protected $fillable = ['name', 'label', 'rules', 'user_id'];

}
