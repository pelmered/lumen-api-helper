<?php

namespace pelmered\APIHelper;

use Illuminate\Support\ServiceProvider;
use AngryCreative\WCImporter\WooCommerceImporter;

class APIHelperServiceProvider extends ServiceProvider
{
    //const CONFIG_KEY = 'api-helper';
    const CONFIG_KEY = 'media';


    /**
     * @inheritdoc
     */
    public function register()
    {
        $this->app->configure(self::CONFIG_KEY);
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
