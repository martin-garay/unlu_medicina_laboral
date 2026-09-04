# Rol `monitoring`

Ejecuta checks sin agente externo: servicios, disco, `medicina:doctor`, scheduler,
vigencia TLS y antigüedad de backups. Un fallo devuelve error de Ansible y puede
integrarse luego con el sistema institucional de alertas.

Los probes HTTP/HTTPS del provisioning se ejecutan con `curl` en el host
administrado. No usan `ansible.builtin.uri`, para evitar incompatibilidades
entre el stack HTTPS de los módulos Ansible y la versión de `urllib3` instalada
en el servidor.

En modo `--check`, los checks operativos se informan como omitidos. Ese modo no
crea paquetes, servicios, releases ni backups, por lo que validar salud real al
final de un dry-run inicial produciría falsos negativos.
