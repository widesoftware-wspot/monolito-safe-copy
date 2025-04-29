FROM sa-saopaulo-1.ocir.io/grtenqmoni5x/wspot-php5.6-container-image:v1.0.18

COPY . /sites/wspot.com.br/

RUN rm /sites/wspot.com.br/web/app_dev.php