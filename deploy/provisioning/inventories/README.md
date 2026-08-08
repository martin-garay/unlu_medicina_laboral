# Inventarios

## Testing

`inventories/testing/hosts.yml` apunta a la instancia de pruebas:

- dominio: `avisos-pruebas.unlu.edu.ar`
- servidor: `170.210.96.164`
- usuario SSH inicial: `mgaray`
- bastion: `martin@170.210.103.133:46659`

La conexión usa un `ProxyCommand` equivalente a `ProxyJump`, por lo que Ansible
puede ejecutarse desde la máquina local sin instalar Ansible en la máquina
intermedia de la Universidad:

```bash
ssh -o ProxyCommand='ssh -o IdentitiesOnly=yes -p 46659 -W %h:%p martin@170.210.103.133' \
  -o IdentitiesOnly=yes mgaray@170.210.96.164
```

Para operación no interactiva, configurar una clave SSH explícita. El inventory
usa `IdentitiesOnly=yes` para evitar que el bastion corte la conexión por exceso
de claves ofrecidas por `ssh-agent`. Si la clave no es una identidad default de
SSH, declarar `ansible_ssh_private_key_file` en el host o usar un alias en
`~/.ssh/config` con `IdentityFile`. Si `sudo -n true` falla en el servidor destino,
el despliegue requerirá `--ask-become-pass` o una regla sudo acordada con
infraestructura.

Antes de ejecutar `site.yml`, reemplazar:

- `application_release_id` por un tag o SHA inmutable;
- `deploy_user_public_keys` por la clave pública autorizada para el usuario `deploy`;
- rutas de certificado si `tls_provider: provided` no usa los defaults de `group_vars/all.yml`.

Validaciones sugeridas:

```bash
ansible-inventory -i inventories/testing/hosts.yml --graph
ansible-playbook -i inventories/testing/hosts.yml site.yml --syntax-check
ansible-playbook -i inventories/testing/hosts.yml site.yml --check --diff
```

## Production

`inventories/production/hosts.example.yml` es una plantilla para
`avisos.unlu.edu.ar`. Copiarla como `hosts.yml`, reemplazar hosts, redes y claves,
y mantenerla sin secretos. El inventory real no debe versionarse.
