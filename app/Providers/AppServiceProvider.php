<?php

namespace App\Providers;

use App\Flows\Common\MessageResolver;
use App\Flows\Transitional\Handlers\EsperandoTipoStepHandler;
use App\Flows\Transitional\Handlers\FallbackStepHandler;
use App\Flows\Validators\MenuSelectionValidator;
use App\Services\Conversation\ConversationFlowResolver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ConversationFlowResolver::class, function ($app) {
            return new ConversationFlowResolver([
                $app->make(EsperandoTipoStepHandler::class),
                $app->make(FallbackStepHandler::class),
            ]);
        });

        $this->app->bind(EsperandoTipoStepHandler::class, function ($app) {
            return new EsperandoTipoStepHandler(
                $app->make(MenuSelectionValidator::class),
            );
        });

        $this->app->singleton(MessageResolver::class, function ($app) {
            return new MessageResolver(
                $app->make('translator'),
                $app->make('view'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
