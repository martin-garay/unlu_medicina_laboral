# Despliegue

Esta carpeta es la fuente de verdad para toda la documentación y el futuro código de despliegue del proyecto.

## Estado actual

La implementación comenzó con la estructura mínima de Ansible y sus inventories de validación. Todavía no existen roles que aprovisionen servicios ni una configuración productiva ejecutable.

## Documentación

- [`docs/ansible-deployment-plan.md`](docs/ansible-deployment-plan.md): relevamiento del repositorio, arquitectura objetivo y plan completo de implementación con Ansible.
- [`docs/deployment-guide.md`](docs/deployment-guide.md): entrada operativa y flujo general de despliegue.
- [`docs/production-deployment.md`](docs/production-deployment.md): controles y procedimiento previsto para producción.

## Estructura futura

Cuando comience la implementación se incorporarán aquí, de forma incremental:

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

No deben guardarse secretos sin cifrar. Los inventarios reales, playbooks, plantillas, pruebas de infraestructura y documentación operativa específica del despliegue deberán permanecer bajo `deploy/`.
