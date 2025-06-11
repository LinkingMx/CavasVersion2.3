#!/bin/bash

# =============================================================================
# Script de Implementación del Sistema de Storage en Producción
# Para el Sistema de Importación de Productos - Laravel + Filament
# =============================================================================

set -e  # Salir si hay algún error

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para imprimir con colores
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_step() {
    echo -e "${BLUE}[STEP]${NC} $1"
}

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    print_error "Este script debe ejecutarse desde el directorio raíz de Laravel"
    exit 1
fi

print_status "🚀 Iniciando configuración del sistema de storage para producción..."

# =============================================================================
# PASO 1: Crear estructura de directorios
# =============================================================================
print_step "1. Creando estructura de directorios..."

directories=(
    "storage/app/private/imports"
    "storage/app/private/livewire-tmp"
    "storage/app/public/imports"
    "storage/app/public/ticket-photos"
    "storage/app/templates"
    "storage/framework/cache/data"
    "storage/framework/sessions"
    "storage/framework/views"
    "storage/logs"
    "storage/debugbar"
    "bootstrap/cache"
)

for dir in "${directories[@]}"; do
    if [ ! -d "$dir" ]; then
        mkdir -p "$dir"
        print_status "✅ Creado: $dir"
    else
        print_warning "⚠️  Ya existe: $dir"
    fi
done

# =============================================================================
# PASO 2: Establecer permisos
# =============================================================================
print_step "2. Configurando permisos..."

# Detectar el usuario del servidor web
if command -v apache2 >/dev/null 2>&1; then
    WEB_USER="www-data"
elif command -v nginx >/dev/null 2>&1; then
    WEB_USER="www-data"
else
    WEB_USER=$(whoami)
    print_warning "No se detectó Apache/Nginx. Usando usuario actual: $WEB_USER"
fi

# Configurar permisos
chown -R $WEB_USER:$WEB_USER storage/ 2>/dev/null || print_warning "No se pudo cambiar propietario. Ejecutar como sudo si es necesario."
chown -R $WEB_USER:$WEB_USER bootstrap/cache/ 2>/dev/null || print_warning "No se pudo cambiar propietario de bootstrap/cache."

chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
chmod -R 775 storage/app/
chmod -R 775 storage/logs/

print_status "✅ Permisos configurados"

# =============================================================================
# PASO 3: Crear enlace simbólico
# =============================================================================
print_step "3. Creando enlace simbólico de storage..."

if [ ! -L "public/storage" ]; then
    php artisan storage:link
    print_status "✅ Enlace simbólico creado"
else
    print_warning "⚠️  El enlace simbólico ya existe"
fi

# =============================================================================
# PASO 4: Verificar template de importación
# =============================================================================
print_step "4. Verificando template de importación..."

if [ ! -f "storage/app/templates/product_import_template.xlsx" ]; then
    print_warning "⚠️  Template de importación no encontrado. Creando uno básico..."
    
    # Crear un template básico si no existe
    cat > "storage/app/templates/template_info.txt" << 'EOF'
Para crear el template de Excel:
1. Crear archivo Excel con las columnas: name, external_sku, price
2. Guardarlo como product_import_template.xlsx
3. Colocarlo en este directorio
EOF
else
    print_status "✅ Template de importación encontrado"
fi

# =============================================================================
# PASO 5: Configurar archivo .env
# =============================================================================
print_step "5. Verificando configuración .env..."

ENV_VARS=(
    "FILESYSTEM_DISK=local"
    "FILAMENT_FILESYSTEM_DISK=public"
    "QUEUE_CONNECTION=database"
    "DB_QUEUE_RETRY_AFTER=3600"
)

for var in "${ENV_VARS[@]}"; do
    KEY=$(echo $var | cut -d'=' -f1)
    VALUE=$(echo $var | cut -d'=' -f2)
    
    if grep -q "^$KEY=" .env; then
        print_warning "⚠️  $KEY ya existe en .env"
    else
        echo "$var" >> .env
        print_status "✅ Agregado: $var"
    fi
done

# =============================================================================
# PASO 6: Verificar configuraciones PHP
# =============================================================================
print_step "6. Verificando configuraciones PHP..."

# Mostrar configuraciones actuales
print_status "📊 Configuraciones PHP actuales:"
php -r "
echo 'upload_max_filesize: ' . ini_get('upload_max_filesize') . PHP_EOL;
echo 'post_max_size: ' . ini_get('post_max_size') . PHP_EOL;
echo 'memory_limit: ' . ini_get('memory_limit') . PHP_EOL;
echo 'max_execution_time: ' . ini_get('max_execution_time') . PHP_EOL;
"

# Detectar ubicación de php.ini
PHP_INI=$(php --ini | grep "Loaded Configuration File" | cut -d':' -f2 | xargs)
print_status "📄 Archivo php.ini: $PHP_INI"

print_warning "⚠️  Asegúrate de que php.ini tenga estas configuraciones:"
echo "   upload_max_filesize = 50M"
echo "   post_max_size = 50M"
echo "   memory_limit = 1024M"
echo "   max_execution_time = 3600"

# =============================================================================
# PASO 7: Ejecutar migraciones
# =============================================================================
print_step "7. Ejecutando migraciones..."

php artisan migrate --force
print_status "✅ Migraciones ejecutadas"

# =============================================================================
# PASO 8: Crear archivos de protección
# =============================================================================
print_step "8. Creando archivos de protección..."

# Proteger directorios sensibles
echo "deny from all" > storage/app/private/.htaccess
echo "deny from all" > storage/logs/.htaccess
print_status "✅ Archivos .htaccess creados"

# =============================================================================
# PASO 9: Crear scripts de mantenimiento
# =============================================================================
print_step "9. Creando scripts de mantenimiento..."

# Script de limpieza
cat > "cleanup-imports.sh" << 'EOF'
#!/bin/bash
# Script de limpieza de archivos de importación
cd "$(dirname "$0")"
php artisan import:clean --days=7
php artisan cache:clear
find storage/logs -name "*.log" -mtime +30 -delete 2>/dev/null || true
echo "Limpieza completada: $(date)"
EOF

chmod +x cleanup-imports.sh
print_status "✅ Script de limpieza creado: cleanup-imports.sh"

# Script de monitoreo
cat > "monitor-system.sh" << 'EOF'
#!/bin/bash
# Script de monitoreo del sistema
cd "$(dirname "$0")"
echo "=== Monitor del Sistema de Importación ==="
echo "Fecha: $(date)"
echo ""
php artisan import:monitor
echo ""
echo "=== Uso de Espacio ==="
du -sh storage/app/
echo ""
echo "=== Procesos de Cola ==="
pgrep -f "queue:work" > /dev/null && echo "✅ Queue worker activo" || echo "❌ Queue worker no activo"
EOF

chmod +x monitor-system.sh
print_status "✅ Script de monitoreo creado: monitor-system.sh"

# =============================================================================
# PASO 10: Configurar cron jobs (opcional)
# =============================================================================
print_step "10. Configurando cron jobs..."

CRON_JOBS="
# Limpieza diaria de archivos de importación (2 AM)
0 2 * * * cd $(pwd) && ./cleanup-imports.sh >> storage/logs/cleanup.log 2>&1

# Monitoreo cada 15 minutos
*/15 * * * * cd $(pwd) && ./monitor-system.sh >> storage/logs/monitor.log 2>&1
"

print_status "📋 Cron jobs recomendados:"
echo "$CRON_JOBS"
print_warning "⚠️  Para instalar cron jobs, ejecuta: crontab -e"

# =============================================================================
# PASO 11: Pruebas del sistema
# =============================================================================
print_step "11. Ejecutando pruebas del sistema..."

# Verificar comandos artisan
if php artisan list | grep -q "import:monitor"; then
    print_status "✅ Comando import:monitor disponible"
else
    print_error "❌ Comando import:monitor no encontrado"
fi

if php artisan list | grep -q "import:clean"; then
    print_status "✅ Comando import:clean disponible"
else
    print_error "❌ Comando import:clean no encontrado"
fi

# Verificar estructura
print_status "📁 Verificando estructura de directorios:"
ls -la storage/app/

# =============================================================================
# FINALIZACIÓN
# =============================================================================
print_step "🎉 Configuración completada!"

print_status "✅ RESUMEN DE CONFIGURACIÓN:"
echo "   📁 Estructura de directorios creada"
echo "   🔐 Permisos configurados"
echo "   🔗 Enlace simbólico creado"
echo "   ⚙️  Variables de entorno configuradas"
echo "   🗃️  Migraciones ejecutadas"
echo "   🛡️  Archivos de protección creados"
echo "   📜 Scripts de mantenimiento creados"

print_warning "⚠️  PRÓXIMOS PASOS:"
echo "   1. Verificar configuraciones PHP en php.ini"
echo "   2. Configurar servidor web (Nginx/Apache) para archivos grandes"
echo "   3. Instalar cron jobs recomendados"
echo "   4. Iniciar queue worker: php artisan queue:work --timeout=3600"
echo "   5. Probar importación con: php artisan test:import-job archivo.csv"

print_status "🚀 Tu sistema está listo para producción!"
