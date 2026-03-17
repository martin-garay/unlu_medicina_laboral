<?php

namespace App\Providers;

use App\Flows\Common\MessageResolver;
use App\Flows\Handlers\MainMenuStepHandler;
use App\Flows\Identification\Handlers\IdentificacionJornadaStepHandler;
use App\Flows\Identification\Handlers\IdentificacionLegajoStepHandler;
use App\Flows\Identification\Handlers\IdentificacionNombreStepHandler;
use App\Flows\Identification\Handlers\IdentificacionSedeStepHandler;
use App\Flows\Placeholders\Handlers\AvisoFechaDesdePlaceholderStepHandler;
use App\Flows\Placeholders\Handlers\CertificadoNumeroAvisoPlaceholderStepHandler;
use App\Flows\Transitional\Handlers\EsperandoCantidadDiasStepHandler;
use App\Flows\Transitional\Handlers\EsperandoCertificadoStepHandler;
use App\Flows\Transitional\Handlers\EsperandoDniStepHandler;
use App\Flows\Transitional\Handlers\EsperandoTipoStepHandler;
use App\Flows\Transitional\Handlers\FallbackStepHandler;
use App\Flows\Validators\LegajoValidator;
use App\Flows\Validators\MenuSelectionValidator;
use App\Flows\Validators\PositiveIntegerValidator;
use App\Flows\Validators\RequiredTextValidator;
use App\Flows\Validators\SedeValidator;
use App\Services\Conversation\ConversationContextService;
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
                $app->make(MainMenuStepHandler::class),
                $app->make(IdentificacionNombreStepHandler::class),
                $app->make(IdentificacionLegajoStepHandler::class),
                $app->make(IdentificacionSedeStepHandler::class),
                $app->make(IdentificacionJornadaStepHandler::class),
                $app->make(AvisoFechaDesdePlaceholderStepHandler::class),
                $app->make(CertificadoNumeroAvisoPlaceholderStepHandler::class),
                $app->make(EsperandoDniStepHandler::class),
                $app->make(EsperandoTipoStepHandler::class),
                $app->make(EsperandoCantidadDiasStepHandler::class),
                $app->make(EsperandoCertificadoStepHandler::class),
                $app->make(FallbackStepHandler::class),
            ]);
        });

        $this->app->bind(MainMenuStepHandler::class, function ($app) {
            return new MainMenuStepHandler(
                $app->make(MenuSelectionValidator::class),
                $app->make(ConversationContextService::class),
            );
        });

        $this->app->bind(IdentificacionNombreStepHandler::class, function ($app) {
            return new IdentificacionNombreStepHandler(
                $app->make(RequiredTextValidator::class),
                $app->make(ConversationContextService::class),
            );
        });

        $this->app->bind(IdentificacionLegajoStepHandler::class, function ($app) {
            return new IdentificacionLegajoStepHandler(
                $app->make(LegajoValidator::class),
                $app->make(ConversationContextService::class),
            );
        });

        $this->app->bind(IdentificacionSedeStepHandler::class, function ($app) {
            return new IdentificacionSedeStepHandler(
                $app->make(SedeValidator::class),
                $app->make(ConversationContextService::class),
            );
        });

        $this->app->bind(IdentificacionJornadaStepHandler::class, function ($app) {
            return new IdentificacionJornadaStepHandler(
                $app->make(RequiredTextValidator::class),
                $app->make(ConversationContextService::class),
            );
        });

        $this->app->bind(AvisoFechaDesdePlaceholderStepHandler::class, function ($app) {
            return new AvisoFechaDesdePlaceholderStepHandler(
                $app->make(ConversationContextService::class),
            );
        });

        $this->app->bind(CertificadoNumeroAvisoPlaceholderStepHandler::class, function ($app) {
            return new CertificadoNumeroAvisoPlaceholderStepHandler(
                $app->make(ConversationContextService::class),
            );
        });

        $this->app->bind(EsperandoDniStepHandler::class, function ($app) {
            return new EsperandoDniStepHandler(
                $app->make(RequiredTextValidator::class),
            );
        });

        $this->app->bind(EsperandoTipoStepHandler::class, function ($app) {
            return new EsperandoTipoStepHandler(
                $app->make(MenuSelectionValidator::class),
            );
        });

        $this->app->bind(EsperandoCantidadDiasStepHandler::class, function ($app) {
            return new EsperandoCantidadDiasStepHandler(
                $app->make(PositiveIntegerValidator::class),
            );
        });

        $this->app->bind(EsperandoCertificadoStepHandler::class, function ($app) {
            return new EsperandoCertificadoStepHandler(
                $app->make(RequiredTextValidator::class),
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
