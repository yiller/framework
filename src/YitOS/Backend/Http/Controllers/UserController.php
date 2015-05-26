<?php namespace YitOS\Backend\Http\Controllers;

use YitOS\_G\Model\User;

class UserController extends Controller {
  
  public function index() {
    dd(app('request')->user()->profile);
    return $this->table();
  }
  
}
