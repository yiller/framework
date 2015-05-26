<?php namespace YitOS\Backend\Http\Controllers;

class DashboardController extends Controller {
  
  public function index() {
    return view('yitos.backend.dashboard');
  }
  
}
