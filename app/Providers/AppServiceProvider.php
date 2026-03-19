<?php

namespace App\Providers;

use App\Flows\Common\MessageResolver;
use App\Flows\Aviso\Handlers\AvisoDomicilioCircunstancialDetalleStepHandler;
use App\Flows\Aviso\Handlers\AvisoDomicilioCircunstancialStepHandler;
use App\Flows\Aviso\Handlers\AvisoConfirmacionFinalStepHandler;
use App\Flows\Aviso\Handlers\AvisoFechaDesdeStepHandler;
use App\Flows\Aviso\Handlers\AvisoFechaHastaStepHandler;
use App\Flows\Aviso\Handlers\AvisoMotivoStepHandler;
use App\Flows\Aviso\Handlers\AvisoObservacionesStepHandler;
use App\Flows\Aviso\Handlers\AvisoTipoAusentismoStepHandler;
use App\Flows\Certificado\Handlers\CertificadoAdjuntoStepHandler;
use App\Flows\Certificado\Handlers\CertificadoNumeroAvisoStepHandler;
use App\Flows\Certificado\Handlers\CertificadoTipoStepHandler;
use App\Flows\Handlers\MainMenuStepHandler;
use App\Flows\Identification\Handlers\IdentificacionJornadaStepHandler;
use App\Flows\Identification\Handlers\IdentificacionLegajoStepHandler;
use App\Flows\Identification\Handlers\IdentificacionNombreStepHandler;
use App\Flows\Identification\Handlers\IdentificacionSedeStepHandler;
use App\Flows\Placeholders\Handlers\AvisoFamiliarPendienteStepHandler;
use App\Flows\Placeholders\Handlers\CertificadoConfirmacionPendienteStepHandler;
use App\Flows\Transitional\Handlers\EsperandoCantidadDiasStepHandler;
use App\Flows\Transitional\Handlers\EsperandoCertificadoStepHandler;
use App\Flows\Transitional\Handlers\EsperandoDniStepHandler;
use App\Flows\Transitional\Handlers\EsperandoTipoStepHandler;
use App\Flows\Validators\AvisoReferenciaValidator;
use App\Flows\Validators\AusentismoTypeValidator;
use App\Flows\Validators\DateInputValidator;
use App\Flows\Validators\AvisoFechaHastaValidator;
use App\Flows\Transitional\Handlers\FallbackStepHandler;
use App\Flows\Validators\LegajoValidator;
use App\Flows\Validators\MenuSelectionValidator;
use App\Flows\Validators\PositiveIntegerValidator;
use App\Flows\Validators\RequiredTextValidator;
use App\Flows\Validators\SedeValidator;
use App\Flows\Validators\TipoCertificadoValidator;
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
                $app->make(AvisoFechaDesdeStepHandler::class),
                $app->make(AvisoFechaHastaStepHandler::class),
                $app->make(AvisoTipoAusentismoStepHandler::class),
                $app->make(AvisoMotivoStepHandler::class),
                $app->make(AvisoDomicilioCircunstancialStepHandler::class),
                $app->make(AvisoDomicilioCircunstancialDetalleStepHandler::class),
                $app->make(AvisoObservacionesStepHandler::class),
                $app->make(AvisoConfirmacionFinalStepHandler::class),
                $app->make(AvisoFamiliarPendienteStepHandler::class),
                $app->make(CertificadoNumeroAvisoStepHandler::class),
                $app->make(CertificadoTipoStepHandler::class),
                $app->make(CertificadoAdjuntoStepHandler::class),
                $app->make(CertificadoConfirmacionPendienteStepHandler::class),
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

        $this->app->bind(AvisoFechaDesdeStepHandler::class, function ($app) {
            return new AvisoFechaDesdeStepHandler(
                $app->make(DateInputValidator::class),
                $app->make(ConversationContextService::class),
            );
        });

        $this->app->bind(AvisoFechaHastaStepHandler::class, function ($app) {
            return new AvisoFechaHastaStepHandler(
                $app->make(AvisoFechaHastaValidator::class),
                $app->make(ConversationContextService::class),
            );
        });

        $this->app->bind(AvisoTipoAusentismoStepHandler::class, function ($app) {
            return new AvisoTipoAusentismoStepHandler(
                $app->make(AusentismoTypeValidator::class),
                $app->make(ConversationContextService::class),
            );
        });

        $this->app->bind(AvisoMotivoStepHandler::class, function ($app) {
            return new AvisoMotivoStepHandler(
                $app->make(RequiredTextValidator::class),
                $app->make(ConversationContextService::class),
            );
        });

        $this->app->bind(AvisoDomicilioCircunstancialStepHandler::class, function ($app) {
            return new AvisoDomicilioCircunstancialStepHandler(
                $app->make(ConversationContextService::class),
            );
        });

        $this->app->bind(AvisoDomicilioCircunstancialDetalleStepHandler::class, function ($app) {
            return new AvisoDomicilioCircunstancialDetalleStepHandler(
                $app->make(RequiredTextValidator::class),
                $app->make(ConversationContextService::class),
            );
        });

        $this->app->bind(AvisoObservacionesStepHandler::class, function ($app) {
            return new AvisoObservacionesStepHandler(
                $app->make(ConversationContextService::class),
                $app->make(\App\Services\AvisoService::class),
            );
        });

        $this->app->bind(AvisoConfirmacionFinalStepHandler::class, function ($app) {
            return new AvisoConfirmacionFinalStepHandler(
                $app->make(ConversationContextService::class),
            );
        });

        $this->app->bind(AvisoFamiliarPendienteStepHandler::class, function ($app) {
            return new AvisoFamiliarPendienteStepHandler(
                $app->make(ConversationContextService::class),
            );
        });

        $this->app->bind(CertificadoNumeroAvisoStepHandler::class, function ($app) {
            return new CertificadoNumeroAvisoStepHandler(
                $app->make(AvisoReferenciaValidator::class),
                $app->make(ConversationContextService::class),
            );
        });

        $this->app->bind(CertificadoTipoStepHandler::class, function ($app) {
            return new CertificadoTipoStepHandler(
                $app->make(TipoCertificadoValidator::class),
                $app->make(ConversationContextService::class),
            );
        });

        $this->app->bind(CertificadoAdjuntoStepHandler::class, function ($app) {
            return new CertificadoAdjuntoStepHandler(
                $app->make(ConversationContextService::class),
            );
        });

        $this->app->bind(CertificadoConfirmacionPendienteStepHandler::class, function ($app) {
            return new CertificadoConfirmacionPendienteStepHandler(
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
