# Despliegue en Azure App Service (Linux, PHP 8.2+)

## 1. Base de datos
App Service no incluye MySQL. Crea **Azure Database for MySQL – Flexible Server** e importa tu dump:

    mysqldump -h 127.0.0.1 -P 3309 -u root artesanias_wayuu > artesanias_wayuu.sql
    mysql -h <servidor>.mysql.database.azure.com -u <admin> -p --ssl-mode=REQUIRED artesanias_wayuu < artesanias_wayuu.sql

En el servidor MySQL habilita el acceso desde Azure (regla de firewall "Allow Azure services") o usa VNet.

## 2. App Service > Variables de entorno
    DB_HOST = <servidor>.mysql.database.azure.com
    DB_PORT = 3306
    DB_NAME = artesanias_wayuu
    DB_USER = <usuario>
    DB_PASS = <contraseña>
    DB_SSL  = 1

## 3. Comando de inicio (Configuración > Configuración de la pila)
    bash /home/site/wwwroot/startup.sh

No actives WEBSITE_RUN_FROM_PACKAGE: las fotos de productos se suben a disco.

## 4. Despliegue
Sube el ZIP (los archivos van en la raíz, no dentro de una carpeta):

    az webapp deploy --resource-group <rg> --name <app> --src-path artesanias_wayuu_azure.zip --type zip

Activa "Solo HTTPS". Revisa logs en Log stream si algo falla.
