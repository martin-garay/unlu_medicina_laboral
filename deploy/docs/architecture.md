# Arquitectura del despliegue

## Fuente de verdad y límites

`deploy/provisioning/site.yml` orquesta capacidades independientes. Los
playbooks eligen hosts y roles; los roles contienen paquetes, servicios,
templates y diferencias por versión. El inventory selecciona entorno,
topología y versiones. Vault contiene secretos.

El plan histórico y las decisiones iniciales permanecen en
[`ansible-deployment-plan.md`](ansible-deployment-plan.md); este documento
describe el contrato implementado.

## Capas

| Capa | Responsabilidad |
|---|---|
| Control node | Git, wrappers, Ansible, colecciones, Docker/Composer, artefactos y SSH |
| Inventory | hosts, topología, versiones, flags y datos no secretos del entorno |
| `group_vars/all.yml` | defaults compartidos de baja precedencia |
| Vault | passwords, claves Laravel/Meta y secretos por entorno |
| Playbooks | orden y composición de capacidades |
| Roles | implementación tecnológica versionada |
| Hosts administrados | Apache/PHP-FPM/Laravel, PostgreSQL y persistencia |

No cargar `group_vars/all.yml` como `vars_files`: elevaría su precedencia y
podría pisar el inventory. Sólo Vault y matrices declarativas se cargan de forma
explícita donde corresponde.

## Selección tecnológica

Las variables `operating_system_family`, `operating_system_version`,
`web_server`, `web_server_version`, `php_version` y `postgresql_version` son
independientes. La matriz admitida vive en
`playbooks/vars/supported-platforms.yml`.

Para soportar una versión nueva:

1. agregar vars/tasks/templates dentro del rol de esa tecnología;
2. mantener intactos los archivos de versiones ya validadas;
3. ampliar la matriz soportada;
4. agregar tests/lint y validar single y split;
5. documentar la nueva combinación en `validation-matrix.md`.

## Topologías

- **single**: un host pertenece a `app_servers` y `db_servers`; Laravel conecta
  por loopback y PostgreSQL no se publica en la red.
- **split**: hosts separados; PostgreSQL escucha sólo en direcciones y redes
  autorizadas para la aplicación.

La topología cambia mediante inventory, no mediante perfiles monolíticos.

## Pipeline del release

El flujo detallado y su rollback están representados en
[`diagrams/deployment-pipeline.mmd`](diagrams/deployment-pipeline.mmd).

Testing usa source Git y transferencia `archive`. PC Uni obtiene el tag exacto,
construye `vendor/` en Docker cuando el destino no tiene salida HTTPS, transfiere
artefactos y deja al servidor validar la plataforma. El release enlaza `.env` y
`storage` compartidos; las migraciones y cachés se completan antes de cambiar
`current`.

La persistencia no vive dentro del release:

- `/var/www/medicina-laboral/shared/.env`;
- `/var/www/medicina-laboral/shared/storage`;
- `/var/lib/medicina-laboral/private`;
- `/var/backups/medicina-laboral`.

## Controles operativos

- Scheduler: cron con lock y `schedule:run` sobre `current`.
- TLS: certificado provisto o CA local sólo para laboratorio/testing.
- Backups: PostgreSQL y archivos, checksums y retención escalonada.
- Restore-test: base temporal y lectura del tar, sin tocar datos activos.
- Monitoring: servicios, firewall efectivo, disco, doctor Laravel, scheduler,
  TLS y antigüedad de backups.
- Security: hardening y nftables sólo cuando los flags del entorno lo habilitan.

## Secretos y responsabilidades

No versionar inventories reales de producción, password files, claves privadas,
tokens, `.env` renderizados ni artefactos locales. `no_log` reduce exposición en
Ansible, pero no reemplaza la higiene de comandos y reportes.

Antes de producción permanecen decisiones humanas: dominio/DNS/PKI, redes y
bastion, canal de alertas, segunda copia de backups, RPO/RTO, retención de datos
sensibles, firma del webhook y almacenamiento privado definitivo.
