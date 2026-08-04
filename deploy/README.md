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
├── profiles/
├── orchestration/
├── technologies/
└── vagrant/
```

Cada implementación concreta se guardará bajo `technologies/<capacidad>/<tecnología>/<versión>/`, junto con su documentación y pruebas. Los perfiles solo seleccionarán combinaciones validadas. Incorporar otra versión significa agregar otra carpeta y otro perfil; no editar la implementación estable existente.

No deben guardarse secretos sin cifrar. Los inventarios reales, playbooks, plantillas, pruebas de infraestructura y documentación operativa específica del despliegue deberán permanecer bajo `deploy/`.
