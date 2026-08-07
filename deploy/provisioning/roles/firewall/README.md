# Rol `firewall`

Administra nftables con política de entrada `drop`. SSH, HTTP/HTTPS y PostgreSQL
se habilitan únicamente desde redes declaradas en el inventory. El template se
valida antes de reemplazar la política activa.
