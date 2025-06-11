# 📁 Análisis del Sistema de Storage - Réplica para Producción

## 🔍 ANÁLISIS ACTUAL DEL SISTEMA

### 📊 **Estructura de Storage Detectada:**

```
storage/
├── app/
│   ├── private/
│   │   ├── imports/           # Archivos de importación (temporal)
│   │   └── livewire-tmp/      # Archivos temporales de Livewire
│   ├── public/
│   │   ├── imports/           # Archivos públicos de importación
│   │   ├── ticket-photos/     # Fotos de tickets
│   │   └── gcore-cavas.png    # Logo de la aplicación
│   └── templates/
│       └── product_import_template.xlsx  # Plantilla de importación
├── framework/
│   └── cache/
│       └── data/              # Cache de archivos
├── logs/
│   └── laravel.log            # Logs del sistema
└── debugbar/                  # Debug toolbar files
```

### ⚙️ **Configuración de Discos:**

1. **`local` (privado)**: `storage_path('app/private')`
2. **`public`**: `storage_path('app/public')` 
3. **Filament default**: `public` disk
4. **Symlink**: `public/storage → storage/app/public`

---

## 🚀 COMANDOS PARA REPLICAR EN PRODUCCIÓN

### 1️⃣ **Estructura de Directorios**

```bash
# Crear estructura completa de storage
mkdir -p storage/app/private/imports
mkdir -p storage/app/private/livewire-tmp
mkdir -p storage/app/public/imports
mkdir -p storage/app/public/ticket-photos
mkdir -p storage/app/templates
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p storage/debugbar
mkdir -p bootstrap/cache
```

### 2️⃣ **Permisos de Directorios**

```bash
# Establecer permisos correctos para Laravel
sudo chown -R www-data:www-data storage/
sudo chown -R www-data:www-data bootstrap/cache/
sudo chmod -R 755 storage/
sudo chmod -R 755 bootstrap/cache/

# Permisos específicos para uploads
sudo chmod -R 775 storage/app/
sudo chmod -R 775 storage/logs/
```

### 3️⃣ **Symlink de Storage**

```bash
# Crear enlace simbólico para archivos públicos
php artisan storage:link

# Verificar que el enlace se creó correctamente
ls -la public/storage
```

### 4️⃣ **Archivos de Configuración**

```bash
# Copiar archivos de configuración críticos
cp config/filesystems.php /path/to/production/config/
cp config/queue.php /path/to/production/config/
cp config/filament.php /path/to/production/config/

# Copiar template de importación
cp storage/app/templates/product_import_template.xlsx /path/to/production/storage/app/templates/
```

### 5️⃣ **Variables de Entorno**

```bash
# Agregar al archivo .env de producción
echo "FILESYSTEM_DISK=local" >> .env
echo "FILAMENT_FILESYSTEM_DISK=public" >> .env
echo "QUEUE_CONNECTION=database" >> .env
echo "DB_QUEUE_RETRY_AFTER=3600" >> .env
```

### 6️⃣ **Configuración de PHP (php.ini)**

```bash
# Configuraciones para archivos grandes
echo "upload_max_filesize = 50M" >> /etc/php/8.1/fpm/php.ini
echo "post_max_size = 50M" >> /etc/php/8.1/fpm/php.ini
echo "memory_limit = 1024M" >> /etc/php/8.1/fpm/php.ini
echo "max_execution_time = 3600" >> /etc/php/8.1/fpm/php.ini
echo "max_input_time = 3600" >> /etc/php/8.1/fpm/php.ini

# Reiniciar PHP-FPM
sudo systemctl restart php8.1-fpm
```

### 7️⃣ **Base de Datos**

```bash
# Ejecutar migraciones necesarias
php artisan migrate

# Crear tablas específicas para imports (si no existen)
php artisan make:migration create_imports_table
php artisan make:migration create_exports_table
php artisan migrate
```

---

## 🔧 CONFIGURACIONES ESPECÍFICAS

### **A. Configuración de Nginx**

```nginx
# /etc/nginx/sites-available/your-site
server {
    # ... configuración existente ...
    
    # Configuración para archivos grandes
    client_max_body_size 50M;
    client_body_timeout 300s;
    client_header_timeout 300s;
    
    # Configuración para archivos estáticos
    location /storage {
        alias /var/www/your-app/storage/app/public;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Timeout para procesamiento
    fastcgi_read_timeout 3600s;
    fastcgi_send_timeout 3600s;
}

# Reiniciar Nginx
sudo systemctl reload nginx
```

### **B. Configuración de Apache**

```apache
# /etc/apache2/sites-available/your-site.conf
<VirtualHost *:80>
    # ... configuración existente ...
    
    # Timeout para archivos grandes
    Timeout 3600
    
    # Configuración PHP
    php_admin_value upload_max_filesize 50M
    php_admin_value post_max_size 50M
    php_admin_value memory_limit 1024M
    php_admin_value max_execution_time 3600
    
    # Alias para storage
    Alias /storage /var/www/your-app/storage/app/public
    <Directory "/var/www/your-app/storage/app/public">
        Options -Indexes
        AllowOverride None
        Require all granted
    </Directory>
</VirtualHost>

# Reiniciar Apache
sudo systemctl reload apache2
```

---

## 🔄 COMANDOS DE MANTENIMIENTO

### **Cron Jobs Recomendados**

```bash
# Editar crontab
crontab -e

# Agregar estas líneas:
# Limpiar archivos de importación antiguos (diariamente a las 2 AM)
0 2 * * * cd /var/www/your-app && php artisan import:clean --days=7

# Limpiar cache de aplicación (cada hora)
0 * * * * cd /var/www/your-app && php artisan cache:clear

# Limpiar logs antiguos (semanalmente)
0 0 * * 0 cd /var/www/your-app && find storage/logs -name "*.log" -mtime +30 -delete
```

### **Scripts de Monitoreo**

```bash
# Crear script de monitoreo
cat > /usr/local/bin/monitor-imports.sh << 'EOF'
#!/bin/bash
cd /var/www/your-app
php artisan import:monitor
EOF

chmod +x /usr/local/bin/monitor-imports.sh

# Ejecutar cada 15 minutos
echo "*/15 * * * * /usr/local/bin/monitor-imports.sh" | crontab -
```

---

## 🚦 VERIFICACIÓN DEL SISTEMA

### **Comandos de Verificación**

```bash
# 1. Verificar estructura de directorios
find storage/ -type d -exec ls -ld {} \;

# 2. Verificar permisos
ls -la storage/app/
ls -la storage/logs/
ls -la bootstrap/cache/

# 3. Verificar symlink
ls -la public/storage

# 4. Verificar configuración PHP
php -i | grep -E "(upload_max_filesize|post_max_size|memory_limit|max_execution_time)"

# 5. Probar sistema de importación
php artisan test:import-job storage/app/templates/product_import_template.xlsx

# 6. Verificar cola de trabajos
php artisan queue:work --once

# 7. Monitorear sistema
php artisan import:monitor
```

---

## 📋 CHECKLIST DE IMPLEMENTACIÓN

### ✅ **Pre-implementación**
- [ ] Backup de la aplicación actual
- [ ] Verificar versiones de PHP (8.1+)
- [ ] Verificar espacio en disco (mínimo 5GB)
- [ ] Verificar permisos de usuario web

### ✅ **Durante la implementación**
- [ ] Crear estructura de directorios
- [ ] Establecer permisos correctos
- [ ] Configurar PHP.ini
- [ ] Configurar servidor web (Nginx/Apache)
- [ ] Crear symlink de storage
- [ ] Configurar variables de entorno
- [ ] Ejecutar migraciones

### ✅ **Post-implementación**
- [ ] Probar subida de archivos
- [ ] Verificar procesamiento de importaciones
- [ ] Configurar cron jobs
- [ ] Probar comandos de monitoreo
- [ ] Verificar logs
- [ ] Documentar configuración final

---

## 🔐 CONSIDERACIONES DE SEGURIDAD

```bash
# 1. Proteger directorios sensibles
echo "deny from all" > storage/app/private/.htaccess
echo "deny from all" > storage/logs/.htaccess

# 2. Configurar firewall para archivos grandes
# (Ajustar según tu firewall)

# 3. Monitorear uso de espacio
echo "*/30 * * * * df -h /var/www/your-app/storage | mail -s 'Storage Usage' admin@domain.com" | crontab -

# 4. Backup automático de templates
echo "0 3 * * * cp /var/www/your-app/storage/app/templates/* /backup/templates/" | crontab -
```

---

## 🎯 RESULTADO FINAL

Una vez ejecutados todos los comandos, tendrás:

✅ **Sistema de storage replicado**  
✅ **Permisos correctos configurados**  
✅ **Servidor web optimizado para archivos grandes**  
✅ **Cron jobs para mantenimiento automático**  
✅ **Monitoreo del sistema activo**  
✅ **Logs y debugging configurados**  

**Tu sistema de importación estará listo para producción! 🚀**
