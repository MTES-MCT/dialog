FROM postgis/postgis:14-3.3

RUN apt-get update
RUN apt-get install -y gdal-bin wget unzip p7zip

COPY run.sh junctions_json.sql /data/
