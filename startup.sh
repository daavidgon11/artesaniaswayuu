#!/bin/bash
# Guarda las imágenes subidas en /home (persistente) para que no se pierdan
# al volver a desplegar. Comando de inicio en App Service:
#   bash /home/site/wwwroot/startup.sh
WWW=/home/site/wwwroot
DATA=/home/data/productos
mkdir -p "$DATA"
if [ -d "$WWW/assets/img/productos" ] && [ ! -L "$WWW/assets/img/productos" ]; then
  cp -n "$WWW"/assets/img/productos/* "$DATA"/ 2>/dev/null
  rm -rf "$WWW/assets/img/productos"
fi
[ -L "$WWW/assets/img/productos" ] || ln -s "$DATA" "$WWW/assets/img/productos"
chmod -R 775 "$DATA"
# Reinicia nginx/php-fpm con la config por defecto de la imagen
service nginx reload 2>/dev/null || true
