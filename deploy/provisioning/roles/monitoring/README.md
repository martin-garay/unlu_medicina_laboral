# Rol `monitoring`

Ejecuta checks sin agente externo: servicios, disco, `medicina:doctor`, scheduler,
vigencia TLS y antigüedad de backups. Un fallo devuelve error de Ansible y puede
integrarse luego con el sistema institucional de alertas.

En modo `--check`, los checks operativos se informan como omitidos. Ese modo no
crea paquetes, servicios, releases ni backups, por lo que validar salud real al
final de un dry-run inicial produciría falsos negativos.
