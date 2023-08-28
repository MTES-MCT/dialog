FROM postgis/postgis:14-3.3

# Install dependencies
RUN apt-get update
RUN apt-get install -y --no-install-recommends gdal-bin wget unzip p7zip-full

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

COPY run.sh junctions_json.sql /data/
