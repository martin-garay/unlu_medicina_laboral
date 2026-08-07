# Rol `tls`

Soporta `local_ca` para Vagrant y `provided` para certificados entregados por la
infraestructura institucional. El material privado local vive en `deploy/.local/tls`
y no se versiona. Apache administra HTTPS y la redirección desde HTTP.
