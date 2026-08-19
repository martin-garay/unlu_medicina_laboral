# Inventarios

## Testing

`inventories/testing/hosts.yml` apunta a la instancia de pruebas:

- dominio: `avisos-pruebas.unlu.edu.ar`
- servidor: `170.210.96.164`
- usuario SSH operativo: `deploy`
- bastion: no requerido para el servidor dedicado de testing

`ProxyCommand` era la instrucción SSH usada para llegar al servidor a través de
un host intermedio. Para el dedicado de testing se usa acceso SSH directo, por
lo que no debe quedar configurado en el inventory normal.

Si el servidor todavía no tiene el usuario `deploy`, ejecutar una única vez el
bootstrap con un usuario inicial privilegiado. Ese usuario inicial no debe quedar
guardado en `inventories/testing/hosts.yml`:

```bash
ansible-playbook -i inventories/testing/hosts.yml playbooks/bootstrap-access.yml \
  -u root \
  -e bootstrap_access_enabled=true \
  -e bootstrap_access_public_keys="['$(cat ~/.ssh/id_ed25519.pub)']"
```

Luego validar la operación normal:

```bash
ansible all -i inventories/testing/hosts.yml -m ping
ansible all -i inventories/testing/hosts.yml -m command -a 'sudo -n true' --become=false
```

Para operación no interactiva, configurar una clave SSH explícita. El inventory
usa `IdentitiesOnly=yes` para evitar que SSH ofrezca claves no deseadas desde
`ssh-agent`. Si la clave no es una identidad default de SSH, declarar
`ansible_ssh_private_key_file` en el host o usar un alias en `~/.ssh/config` con
`IdentityFile`.

Antes de ejecutar `site.yml`, reemplazar:

- `application_release_id` por un tag o SHA inmutable si no se usa el tag de testing previsto;
- `deploy_user_public_keys` por la clave pública autorizada para el usuario `deploy`;
- `ssh_allowed_networks` por el CIDR real desde donde se administrará el servidor;
- rutas de certificado si `tls_provider: provided` no usa los defaults de `group_vars/all.yml`.

Mientras no existan certificados institucionales, testing puede usar
`tls_provider: local_ca` para validar convergencia técnica. Esa CA local no sirve
para configurar el webhook público real de Meta sin un túnel o proxy que presente
un certificado público válido.

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
