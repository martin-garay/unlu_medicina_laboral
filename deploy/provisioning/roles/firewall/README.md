# Rol `firewall`

Administra nftables con política de entrada `drop`. SSH, HTTP/HTTPS y PostgreSQL
se habilitan únicamente desde redes declaradas en el inventory. El template se
valida antes de reemplazar la política activa.

Por defecto `firewall_flush_ruleset` es `false`, por lo que el rol no ejecuta
`flush ruleset`: sólo destruye y recrea la tabla administrada
`inet medicina_laboral`, preservando tablas o reglas nftables preexistentes
fuera de esa tabla. Si se define `firewall_flush_ruleset: true`, el template
limpia todo el ruleset antes de aplicar la política administrada; usarlo sólo
cuando Ansible deba tomar control completo del firewall del host.
