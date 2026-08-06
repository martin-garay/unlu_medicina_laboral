# Despliegue en producción

## Estado

Este documento define el procedimiento y los controles requeridos para producción. Todavía no autoriza un despliegue productivo: los roles deben implementarse y probarse primero con Vagrant en topología de una y dos máquinas.

## Datos que deberá proporcionar la Universidad

- IP o hostname de los servidores de aplicación y base;
- usuario inicial y clave SSH;
- dominio y DNS;
- método de emisión TLS institucional o autorización para ACME;
- existencia de proxy, WAF, NAT o bastion;
- redes autorizadas entre aplicación y PostgreSQL;
- canal para alertas operativas.

Estos datos vivirán en inventory, variables por entorno o Vault. No se hardcodearán en roles.

## Topologías

La recomendada utiliza dos servidores:

```text
Internet -> HTTPS -> Apache/PHP-FPM/Laravel -> PostgreSQL privado
```

También se admite un servidor único asignando el mismo host a `app_servers` y `db_servers`. PostgreSQL nunca debe quedar abierto a Internet.

## Preparación previa

Antes del primer despliegue deberán estar confirmados:

- Debian y las versiones tecnológicas seleccionadas están soportadas;
- acceso SSH por clave y escalamiento mediante `become`;
- Vault productivo creado, cifrado y respaldado por un procedimiento institucional;
- `APP_DEBUG=false`;
- dominio, DNS y TLS resolviendo hacia Apache o el proxy institucional;
- credenciales de Meta y verify token almacenados exclusivamente en Vault;
- administrador local de desarrollo deshabilitado;
- ventana y responsable para migraciones;
- espacio suficiente para releases, storage y backups locales.

## Inventario productivo

El inventory productivo no debe contener secretos. Conceptualmente:

```yaml
all:
  children:
    app_servers:
      hosts:
        app01.unlu.example:
    db_servers:
      hosts:
        db01.unlu.example:
```

Los nombres son ilustrativos. Hasta disponer de hosts reales, la validación se realiza con los inventories Vagrant.

## Secuencia productiva prevista

1. ejecutar lint, syntax-check y validación de inventory;
2. comprobar conectividad y privilegios sin realizar cambios;
3. ejecutar `--check --diff` donde sea seguro;
4. generar un backup previo si el despliegue incluye migraciones;
5. desplegar un tag o commit explícito;
6. instalar dependencias con flags productivos;
7. renderizar `.env` desde variables y Vault;
8. enlazar directorios persistentes y aplicar permisos;
9. ejecutar migraciones una sola vez;
10. activar el release mediante symlink;
11. recargar PHP-FPM y Apache solo si cambió su configuración;
12. validar HTTPS, `/up`, conexión DB, scheduler y logs;
13. conservar el release anterior para rollback.

Los comandos ejecutables se agregarán cuando existan los playbooks respectivos.

## Backups

La primera versión almacenará los backups únicamente en el servidor universitario:

```text
/var/backups/medicina-laboral/
├── postgresql/
├── files/
└── manifests/
```

Se aplicarán permisos restrictivos, retención, checksums y pruebas de restore. No se implementarán restic, S3, SFTP ni transferencias a otro servidor en esta etapa. Esto implica que una pérdida total del servidor puede incluir sus backups.

## TLS y webhook

La URL productiva será parametrizable:

```text
https://<dominio>/api/whatsapp/webhook
```

Ngrok sirve únicamente para desarrollo/Vagrant. Antes de producción se deben validar certificado, renovación, redirección HTTP a HTTPS, firewall, firma de los POST de Meta y ausencia de secretos o payloads sensibles en logs.

## Criterios para autorizar producción

- aprovisionamiento exitoso e idempotente en Debian 13;
- pruebas single-host y split-host completas;
- restore comprobado en un entorno limpio;
- rollback de código ensayado;
- migraciones revisadas por reversibilidad y compatibilidad;
- PostgreSQL accesible solo desde redes autorizadas;
- credenciales de desarrollo deshabilitadas;
- health checks y scheduler verificados;
- riesgos de storage privado, logs sensibles y firma del webhook resueltos.

Si alguno de estos controles falla, el despliegue productivo debe detenerse.

