# Roles

Los roles se incorporarán por milestone y por tecnología:

- `common`
- `firewall`
- `apache`
- `php`
- `postgresql`
- `application`
- `laravel_scheduler`
- `tls`
- `backup`
- `monitoring`

Cada rol implementado debe contener README, defaults, handlers y archivos específicos por versión solo cuando sean necesarios. Los playbooks orquestan roles; no contienen nombres de paquetes, servicios o rutas propios de una tecnología.

