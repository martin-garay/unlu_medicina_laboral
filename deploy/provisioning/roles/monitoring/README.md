# Rol `monitoring`

Ejecuta checks sin agente externo: servicios, disco, `medicina:doctor`, scheduler,
vigencia TLS y antigüedad de backups. Un fallo devuelve error de Ansible y puede
integrarse luego con el sistema institucional de alertas.
