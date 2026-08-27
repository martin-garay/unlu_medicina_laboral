# Rol `tls`

Soporta `local_ca` para Vagrant y `provided` para certificados entregados por la
infraestructura institucional. El material privado local vive en `deploy/.local/tls`
y no se versiona. Apache administra HTTPS y la redirección desde HTTP.

Con `tls_provider: local_ca`, el rol genera el material TLS en la estación de
control incluso durante `--check`. Es necesario para que Ansible pueda evaluar
las tareas `copy` que instalarían la clave y los certificados en el host remoto.
Ese material queda bajo `.local/`, fuera de Git.
