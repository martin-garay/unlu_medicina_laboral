# Operación y soporte

## Objetivo

Este documento reúne una base mínima de operación para desarrollo avanzado, QA local y entornos de prueba.

No define todavía un despliegue productivo completo.  
Sí deja más claro cómo:

- levantar el entorno
- verificar configuración operativa
- ejecutar pruebas
- revisar logs
- correr automatismos manualmente
- diagnosticar problemas frecuentes

## Comandos recomendados

### Setup inicial

```bash
make up
make setup
```

Esto deja:

- contenedores levantados
- dependencias instaladas
- `APP_KEY` generada
- migraciones corridas

## Validación operativa rápida

Usar:

```bash
make doctor
```

o:

```bash
docker-compose exec app php artisan medicina:doctor
```

Este diagnóstico revisa:

- `APP_KEY`
- conectividad a base de datos
- canal de logs
- configuración mínima de WhatsApp
- umbrales de inactividad
- drivers de storage de adjuntos

### Interpretación

- `OK`: el chequeo no detecta problemas
- `WARN`: el entorno puede funcionar, pero falta algo recomendable
- `ERROR`: hay una condición que conviene corregir antes de operar

## Logs

Por default local, `LOG_CHANNEL=stack` escribe los logs de Laravel en `storage/logs/laravel.log`.

Para seguir excepciones y logs de aplicación:

```bash
make logs
```

Para seguir stdout/stderr del contenedor, incluido el access log del servidor embebido:

```bash
make logs-app
```

Si se configura `LOG_CHANNEL=stderr`, los `Log::` de Laravel pasan a verse también por `make logs-app`.

## Testing

Comandos recomendados:

```bash
make test
make test-unit
make test-feature
```

Si hace falta ejecutar una tanda puntual:

```bash
make artisan CMD="test tests/Feature/Services/ConversationTimeoutServiceTest.php"
```

## Scheduler e inactividad

El scheduler registra actualmente:

- `conversations:process-timeouts`

Para ejecutar una pasada manual del scheduler:

```bash
make schedule-run
```

Para correr el procesamiento de inactividad directamente:

```bash
make timeouts
```

Y para una corrida reproducible con hora fija:

```bash
make timeouts-now NOW="2026-03-20 10:00:00"
```

## Comandos útiles de soporte

```bash
make help
make ps
make restart
make sh
make db
```

También puede ejecutarse cualquier comando Artisan con:

```bash
make artisan CMD="about"
```

## Criterios mínimos antes de usar el sistema en entorno de prueba

Conviene verificar al menos:

- `make doctor` sin errores
- `make test` o al menos la tanda impactada por el cambio
- contenedores arriba con `make ps`
- logs visibles con `make logs`
- migraciones aplicadas
- variables de WhatsApp revisadas si se va a probar el webhook real

## Limitaciones actuales

- no hay todavía pipeline CI/CD obligatoria
- no hay colas ni jobs para envíos automáticos
- no hay observabilidad avanzada centralizada
- el canal principal sigue siendo WhatsApp Cloud API

La base actual prioriza operabilidad simple y diagnósticos rápidos dentro del entorno Docker del proyecto.
