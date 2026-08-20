# Despliegue

Esta carpeta es la fuente de verdad para toda la documentación y el código de despliegue del proyecto.

## Estado actual

La implementación base está completa y validada con Debian 13 en topologías
Vagrant single y split. Aprovisiona PostgreSQL 17, PHP 8.4, Apache 2.4, Laravel,
scheduler, TLS, backups, firewall, hardening y diagnóstico. Producción utiliza la
misma composición, copiando y completando el inventory de ejemplo con datos reales.

## Documentación

- [`docs/ansible-deployment-plan.md`](docs/ansible-deployment-plan.md): relevamiento del repositorio, arquitectura objetivo y plan completo de implementación con Ansible.
- [`docs/deployment-guide.md`](docs/deployment-guide.md): entrada operativa y flujo general de despliegue.
- [`docs/production-deployment.md`](docs/production-deployment.md): controles y procedimiento previsto para producción.
- [`docs/validation-matrix.md`](docs/validation-matrix.md): evidencia de lint, convergencia, restore y rollback.

## Estructura

```text
deploy/
├── README.md
├── .local/
├── .tools/
├── docs/
└── provisioning/
    ├── ansible.cfg
    ├── requirements.yml
    ├── site.yml
    ├── playbooks/
    ├── inventories/
    ├── group_vars/
    ├── roles/
    ├── bin/
    └── Vagrantfile
```

### Responsabilidades

- `deploy/README.md`: entrada rápida y mapa general del despliegue.
- `deploy/docs/`: documentación operativa canónica. Incluye guías de despliegue,
  producción, matriz de validación y plan Ansible.
- `deploy/provisioning/`: raíz técnica para ejecutar Ansible y Vagrant.
- `deploy/provisioning/site.yml`: orquestador principal del despliegue completo.
- `deploy/provisioning/playbooks/`: playbooks por capacidad o tarea operacional:
  bootstrap de acceso, common, runtime, aplicación, base de datos, TLS,
  scheduler, backups, restore-test, monitoring, security y rollback.
- `deploy/provisioning/inventories/`: inventarios por entorno:
  - `vagrant/`: laboratorio local con topologías `single` y `split`;
  - `testing/`: servidor dedicado de pruebas, preparado sin ejecutar contra el
    host real hasta confirmar red y acceso SSH;
  - `production/`: plantilla ejemplo para producción.
- `deploy/provisioning/group_vars/`: variables globales compartidas, defaults de
  versiones, rutas, backup, TLS, usuario operativo y base de datos. Los secretos
  deben ir cifrados con Vault.
- `deploy/provisioning/roles/`: roles Ansible reutilizables por tecnología o
  responsabilidad.
- `deploy/provisioning/bin/`: wrappers auxiliares versionables, como `bin/vagrant`.
- `deploy/provisioning/Vagrantfile`: definición del laboratorio local.
- `deploy/.local/`: material local no versionado, como claves SSH, descargas,
  Vagrant home local y archivos temporales.
- `deploy/.tools/`: herramientas locales no versionadas usadas para operar o
  validar el despliegue.
- `deploy/provisioning/.local/` y `deploy/provisioning/.vagrant/`: estado local
  generado por validaciones, TLS local, releases temporales y Vagrant.

La implementación mantiene la forma familiar del provisioning de elecciones:
playbooks en `deploy/provisioning/playbooks/`, variables en `group_vars/`,
inventarios por entorno y roles estándar en `roles/`.

Cada tecnología tendrá un rol propio, por ejemplo `roles/postgresql/` o `roles/php/`. Las diferencias por versión vivirán dentro de `vars/versions/`, `tasks/versions/` o `templates/versions/` únicamente cuando sean necesarias.

El inventory seleccionará cada tecnología de forma independiente, por ejemplo `postgresql_version: "17"` o `php_version: "8.4"`. Cambiar una versión soportada solo modifica inventory. Agregar una versión todavía no soportada amplía exclusivamente la carpeta de esa tecnología y sus tests; no obliga a tocar las demás.

No deben guardarse secretos sin cifrar. Los inventarios reales, playbooks,
plantillas, pruebas y documentación operativa permanecen bajo `deploy/`.

## Entrada rápida

```bash
cd deploy/provisioning
ansible-galaxy collection install -r requirements.yml
ansible-playbook -i inventories/vagrant/single/hosts.yml site.yml
ansible-playbook -i inventories/vagrant/split/hosts.yml site.yml
```

Consultar [`docs/deployment-guide.md`](docs/deployment-guide.md) para operación y
[`docs/production-deployment.md`](docs/production-deployment.md) antes de usar
servidores institucionales.
