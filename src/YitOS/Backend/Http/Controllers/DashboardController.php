<?php namespace YitOS\Backend\Http\Controllers;

class DashboardController extends BackendController {
  
  public function index() {
    return view('yitos.backend.dashboard');
  }
  
}
