# Despliegue

Esta carpeta es la fuente de verdad para toda la documentación y el futuro código de despliegue del proyecto.

## Estado actual

La etapa vigente es únicamente de relevamiento y planificación. Todavía no existen roles, playbooks, inventarios ejecutables ni configuración productiva.

## Documentación

- [`docs/ansible-deployment-plan.md`](docs/ansible-deployment-plan.md): relevamiento del repositorio, arquitectura objetivo y plan completo de implementación con Ansible.

## Estructura futura

Cuando comience la implementación se incorporarán aquí, de forma incremental:

```text
deploy/
├── README.md
├── docs/
├── ansible.cfg
├── requirements.yml
├── inventories/
├── playbooks/
├── orchestration/
├── technologies/
└── vagrant/
```

Cada implementación concreta se guardará bajo `technologies/<capacidad>/<tecnología>/`, junto con su documentación, rol y pruebas. Las diferencias por versión vivirán dentro de `vars/versions/`, `tasks/versions/` o `templates/versions/` únicamente cuando sean necesarias.

El inventory seleccionará cada tecnología de forma independiente, por ejemplo `postgresql_version: "17"` o `php_version: "8.4"`. Cambiar una versión soportada solo modifica inventory. Agregar una versión todavía no soportada amplía exclusivamente la carpeta de esa tecnología y sus tests; no obliga a tocar las demás.

No deben guardarse secretos sin cifrar. Los inventarios reales, playbooks, plantillas, pruebas de infraestructura y documentación operativa específica del despliegue deberán permanecer bajo `deploy/`.
