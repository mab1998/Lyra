<?php

namespace SertxuDeveloper\Lyra;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Routing\Router;
//use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

use SertxuDeveloper\Lyra\Http\Middleware\LyraAdminMiddleware;
use SertxuDeveloper\Lyra\Facades\Lyra as LyraFacade;
use SertxuDeveloper\Lyra\Http\Resources\Users;
use SertxuDeveloper\Lyra\Models\MenuItems;
use SertxuDeveloper\Lyra\Policy\MenuItemsPolicy;

class LyraServiceProvider extends ServiceProvider {

  /**
   * The policy mappings for the application.
   *
   * @var array
   */
  protected $policies = [
    MenuItems::class => MenuItemsPolicy::class,
  ];

  /**
   * Bootstrap services.
   *
   * @param \Illuminate\Routing\Router $router
   * @return void
   */
  public function boot(Router $router) {
    $router->aliasMiddleware('lyra', LyraAdminMiddleware::class);
    $this->loadViewsFrom(__DIR__ . '/../resources/views', 'lyra');

    $this->registerConfigs();
    $this->registerPublishable();
    $this->registerResources();

    $this->loadTranslationsFrom(__DIR__ . '/../publishable/lang/', 'lyra');

    if ($this->app->runningInConsole()) {
      $this->registerCommands();
    }

    Lyra::routes();
  }

  /**
   * Register services.
   *
   * @return void
   */
  public function register() {
    $loader = AliasLoader::getInstance();
    $loader->alias('Lyra', LyraFacade::class);

    $this->app->singleton('lyra', function () {
      return new Lyra();
    });

    $this->loadHelpers();
    $this->registerPolicies();
  }

  private function registerConfigs() {
    $this->mergeConfigFrom(dirname(__DIR__) . '/publishable/config/lyra.php', 'lyra');
    $this->mergeConfigFrom(dirname(__DIR__) . '/publishable/config/auth/guards.php', 'auth.guards.lyra');
    $this->mergeConfigFrom(dirname(__DIR__) . '/publishable/config/auth/passwords.php', 'auth.passwords.lyra');
    $this->mergeConfigFrom(dirname(__DIR__) . '/publishable/config/auth/providers.php', 'auth.providers.lyra');
  }

  private function registerPublishable() {
    $packagePath = __DIR__ . '/..';

    $publishable = [
      'lyra-assets' => [
        "{$packagePath}/publishable/assets" => public_path(config('lyra.assets_path')),
      ],
      "lyra-views" => [
        "{$packagePath}/resources/views" => base_path('resources/views/vendor/lyra')
      ],
      "lyra-config" => [
        "{$packagePath}/publishable/config/lyra.php" => config_path('lyra.php')
      ],
      'lyra-migrations' => [
        "{$packagePath}/publishable/database/migrations/" => database_path('migrations'),
      ],
      'lyra-seeds' => [
        "{$packagePath}/publishable/database/seeds/" => database_path('seeds'),
      ],
      'lyra-demo_content' => [
        "{$packagePath}/publishable/demo_content/" => storage_path('app/public'),
      ],
    ];

    foreach ($publishable as $group => $paths) {
      $this->publishes($paths, $group);
    }
  }

  /**
   * Register terminal commands
   */
  private function registerCommands() {
    $this->commands([
      Commands\InstallCommand::class,
    ]);
  }

  private function registerResources() {

    Lyra::resources([
      "users" => Users::class
    ]);
  }

  /**
   * Get dynamically the Helpers from the /src/Helpers directory and require_once each file.
   */
  protected function loadHelpers() {
    foreach (glob(__DIR__ . '/Helpers/*.php') as $filename) {
      require_once $filename;
    }
  }


}
