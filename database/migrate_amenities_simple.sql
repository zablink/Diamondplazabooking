-- Simple SQL Script to Migrate Amenities (Alternative method)
-- This script uses a simpler approach if JSON functions are not available

-- Method 1: If amenities are stored as JSON array like ["WiFi", "TV", "Coffee"]
-- This requires MySQL 5.7+ with JSON support

-- Step 1: Insert common amenities with icons (you can customize this list)
INSERT IGNORE INTO bk_amenities (amenity_name, amenity_icon, amenity_category, is_active, display_order) VALUES
('WiFi', 'fas fa-wifi', 'tech', 1, 1),
('TV', 'fas fa-tv', 'tech', 1, 2),
('Air Conditioning', 'fas fa-snowflake', 'climate', 1, 3),
('Mini Bar', 'fas fa-glass-martini', 'food', 1, 4),
('Safe Box', 'fas fa-lock', 'security', 1, 5),
('Hair Dryer', 'fas fa-wind', 'bathroom', 1, 6),
('Bathtub', 'fas fa-bath', 'bathroom', 1, 7),
('Shower', 'fas fa-shower', 'bathroom', 1, 8),
('Coffee Maker', 'fas fa-coffee', 'food', 1, 9),
('Coffee', 'fas fa-coffee', 'food', 1, 10),
('Electric Kettle', 'fas fa-mug-hot', 'food', 1, 11),
('Work Desk', 'fas fa-desktop', 'work', 1, 12),
('Desk', 'fas fa-desktop', 'work', 1, 13),
('Balcony', 'fas fa-home', 'room', 1, 14),
('Room', 'fas fa-bed', 'room', 1, 15);

-- Method 2: Extract from JSON (if your MySQL version supports JSON functions)
-- Uncomment and use this if you have MySQL 5.7+ and amenities are stored as JSON

/*
-- Create temporary procedure to extract amenities
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS migrate_amenities_from_rooms()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE room_amenities TEXT;
    DECLARE amenity_name VARCHAR(255);
    DECLARE i INT DEFAULT 0;
    DECLARE amenity_count INT;
    
    DECLARE cur CURSOR FOR 
        SELECT amenities FROM bk_room_types WHERE amenities IS NOT NULL AND amenities != '';
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO room_amenities;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Get count of amenities in JSON array
        SET amenity_count = JSON_LENGTH(room_amenities);
        SET i = 0;
        
        -- Extract each amenity
        WHILE i < amenity_count DO
            SET amenity_name = TRIM(JSON_UNQUOTE(JSON_EXTRACT(room_amenities, CONCAT('$[', i, ']'))));
            
            IF amenity_name IS NOT NULL AND amenity_name != '' THEN
                -- Insert with appropriate icon
                INSERT IGNORE INTO bk_amenities (amenity_name, amenity_icon, amenity_category, is_active, display_order)
                VALUES (
                    amenity_name,
                    CASE 
                        WHEN amenity_name LIKE '%WiFi%' OR amenity_name LIKE '%wifi%' THEN 'fas fa-wifi'
                        WHEN amenity_name LIKE '%TV%' THEN 'fas fa-tv'
                        WHEN amenity_name LIKE '%Air%' OR amenity_name LIKE '%AC%' THEN 'fas fa-snowflake'
                        WHEN amenity_name LIKE '%Coffee%' THEN 'fas fa-coffee'
                        WHEN amenity_name LIKE '%Desk%' THEN 'fas fa-desktop'
                        WHEN amenity_name LIKE '%Shower%' THEN 'fas fa-shower'
                        WHEN amenity_name LIKE '%Bath%' THEN 'fas fa-bath'
                        WHEN amenity_name LIKE '%Safe%' THEN 'fas fa-lock'
                        WHEN amenity_name LIKE '%Hair%' THEN 'fas fa-wind'
                        WHEN amenity_name LIKE '%Bar%' THEN 'fas fa-glass-martini'
                        WHEN amenity_name LIKE '%Balcony%' THEN 'fas fa-home'
                        WHEN amenity_name LIKE '%Room%' OR amenity_name = 'Room' THEN 'fas fa-bed'
                        ELSE 'fas fa-star'
                    END,
                    'general',
                    1,
                    0
                );
            END IF;
            
            SET i = i + 1;
        END WHILE;
    END LOOP;
    
    CLOSE cur;
END$$

DELIMITER ;

-- Run the procedure
CALL migrate_amenities_from_rooms();

-- Drop the procedure
DROP PROCEDURE IF EXISTS migrate_amenities_from_rooms;
*/

-- Check results
SELECT * FROM bk_amenities ORDER BY amenity_name;
