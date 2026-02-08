#!/bin/bash

# Este script se ejecuta al iniciar el contenedor de SQL Server para restaurar una base
# db/init/setup.sh

# Esperar a que SQL Server esté listo para aceptar conexiones
# Este bucle intentará conectarse cada 5 segundos hasta que tenga éxito
echo "Esperando a que SQL Server esté listo..."
# Usar un bucle más robusto para verificar la conexión
until /opt/mssql-tools/bin/sqlcmd -S localhost -U SA -P "$SA_PASSWORD" -Q "SELECT 1" &> /dev/null; do
    echo "SQL Server no está listo. Reintentando en 5 segundos..."
    sleep 5
done
echo "SQL Server está listo."

# Ruta donde se copiará el backup dentro del contenedor
BACKUP_FILE="/var/opt/mssql/backup/gestion_asistencias_vacaciones_insip_db.bak"
DB_NAME="gestion_asistencias_vacaciones_insip_db"

# Nombres lógicos obtenidos del backup usando RESTORE FILELISTONLY
# ¡CORREGIDOS SEGÚN LA SALIDA DE RESTORE FILELISTONLY!
LOGICAL_DATA_NAME="gestion_asistencias_vacaciones_inisip_db"
LOGICAL_LOG_NAME="gestion_asistencias_vacaciones_inisip_db_log"

# Verificar si la base de datos ya existe antes de restaurar
DB_EXISTS=$(/opt/mssql-tools/bin/sqlcmd -S localhost -U SA -P "$SA_PASSWORD" -h -1 -Q "SET NOCOUNT ON; SELECT COUNT(*) FROM sys.databases WHERE name = N'$DB_NAME'" | head -n 1 | tr -d '[:space:]')

if [ "$DB_EXISTS" -eq 0 ]; then
    echo "La base de datos '$DB_NAME' no existe. Iniciando restauración..."

    # Comando para restaurar la base de datos desde el archivo .bak
    /opt/mssql-tools/bin/sqlcmd -S localhost -U SA -P "$SA_PASSWORD" -Q "RESTORE DATABASE [$DB_NAME] FROM DISK = N'$BACKUP_FILE' WITH FILE = 1, MOVE N'$LOGICAL_DATA_NAME' TO N'/var/opt/mssql/data/$DB_NAME.mdf', MOVE N'$LOGICAL_LOG_NAME' TO N'/var/opt/mssql/data/$DB_NAME_log.ldf', NOUNLOAD, REPLACE, STATS = 5"

    if [ $? -eq 0 ]; then
        echo "Restauración de la base de datos '$DB_NAME' completada exitosamente."
    else
        echo "¡ERROR! Falló la restauración de la base de datos '$DB_NAME'."
        exit 1
    fi
else
    echo "La base de datos '$DB_NAME' ya existe. Saltando la restauración."
fi

# El contenedor permanecerá en ejecución por el CMD/ENTRYPOINT del Dockerfile base.
# No es necesario agregar `tail -f /dev/null` aquí para la mayoría de las imágenes oficiales de SQL Server.