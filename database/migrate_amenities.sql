-- SQL Script to Migrate Amenities from bk_room_types to bk_amenities
-- Run this script to populate bk_amenities table with existing amenities from room types
-- Fixed collation issue: Uses CAST to convert to consistent character set

-- Step 1: Create temporary table to store unique amenities
DROP TEMPORARY TABLE IF EXISTS temp_amenities;
CREATE TEMPORARY TABLE temp_amenities (
    amenity_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci PRIMARY KEY
);

-- Step 2: Extract amenities from bk_room_types
-- This handles JSON array format: ["WiFi", "TV", "Coffee"]
-- Using CAST to convert to utf8mb4 to avoid collation mismatch
INSERT INTO temp_amenities (amenity_name)
SELECT DISTINCT CAST(TRIM(JSON_UNQUOTE(JSON_EXTRACT(amenities, CONCAT('$[', idx, ']')))) AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS amenity_name
FROM bk_room_types,
     (SELECT 0 AS idx UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
      UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
      UNION SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14
      UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19) AS indices
WHERE amenities IS NOT NULL 
  AND amenities != ''
  AND JSON_EXTRACT(amenities, CONCAT('$[', idx, ']')) IS NOT NULL
  AND TRIM(JSON_UNQUOTE(JSON_EXTRACT(amenities, CONCAT('$[', idx, ']')))) != '';

-- Step 3: Insert amenities into bk_amenities with appropriate icons
-- Using CAST to ensure consistent character set for comparisons
INSERT INTO bk_amenities (amenity_name, amenity_icon, amenity_category, is_active, display_order)
SELECT 
    ta.amenity_name,
    CASE 
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%WiFi%' 
             OR CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%wifi%' 
             OR CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Internet%' THEN 'fas fa-wifi'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%TV%' 
             OR CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Television%' THEN 'fas fa-tv'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Air%' 
             OR CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%AC%' 
             OR CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Conditioning%' THEN 'fas fa-snowflake'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Mini Bar%' 
             OR CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Bar%' THEN 'fas fa-glass-martini'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Safe%' 
             OR CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Lock%' THEN 'fas fa-lock'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Hair%' 
             OR CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Dryer%' THEN 'fas fa-wind'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Bath%' 
             OR CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Tub%' THEN 'fas fa-bath'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Shower%' THEN 'fas fa-shower'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Coffee%' THEN 'fas fa-coffee'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Kettle%' 
             OR CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Hot%' THEN 'fas fa-mug-hot'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Desk%' 
             OR CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Work%' THEN 'fas fa-desktop'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Balcony%' THEN 'fas fa-home'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Room%' 
             OR CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) = 'Room' THEN 'fas fa-bed'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Bed%' THEN 'fas fa-bed'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Couch%' 
             OR CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Sofa%' THEN 'fas fa-couch'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Chair%' THEN 'fas fa-chair'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Swimming%' 
             OR CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Pool%' THEN 'fas fa-swimming-pool'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Spa%' THEN 'fas fa-spa'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Gym%' 
             OR CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Fitness%' THEN 'fas fa-dumbbell'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Parking%' THEN 'fas fa-parking'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Car%' THEN 'fas fa-car'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Music%' THEN 'fas fa-music'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Game%' THEN 'fas fa-gamepad'
        ELSE 'fas fa-star'
    END AS amenity_icon,
    CASE 
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Bath%' 
             OR CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Shower%' 
             OR CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Hair%' THEN 'bathroom'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%TV%' 
             OR CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%WiFi%' 
             OR CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Internet%' THEN 'tech'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Coffee%' 
             OR CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Kettle%' 
             OR CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Bar%' THEN 'food'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Desk%' 
             OR CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Work%' THEN 'work'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Swimming%' 
             OR CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Spa%' 
             OR CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Gym%' THEN 'service'
        WHEN CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Bed%' 
             OR CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Room%' 
             OR CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4) LIKE '%Couch%' THEN 'room'
        ELSE 'general'
    END AS amenity_category,
    1 AS is_active,
    0 AS display_order
FROM temp_amenities ta
WHERE NOT EXISTS (
    SELECT 1 FROM bk_amenities ba 
    WHERE CAST(ba.amenity_name AS CHAR CHARACTER SET utf8mb4) = CAST(ta.amenity_name AS CHAR CHARACTER SET utf8mb4)
);

-- Step 4: Clean up temporary table
DROP TEMPORARY TABLE IF EXISTS temp_amenities;

-- Step 5: Show summary
SELECT 
    COUNT(*) AS total_amenities_migrated,
    GROUP_CONCAT(amenity_name ORDER BY amenity_name SEPARATOR ', ') AS migrated_amenities
FROM bk_amenities;
