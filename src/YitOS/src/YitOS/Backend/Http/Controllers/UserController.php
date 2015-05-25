<?php namespace YitOS\Backend\Http\Controllers;

use YitOS\Foundation\Scaffold\Traits\DataTable;
use YitOS\Model\User;

class UserController extends BackendController {
  use DataTable;
  
  public function index() {
    dd(User::getModel());
    return $this->table();
  }
  
}
