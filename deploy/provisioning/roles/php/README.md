# Rol PHP

Instala y configura PHP-FPM para Laravel únicamente en `app_servers`.

La versión se resuelve dentro del rol mediante `php_version` y la plataforma. PHP 8.4 sobre Debian 13 vive bajo `vars/versions/php-8.4-debian-13.yml` y `tasks/versions/php-8.4-debian-13.yml`.

Administra paquetes, límites PHP, OPcache, servicio y socket FPM. No configura Apache ni despliega código.

