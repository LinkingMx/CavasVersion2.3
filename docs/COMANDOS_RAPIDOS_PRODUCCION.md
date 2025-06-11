# 🚀 COMANDOS RÁPIDOS PARA RÉPLICA EN PRODUCCIÓN

## ⚡ IMPLEMENTACIÓN RÁPIDA (1 comando)

```bash
# Descargar y ejecutar script automatizado
wget https://tu-repo.com/setup-production-storage.sh
chmod +x setup-production-storage.sh
./setup-production-storage.sh
```

## 📋 COMANDOS MANUALES ESENCIALES

### 1. **Estructura básica**

```bash
mkdir -p storage/app/{private/imports,public/imports,templates}
mkdir -p storage/{logs,framework/cache/data}
chmod -R 775 storage/ bootstrap/cache/
```

### 2. **Enlace simbólico**

```bash
php artisan storage:link
```

### 3. **Variables de entorno**

```bash
echo "FILESYSTEM_DISK=local" >> .env
echo "FILAMENT_FILESYSTEM_DISK=public" >> .env
echo "QUEUE_CONNECTION=database" >> .env
echo "DB_QUEUE_RETRY_AFTER=3600" >> .env
```

### 4. **Migraciones**

```bash
php artisan migrate --force
```

### 5. **Cron jobs**

```bash
# Agregar a crontab -e
0 2 * * * cd /var/www/tu-app && php artisan import:clean --days=7
*/15 * * * * cd /var/www/tu-app && php artisan import:monitor
```

### 6. **Iniciar queue worker**

```bash
php artisan queue:work --timeout=3600 &
```

## 🔧 CONFIGURACIÓN SERVIDOR WEB

### **Nginx**

```nginx
client_max_body_size 50M;
fastcgi_read_timeout 3600s;
```

### **Apache**

```apache
php_admin_value upload_max_filesize 50M
php_admin_value memory_limit 1024M
```

### **PHP (php.ini)**

```ini
upload_max_filesize = 50M
post_max_size = 50M
memory_limit = 1024M
max_execution_time = 3600
```

## ✅ VERIFICACIÓN RÁPIDA

```bash
# Verificar estructura
ls -la storage/app/

# Verificar permisos
ls -ld storage/app/private/imports

# Verificar symlink
ls -la public/storage

# Probar sistema
php artisan import:monitor
```

## 🎯 RESULTADO: SISTEMA LISTO EN < 5 MINUTOS!
