# Rol PostgreSQL

Instala el cliente PostgreSQL en los hosts que deben conectarse y el servidor únicamente en `db_servers`.

## Responsabilidades

- seleccionar paquetes y rutas desde la versión PostgreSQL y plataforma;
- instalar y habilitar el cluster;
- limitar `listen_addresses` y `pg_hba.conf` a inventory;
- crear rol y base de Laravel;
- usar SCRAM para conexiones TCP;
- validar servicio y consulta remota.

## Variables principales

- `postgresql_version`
- `postgresql_server_enabled`
- `postgresql_listen_addresses`
- `postgresql_allowed_networks`
- `postgresql_database_name`
- `postgresql_application_user`
- `postgresql_application_password`
- `postgresql_no_password_changes` (`true` por defecto; usar `false` durante una rotación explícita)

La contraseña proviene de Ansible Vault. Una versión nueva agrega sus propios archivos bajo `vars/versions/`, `tasks/versions/` y, si cambia el formato, `templates/versions/`.
