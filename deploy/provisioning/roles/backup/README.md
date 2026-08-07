# Rol `backup`

Instala backups locales separados para PostgreSQL y archivos persistentes. Genera
artefactos con checksum SHA-256 bajo `/var/backups/medicina-laboral`, permisos
`0700` y retención de 7 días, 31 días y 186 días para los niveles diario,
semanal y mensual. No transfiere datos fuera del servidor.
