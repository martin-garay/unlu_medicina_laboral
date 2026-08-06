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

- Ansible;
- una clave SSH autorizada en los hosts;
- el password file de Vault fuera del repositorio;
- Vagrant y un provider compatible para las pruebas locales;
- `ansible-lint` y `yamllint` para la validación completa.

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

Los pasos 2 a 8 se incorporarán progresivamente. La presencia de esta guía no implica todavía que estén implementados.
