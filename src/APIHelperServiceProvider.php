<?php

namespace pelmered\APIHelper;

use Illuminate\Support\ServiceProvider;
use AngryCreative\WCImporter\WooCommerceImporter;
use pelmered\APIHelper\Traits\ControllerResponses;

class APIHelperServiceProvider extends ServiceProvider
{
    use ControllerResponses;

    //const CONFIG_KEY = 'api-helper';
    const CONFIG_KEY = 'media';


    /**
     * @inheritdoc
     */
    public function register()
    {
        $this->app->configure(self::CONFIG_KEY);

        $request = $this->app->make('request');

        if ($request->isMethod('OPTIONS')) {
            $this->app->options($request->path(), function() {
                return $this->setStatusCode(200)->response(['status' => 'ok']);
            });
        }

    }

    /*
     * Boot the publishing config
     */
    public function boot()
    {
        // Requires irazasyed/larasupport package to work with vendor:publish command
        $this->publishes([
            dirname(__DIR__).'/config/media.php' => config_path('media.php')
        ], 'config');

        $this->publishes([
            dirname(__DIR__).'/migrations/' => database_path('migrations')
        ], 'migrations');
    }

}
