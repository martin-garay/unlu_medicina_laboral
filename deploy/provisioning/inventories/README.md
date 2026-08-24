# Inventarios

## Testing

`inventories/testing/hosts.yml` apunta a la instancia de pruebas:

- dominio: `avisos-pruebas.unlu.edu.ar`
- servidor: `170.210.96.164`
- usuario SSH operativo: `deploy`
- clave SSH operativa local: `deploy/.local/ssh/deploy_ed25519`
- salto actual: `martin@170.210.103.133:46659`
- acceso inicial confirmado: `mgaray@170.210.96.164` vía salto y elevación manual con `su -`
- red administrativa observada por el servidor: `170.210.103.133`
- security/hardening/firewall: no gestionados por Ansible en testing inicial
  (`security_enabled: false`, `firewall_enabled: false`)

El host de inventory `avisos-testing` usa `ansible_host:
unlu-medicina-testing-deploy`, un alias local que debe existir en
`~/.ssh/config`. Así el salto queda fuera del inventory versionado.

El salto actual depende de una máquina institucional usada como punto de acceso.
Mantenerlo en `~/.ssh/config` del operador y no hardcodearlo en el inventory:

```sshconfig
Host unlu-pc
  HostName 170.210.103.133
  Port 46659
  User martin
  IdentitiesOnly yes
  IdentityFile ~/.ssh/id_ed25519

Host unlu-medicina-testing
  HostName 170.210.96.164
  User mgaray
  ProxyJump unlu-pc
  IdentitiesOnly yes
  IdentityFile ~/.ssh/id_ed25519

Host unlu-medicina-testing-deploy
  HostName 170.210.96.164
  User deploy
  ProxyJump unlu-pc
  IdentitiesOnly yes
  IdentityFile /home/mgaray/UNLu/unlu_medicina_laboral/deploy/.local/ssh/deploy_ed25519
```

Validar primero el acceso manual:

```bash
ssh unlu-medicina-testing
echo "$SSH_CONNECTION"
whoami
hostname
su -
```

El valor observado de `SSH_CONNECTION` en testing fue:

```text
170.210.103.133 <puerto-origen> 170.210.96.164 22
```

Esto confirma que el servidor ve la administración desde `170.210.103.133`.

Si el servidor todavía no tiene el usuario `deploy`, ejecutar una única vez el
bootstrap. Si ya existe acceso SSH root no interactivo, puede usarse el playbook:

```bash
ansible-playbook -i inventories/testing/hosts.yml playbooks/bootstrap-access.yml \
  -u root \
  -e bootstrap_access_enabled=true \
  -e bootstrap_access_public_keys="['$(cat ../.local/ssh/deploy_ed25519.pub)']"
```

Si el acceso privilegiado disponible es manual con `su -`, hacer el bootstrap
como root dentro del servidor:

```bash
DEPLOY_PUBKEY='<contenido de deploy/.local/ssh/deploy_ed25519.pub>'

apt-get update
DEBIAN_FRONTEND=noninteractive apt-get install -y sudo python3

groupadd --system deploy 2>/dev/null || true
id -u deploy >/dev/null 2>&1 || useradd \
  --gid deploy \
  --groups sudo \
  --shell /bin/bash \
  --create-home \
  deploy

passwd -l deploy

install -d -o deploy -g deploy -m 0700 /home/deploy/.ssh
printf '%s\n' "$DEPLOY_PUBKEY" > /home/deploy/.ssh/authorized_keys
chown deploy:deploy /home/deploy/.ssh/authorized_keys
chmod 0600 /home/deploy/.ssh/authorized_keys

printf 'deploy ALL=(ALL:ALL) NOPASSWD: ALL\n' > /etc/sudoers.d/deploy
chown root:root /etc/sudoers.d/deploy
chmod 0440 /etc/sudoers.d/deploy
visudo -cf /etc/sudoers.d/deploy
```

Luego validar la operación normal:

```bash
ssh unlu-medicina-testing-deploy 'whoami; sudo -n true; python3 --version'
ansible all -i inventories/testing/hosts.yml -m ping
ansible all -i inventories/testing/hosts.yml -m command -a 'sudo -n true' --become=false
```

Para operación no interactiva, configurar una clave SSH explícita. El inventory
usa `IdentitiesOnly=yes` para evitar que SSH ofrezca claves no deseadas desde
`ssh-agent`. Si la clave no es una identidad default de SSH, declarar
`ansible_ssh_private_key_file` en el host o usar un alias en `~/.ssh/config` con
`IdentityFile`.

Antes de ejecutar `site.yml`, confirmar:

- `application_release_id` apunta al tag remoto previsto;
- `deploy_user_public_keys` contiene la clave pública autorizada para el usuario `deploy`;
- `security_enabled` sigue en `false` mientras no se gestione hardening desde Ansible;
- `firewall_enabled` sigue en `false` mientras no se gestione firewall desde Ansible;
- rutas de certificado si `tls_provider: provided` no usa los defaults de `group_vars/all.yml`.

Testing conserva inicialmente los controles institucionales existentes. El
servidor ya tiene reglas nftables restrictivas cargadas aunque
`nftables.service` figure `disabled/inactive`, incluyendo acceso SSH desde
varias IPs institucionales y el puerto `10050` desde `170.210.96.186`. No
habilitar `security_enabled` ni `firewall_enabled` hasta definir cómo preservar
esos accesos, servicios y la política SSH existente.

Si `ansible ping` falla por timeout a `170.210.96.164:22`, no ejecutar
`site.yml` todavía. Primero validar firewall externo, VPN/ruta institucional o
estado del servicio SSH desde una red autorizada.

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
