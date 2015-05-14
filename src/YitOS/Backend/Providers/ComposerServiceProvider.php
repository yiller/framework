<?php namespace YitOS\Backend\Providers;

use View;
use Illuminate\Support\ServiceProvider;

class ComposerServiceProvider extends ServiceProvider {

	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		//View::composer('_partial.page.TKD', 'WebShell\Http\ViewComposers\TKDComposer');
    //View::composer('_partial.page.breadcrumb', 'WebShell\Http\ViewComposers\BreadcrumbComposer');
    View::composer('backend._partial.sidebar.menu', 'YitOS\Backend\Http\ViewComposers\MenusComposer');
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}

}
