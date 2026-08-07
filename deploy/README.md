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
├── docs/
└── provisioning/
    ├── ansible.cfg
    ├── requirements.yml
    ├── inventories/
    ├── group_vars/
    ├── roles/
    ├── bin/
    └── Vagrantfile
```

La primera versión seguirá la forma familiar del provisioning de elecciones: playbooks en `deploy/provisioning/`, variables en `group_vars/`, inventarios por entorno y roles estándar en `roles/`.

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
