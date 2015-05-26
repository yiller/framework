<?php namespace YitOS\Backend\Http\Controllers;

abstract class BackendController extends Controller {
  
  public function __construct() {
    parent::__construct();
    
    $this->middleware('company');
  }
  
}
