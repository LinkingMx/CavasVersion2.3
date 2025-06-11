# Configuraciones recomendadas para trabajos de importación grandes

# En config/database.php - para mejorar rendimiento de MySQL/PostgreSQL

# Agregar estas configuraciones dentro de la conexión mysql:

'options' => [
PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'",
PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
],

# En .env - Configuraciones de memoria y timeout

QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=3600

# Para el archivo php.ini (si tienes acceso):

memory_limit=1024M
max_execution_time=3600
upload_max_filesize=100M
post_max_size=100M

# Para archivos muy grandes (más de 5000 registros), considera usar:

# - Queues dedicadas

# - Background processing con notificaciones por email

# - Dividir archivos grandes en chunks más pequeños
