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
		View::composer('yitos.backend.partial.page.TKD', 'YitOS\Backend\Http\ViewComposers\TKDComposer');
    View::composer('yitos.backend.partial.sidebar.menu', 'YitOS\Backend\Http\ViewComposers\MenusComposer');
    View::composer(['yitos.backend.partial.page.breadcrumbs', 'yitos.backend.partial.page.header'], 'YitOS\Backend\Http\ViewComposers\BreadcrumbsComposer');
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
