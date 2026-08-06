# Rol common

Prepara la base común de los hosts sin instalar componentes de aplicación o base de datos.

## Responsabilidades

- validar familia y versión del sistema operativo;
- instalar paquetes operativos básicos;
- configurar timezone;
- crear el usuario y grupo de despliegue;
- instalar claves SSH autorizadas;
- habilitar sudo sin contraseña solo cuando el entorno lo solicita explícitamente.

## Variables principales

- `common_timezone`
- `common_base_packages`
- `deploy_user_name`
- `deploy_user_group`
- `deploy_user_public_keys`
- `deploy_user_passwordless_sudo`

Las diferencias de Debian 13 viven en `vars/versions/debian-13.yml` y `tasks/versions/debian-13.yml`. Agregar otro sistema no modifica esos archivos.

## Validaciones

- facts coinciden con la selección del inventory;
- usuario y clave instalados;
- `sudo -n true` funciona cuando está habilitado;
- segunda ejecución sin cambios.

