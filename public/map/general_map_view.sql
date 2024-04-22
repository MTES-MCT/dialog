CREATE OR REPLACE VIEW general_map_view AS
SELECT location.road_name AS road_name, location.road_number AS road_number, measure.type AS "type",
       regulation_order.category AS category, regulation_order.description AS description, regulation_order.identifier AS identifier,
       (regulation_order.end_date IS NULL) AS is_permanent, (regulation_order_record.status = 'draft') AS is_draft,
       organization.name AS organization_name, location.geometry AS geometry
FROM location
JOIN measure ON measure.uuid = location.measure_uuid
JOIN regulation_order ON regulation_order.uuid = measure.regulation_order_uuid
JOIN regulation_order_record ON regulation_order_record.regulation_order_uuid = regulation_order.uuid
JOIN organization ON organization.uuid = regulation_order_record.organization_uuid;
