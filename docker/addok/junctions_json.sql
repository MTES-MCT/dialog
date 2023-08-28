SELECT row_to_json(p) FROM
(
    SELECT j.*, c.nom as city, cc.code_postal as postcode
    FROM (SELECT 'junction_' || st_geohash(st_centroid(unnest(ST_ClusterWithin(st_intersection(r1.geometry, r2.geometry),0.0001)))) as id,
        'poi' as type,
        'junction' as poi,
        format('%s / %s', r1.name, r2.name) as name,
        st_y(st_centroid(unnest(ST_ClusterWithin(st_intersection(r1.geometry, r2.geometry),0.0005)))) as lat,
        st_x(st_centroid(unnest(ST_ClusterWithin(st_intersection(r1.geometry, r2.geometry),0.0005)))) as lon,
        '' as context,
        0 as rank
    FROM osm_roads r1
    JOIN osm_roads r2
        on (st_intersects(r1.geometry, r2.geometry))
    WHERE
        r1.name is not null and length(r1.name) > 3
        and r2.name is not null and length(r2.name) > 3
        and r1.name < r2.name
    GROUP BY r1.name, r2.name) as j
    JOIN (
        (SELECT geometry, nom, insee_com AS code_insee FROM communes)
        UNION ALL
        (SELECT geometry, nom, insee_arm AS code_insee FROM arrondissements_municipaux)
    ) c ON (ST_intersects(st_setsrid(st_makepoint(lon,lat),4326), geometry))
    JOIN codes_communes cc ON cc.code_insee = c.code_insee
) as p
