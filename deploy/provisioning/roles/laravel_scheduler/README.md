# Rol `laravel_scheduler`

Administra la invocación por minuto de Laravel Scheduler en los hosts de aplicación.

El cron se ejecuta como `deploy`, usa la release `current`, persiste salida en
`shared/storage/logs/scheduler.log` y utiliza `flock` para evitar dos invocaciones
simultáneas del scheduler. La propia tarea de conversaciones conserva además su
protección Laravel `withoutOverlapping`.
