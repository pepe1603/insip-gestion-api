#docker/sqlserver/Dockerfile
FROM mcr.microsoft.com/mssql/server:2019-latest

# Cambiar a usuario root para instalar paquetes
USER root

# Instalar herramientas de SQL Server
RUN apt-get update && \
    apt-get install -y curl apt-transport-https gnupg && \
    curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - && \
    curl https://packages.microsoft.com/config/ubuntu/20.04/prod.list > /etc/apt/sources.list.d/mssql-release.list && \
    apt-get update && \
    ACCEPT_EULA=Y apt-get install -y msodbcsql17 mssql-tools unixodbc-dev && \
    # Limpiar para reducir tamaño
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Volver al usuario mssql para ejecutar SQL Server
USER mssql
