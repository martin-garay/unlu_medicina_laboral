# Guía de despliegue

## Propósito

Esta guía será la entrada operativa para aprovisionar y desplegar Medicina Laboral con Ansible. La implementación crece por etapas; los comandos documentados aquí solo deben utilizarse cuando los archivos correspondientes existan y hayan pasado sus validaciones.

La arquitectura y las decisiones completas están en [`ansible-deployment-plan.md`](ansible-deployment-plan.md). La operación productiva se documenta por separado en [`production-deployment.md`](production-deployment.md).

## Alcance inicial

- Debian 13 como sistema soportado inicialmente.
- Apache 2.4, PHP 8.4 y PostgreSQL 17 seleccionados mediante variables independientes.
- Aplicación y base en dos servidores, o en uno cuando el mismo host pertenece a `app_servers` y `db_servers`.
- Acceso SSH por clave y uso de `become`.
- Secretos cifrados con Ansible Vault.
- HTTPS local en Vagrant y URL pública temporal de ngrok para Meta.
- Backups locales en `/var/backups/medicina-laboral`.

## Requisitos de la estación de control

- Python 3 y `python3-venv`;
- una clave SSH autorizada en los hosts;
- el password file de Vault fuera del repositorio;
- Vagrant y un provider compatible para las pruebas locales;
- `ansible-lint` y `yamllint` para la validación completa.

La instalación operativa de Ansible debe hacerse con el venv versionado del
repo, no con paquetes globales del sistema. Esto evita mezclar el Ansible del
sistema con paquetes `pip --user` del operador:

```bash
cd deploy/provisioning
bin/setup-control-node
export PATH="$PWD/bin:$PATH"
ansible --version
```

Los wrappers `bin/ansible`, `bin/ansible-playbook`, `bin/ansible-vault`,
`bin/ansible-galaxy`, `bin/ansible-inventory`, `bin/ansible-lint` y
`bin/yamllint` fijan `PYTHONNOUSERSITE=1`, `ANSIBLE_HOME`, `ANSIBLE_LOCAL_TEMP`
y `ANSIBLE_COLLECTIONS_PATH` dentro de `deploy/.tools/`. Si no se agrega `bin/`
al `PATH`, ejecutar los comandos con prefijo `bin/`.

El password file inicial esperado es:

```text
~/.config/medicina-laboral/ansible-vault-password
```

Debe pertenecer al operador y tener permisos `0600`. Nunca debe copiarse al repositorio.

## Estructura

```text
deploy/provisioning/
├── ansible.cfg
├── requirements.yml
├── group_vars/
├── inventories/
├── playbooks/
└── roles/
```

Cada tecnología tendrá su propio rol. El inventory elegirá sus versiones sin formar un perfil monolítico:

```yaml
operating_system_family: debian
operating_system_version: "13"
web_server: apache
web_server_version: "2.4"
php_version: "8.4"
postgresql_version: "17"
```

Una versión nueva se incorpora dentro del rol de la tecnología afectada y luego se habilita en su lista de versiones soportadas.

## Inventarios locales

- `inventories/vagrant/single/hosts.yml`: una VM en los grupos de aplicación y base.
- `inventories/vagrant/split/hosts.yml`: dos VM, una para cada responsabilidad.

La topología se selecciona cambiando el inventory, no los roles.

El wrapper `bin/vagrant` usa la instalación local no versionada si está disponible y conserva el estado de Vagrant bajo `deploy/.local/`. Comandos previstos:

```bash
cd deploy/provisioning
MEDICINA_VAGRANT_TOPOLOGY=single bin/vagrant up
MEDICINA_VAGRANT_TOPOLOGY=split bin/vagrant up
```

La box inicial es `bento/debian-13` versión `202510.26.0`, con provider VirtualBox. El nombre y la versión pertenecen exclusivamente a la capa Vagrant/SO y podrán ampliarse sin modificar PostgreSQL, PHP o Apache.

Para la puesta en marcha de testing se valida primero la topología `single`, que
modela un servidor único con aplicación y base en el mismo host. En esa topología
Laravel conecta PostgreSQL por `127.0.0.1`, PostgreSQL no escucha en la IP de red
privada y el firewall no abre `5432` hacia redes externas.

Si una VM single queda lenta durante el primer arranque, el `Vagrantfile` define
un `boot_timeout` amplio. Antes de destruirla, inspeccionar el estado con:

```bash
MEDICINA_VAGRANT_TOPOLOGY=single bin/vagrant status
MEDICINA_VAGRANT_TOPOLOGY=single bin/vagrant ssh medicina-single
```

Cuando la VM esté limpia y accesible, guardar un snapshot base:

```bash
MEDICINA_VAGRANT_TOPOLOGY=single bin/vagrant snapshot save medicina-single pristine-debian13
```

El acceso operativo normal usa el usuario `deploy`. Para servidores reales donde
ese usuario todavía no exista, `playbooks/bootstrap-access.yml` lo crea en una
ejecución inicial explícita usando un usuario privilegiado temporal. El usuario
temporal no debe quedar guardado en el inventory permanente.

En el servidor de testing institucional, el acceso inicial confirmado requiere
saltar por `martin@170.210.103.133:46659`, entrar como `mgaray` a
`170.210.96.164` y elevar con `su -`. Como ese paso depende de credenciales
humanas, la configuración local de SSH no interactivo y la carga del password de
`su` en Vault quedan documentadas en
`deploy/provisioning/inventories/README.md`. En testing remoto se carga
`vault_testing_bootstrap_become_password` para `su` a root. Una vez cargado, el
usuario `deploy` se crea con:

```bash
cd deploy/provisioning
ansible-playbook -i inventories/testing/hosts.yml playbooks/bootstrap-access.yml
```

Mientras no se defina la política completa de seguridad institucional, testing
usa `security_enabled: false` y `firewall_enabled: false`. Esto permite ejecutar
`site.yml` completo sin aplicar los roles `hardening` ni `firewall`, y sin exigir
`nftables.service` en `monitoring`; no elimina ni reemplaza las reglas nftables
existentes ni modifica la política SSH del servidor.

## Validaciones iniciales

Desde `deploy/provisioning/`:

```bash
ansible-inventory -i inventories/vagrant/single/hosts.yml --graph
ansible-inventory -i inventories/vagrant/split/hosts.yml --graph
ansible-playbook -i inventories/vagrant/single/hosts.yml playbooks/validate.yml --syntax-check
ansible-playbook -i inventories/vagrant/split/hosts.yml playbooks/validate.yml --syntax-check
```

El playbook `validate.yml` comprueba que cada tecnología y versión haya sido declarada como soportada antes de aprovisionar.

## Flujo previsto

1. validar inventory y selecciones tecnológicas;
2. comprobar conectividad SSH;
3. aprovisionar sistema operativo y usuario `deploy`;
4. instalar PostgreSQL;
5. instalar Apache y PHP-FPM;
6. desplegar Laravel y configurar `.env` desde Vault;
7. configurar scheduler, TLS, backups y verificaciones;
8. repetir el playbook para comprobar idempotencia.

Los pasos 2 a 8 ya están implementados para Vagrant. Para converger todo el entorno:

```bash
ansible-playbook -i inventories/vagrant/split/hosts.yml site.yml
ansible-playbook -i inventories/vagrant/single/hosts.yml site.yml
```

Para generar backups inmediatos y ensayar una restauración no destructiva:

```bash
ansible-playbook -i inventories/vagrant/split/hosts.yml playbooks/backup.yml -e backup_run_now=true
ansible-playbook -i inventories/vagrant/split/hosts.yml playbooks/restore-test.yml
ansible-playbook -i inventories/vagrant/split/hosts.yml playbooks/monitoring.yml
```

`restore-test.yml` restaura PostgreSQL en `medicina_laboral_restore_test`, verifica
la tabla de migraciones y elimina esa base temporal. Para archivos comprueba el
checksum y que el archivo tar pueda leerse; no sobrescribe storage activo.

`monitoring.yml` falla si falta un servicio, la política nftables, capacidad de
disco, configuración Laravel/WhatsApp, scheduler, vigencia TLS o un backup reciente.

Para volver a una release ya instalada:

```bash
ansible-playbook -i inventories/production/hosts.yml playbooks/rollback.yml \
  -e rollback_release_id=<release-anterior>
```

El playbook exige que la release exista y sea distinta de `current`, cambia el
symlink, reconstruye caches, recarga PHP-FPM y valida HTTPS. Si cualquier paso
falla, restaura el symlink anterior y verifica la recuperación. No revierte ni
modifica migraciones de base de datos.

La activación de una release nueva aplica la misma protección: valida `/up`
inmediatamente después de cambiar `current` y recupera el symlink anterior si el
health check falla. Las migraciones ya ejecutadas nunca se revierten de forma
automática.
