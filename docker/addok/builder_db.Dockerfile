FROM postgis/postgis:14-3.3-alpine

RUN apk update
RUN apk add gdal wget unzip p7zip

COPY run.sh junctions_json.sql /data/
