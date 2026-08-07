# Matriz de validación del despliegue

## Baseline validado

| Capa | Selección |
|---|---|
| SO | Debian 13.1 |
| Web | Apache 2.4 + MPM event + PHP-FPM |
| PHP | 8.4 |
| PostgreSQL | 17 |
| Ansible | roles locales y colecciones fijadas en `requirements.yml` |

Las versiones se seleccionan independientemente por inventory. Esta matriz no
afirma soporte para una combinación nueva hasta agregar y ejecutar sus tareas
específicas dentro del rol afectado.

## Evidencia 2026-08-07

| Control | single | split |
|---|---:|---:|
| VM Vagrant y SSH por clave | OK | OK |
| `site.yml` desde VM limpia | OK | componentes convergidos |
| segunda convergencia | OK | OK |
| PostgreSQL local/remoto | OK | OK |
| HTTPS con CA confiable y redirect | OK | OK |
| scheduler registrado | OK | OK |
| firewall sin perder SSH/DB/HTTPS | OK | OK |
| backup DB y archivos | OK | OK |
| restore DB temporal y lectura tar | OK | OK |
| rollback exitoso | OK | OK |
| rescate ante rollback fallido | — | OK |
| diagnóstico operativo | OK | OK |
| `--check --diff` | OK | OK |

La segunda convergencia informa solamente la sincronización del artefacto local
como cambio esperado. La configuración de servicios y archivos queda estable.

## Validaciones estáticas

- `yamllint`: aprobado con `.yamllint` versionado;
- `ansible-lint`: aprobado sin violaciones con perfil `production`;
- syntax-check: todos los playbooks y `site.yml` contra inventories single/split;
- inventory productivo de ejemplo: parseado y con grupos app/db.

Los avisos de deprecación observados provienen de `ansible.posix.synchronize`
2.1.0 y no son fallos de reglas. La colección está fijada y deberá actualizarse
en un milestone independiente con repetición de esta matriz.

## Límites antes de producción real

- completar hosts, redes, dominio, DNS, certificado y acceso institucional;
- resolver validación de firma de los POST de Meta;
- confirmar política de datos sensibles en logs;
- implementar storage real de adjuntos médicos (hoy los drivers son metadata);
- confirmar RPO/RTO y retención con responsables institucionales;
- mantener una segunda copia futura: los backups actuales están en el servidor.
