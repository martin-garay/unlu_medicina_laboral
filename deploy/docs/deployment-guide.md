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

- Git, OpenSSH client y `rsync`;
- Python 3 con `python3-venv`;
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

Python 3.10 o superior es el minimo recomendado para la estacion de control. El
setup acepta Python 3.8 para compatibilidad con PC Uni, pero puede mostrar
warnings de deprecacion de `cryptography`; esos warnings no bloquean la
ejecucion. Si existe un Python mas nuevo en la estacion de control, recrear el
venv indicando explicitamente el interprete:

```bash
cd deploy/provisioning
MEDICINA_CONTROL_PYTHON=python3.12 bin/setup-control-node
```

Para verificar que se esta usando el Ansible versionado del repo:

```bash
which ansible
ansible --version
```

La ruta debe resolver a `deploy/provisioning/bin/ansible` o al venv
`deploy/.tools/ansible-control-venv`. Si aparece `/usr/bin/ansible`, falta
exportar `PATH="$PWD/bin:$PATH"` o usar el prefijo `bin/`.

Antes de commitear cambios en `deploy/provisioning`, correr el check versionado:

```bash
bin/check-deploy
```

Ese check valida lint, syntax-check de playbooks, invariantes criticas de
testing y que ningun playbook vuelva a cargar `../group_vars/all.yml` como
`vars_files`.

En estaciones de control con Python 3.8, como PC Uni, `bin/check-deploy` saltea
`ansible-lint` porque las versiones actuales de esa herramienta ya no son
compatibles de forma confiable con ese interprete. Los checks criticos de
deploy siguen corriendo: invariantes, `yamllint`, `validate.yml --check`,
syntax-check y `git diff --check`. El lint completo debe ejecutarse desde una
estacion con Python 3.10 o superior antes de integrar cambios de provisioning.

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

`group_vars/all.yml` contiene defaults compartidos y Ansible lo carga como
variables de grupo. No debe incluirse como `vars_files` dentro de los playbooks:
las variables cargadas con `vars_files` tienen mayor precedencia y pueden pisar
selecciones del inventory, como `application_release_id`, `security_enabled` o
`firewall_enabled`. Los playbooks sólo deben declarar `vars_files` para secretos
explícitos, por ejemplo `../group_vars/vault.yml`.

Cada directorio de inventory versionado contiene un enlace `group_vars` hacia
`deploy/provisioning/group_vars`, para que los defaults compartidos carguen
tambien cuando se invoca Ansible con un archivo concreto como
`-i inventories/testing/hosts.yml`. `bin/check-deploy` valida que esos defaults
esten disponibles antes de ejecutar deploy.

La matriz de plataformas soportadas que usa `playbooks/validate.yml` vive en
`playbooks/vars/supported-platforms.yml`. Esa matriz se carga de forma explicita
porque no es una seleccion de entorno y no debe depender de la carga implicita de
`group_vars`.

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

El checkout de la aplicación se realiza en la estación de control y luego se
sincroniza al host remoto. En testing se usa HTTPS para GitHub porque desde PC
Uni se observo timeout hacia `github.com:22`.

La sincronizacion de la aplicación usa `rsync` en la estación de control y en el
servidor. El rol `application` instala `rsync` en apply real. En un primer
`--check`, si el servidor todavía no lo tiene, la tarea de sincronización se
saltea con un mensaje explicito porque el paquete aún no fue instalado. Lo mismo
ocurre si el directorio de release todavía no existe: el apply real lo crea antes
de sincronizar.

El rol `monitoring` valida salud operativa al final del apply real: servicios,
doctor de Laravel, scheduler, TLS y backups recientes. En `--check` informa que
omite esos controles porque el dry-run no instala servicios ni crea releases o
backups reales.

Mientras no se defina la política completa de seguridad institucional, testing
usa `security_enabled: false` y `firewall_enabled: false`. Esto permite ejecutar
`site.yml` completo sin aplicar los roles `hardening` ni `firewall`, y sin exigir
`nftables.service` en `monitoring`; no elimina ni reemplaza las reglas nftables
existentes ni modifica la política SSH del servidor.

## Diagnóstico de la estación de control

Si aparecen errores como `No filter named 'bool' found`, referencias a
`environmentfilter` de Jinja2 o `No module named 'ansible.module_utils.six.moves'`,
el problema esperado no esta en el servidor sino en la instalacion de Ansible de
la estacion de control. La causa observada en PC Uni fue una combinacion del
Ansible del sistema con paquetes `pip --user` de Python 3.8. Corregirlo usando
el venv versionado:

```bash
cd deploy/provisioning
bin/setup-control-node
export PATH="$PWD/bin:$PATH"
ansible --version
```

El bootstrap inicial de `deploy` usa tareas `raw` a proposito: instala `sudo` y
`python3` si faltan, y crea el usuario operativo antes de depender de modulos
Python remotos. Despues del bootstrap, `ansible -m ping` es una validacion de
que el host ya puede ejecutar modulos Ansible normales.

Si `site.yml --check --diff` falla en `tls : Install TLS private key` con salida
censurada por `no_log`, revisar primero que se este usando un commit que genere
el material `local_ca` tambien en check mode. El rol necesita tener disponibles
la clave y certificados locales antes de simular las tareas `copy`; si no puede
crearlos, falla antes con un mensaje que apunta a `openssl` en la estacion de
control.

En `--check`, si el host remoto todavia no tiene directorios como
`/etc/ssl/private`, el rol informa que los crearia durante el apply real y
saltea la copia TLS correspondiente para no fallar dentro de una tarea con
`no_log`.

Si falla en `tls : Sign server certificate with local CA` con
`x509: Unrecognized flag copy_extensions`, la estacion de control todavia esta
usando una version anterior del rol. Actualizar desde `origin/main`; el rol
actual firma el certificado con `-extfile` y no depende de `-copy_extensions`.

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
