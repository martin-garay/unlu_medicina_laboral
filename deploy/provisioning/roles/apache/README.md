# Rol Apache

Instala Apache en `app_servers`, activa módulos requeridos y configura un VirtualHost que envía PHP a PHP-FPM.

La selección Apache 2.4/Debian 13 vive dentro del rol. TLS se incorporará en el rol `tls`; este rol prueba inicialmente HTTP privado.

Variables principales: `apache_server_name`, `apache_document_root`, `apache_fpm_socket`, logs y headers básicos.

