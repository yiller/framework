<?php namespace YitOS\Backend\Http\Controllers;

use YitOS\Routing\Controller as BaseController;

abstract class Controller extends BaseController {
  
  public function __construct() {
    $this->middleware('acl');
  }
  
}
