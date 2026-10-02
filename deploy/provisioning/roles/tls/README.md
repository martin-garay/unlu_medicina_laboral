# Rol `tls`

Soporta `local_ca` para Vagrant y `provided` para certificados entregados por la
infraestructura institucional. El material privado local vive en `deploy/.local/tls`
y no se versiona. Apache administra HTTPS y la redirección desde HTTP.

`provided` requiere `tls_certificate_path` (cadena completa) y
`tls_private_key_path` existentes en el servidor. Sigue los symlinks de
Let's Encrypt y falla si su destino falta o no es un archivo regular. No copia,
modifica permisos, respalda, emite ni renueva esos archivos. Infraestructura
debe renovar y recargar Apache; el deploy conserva las rutas `live/`.

`local_ca` guarda un backup remoto del archivo anterior cuando una copia cambia
la clave, el certificado o la CA. Los backups de claves siguen siendo secretos y
requieren custodia y retención operacional; no deben descargarse al repo.

Con `tls_provider: local_ca`, el rol genera el material TLS en la estación de
control incluso durante `--check`. Es necesario para que Ansible pueda evaluar
las tareas `copy` que instalarían la clave y los certificados en el host remoto.
Ese material queda bajo `.local/`, fuera de Git.

El certificado de servidor se firma con un archivo local de extensiones
`*.ext`, no con `openssl x509 -copy_extensions`, para mantener compatibilidad
con estaciones de control que tienen versiones antiguas de OpenSSL.

Durante `--check`, si un directorio remoto de destino TLS todavía no existe, el
rol informa que lo crearía en apply real y saltea la copia correspondiente. En
la ejecución real, esos directorios se crean antes de instalar la clave y los
certificados.
