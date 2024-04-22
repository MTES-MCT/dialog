CREATE OR REPLACE VIEW general_map_view AS
SELECT location.road_name AS road_name, location.road_number AS road_number, measure.type AS "type",
       regulation_order.category AS category, regulation_order.description AS description, regulation_order.identifier AS identifier,
       (regulation_order.end_date IS NULL) AS is_permanent, (regulation_order_record.status = 'draft') AS is_draft,
       organization.name AS organization_name, location.geometry AS geometry,
       regulation_order.uuid AS regulation_order_id
FROM location
JOIN measure ON measure.uuid = location.measure_uuid
JOIN regulation_order ON regulation_order.uuid = measure.regulation_order_uuid
JOIN regulation_order_record ON regulation_order_record.regulation_order_uuid = regulation_order.uuid
JOIN organization ON organization.uuid = regulation_order_record.organization_uuid;


-- location.geometry may be LineString or MultiLineString -> before applying ST_Collect, we use ST_Dump to get on LineString per row, otherwise we may get an undisplayable GeometryCollection (ST_Collect of several MultiLineString returns a GeometryCollection)
CREATE OR REPLACE VIEW general_map_view_aggregated AS
WITH one_line_per_row AS (
SELECT location.road_name, location.road_number, 
       (ST_Dump(location.geometry)).geom AS geometry_as_line, 
       regulation_order.uuid AS regulation_order_id
FROM location
JOIN measure ON measure.uuid = location.measure_uuid
JOIN regulation_order ON regulation_order.uuid = measure.regulation_order_uuid
JOIN regulation_order_record ON regulation_order_record.regulation_order_uuid = regulation_order.uuid
JOIN organization ON organization.uuid = regulation_order_record.organization_uuid
)
SELECT regulation_order.uuid AS regulation_order_id,
       array_agg(concat(E'\'', one_line_per_row.road_name, E'\'', '[', one_line_per_row.road_number, ']')) AS named_locations, 
       ST_Multi(ST_Collect(one_line_per_row.geometry_as_line))::Geometry(MultiLineString,4326) AS geometry
FROM one_line_per_row
JOIN regulation_order ON regulation_order.uuid = one_line_per_row.regulation_order_id
GROUP BY regulation_order.uuid;
