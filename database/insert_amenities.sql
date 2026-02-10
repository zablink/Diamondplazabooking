-- Simple SQL Script to Insert Amenities into bk_amenities
-- Run this script to add common amenities to the table
-- Checks for duplicate names AND icons before inserting

-- Insert amenities, checking for duplicate names and icons
INSERT INTO bk_amenities (amenity_name, amenity_icon, amenity_category, is_active, display_order)
SELECT * FROM (
    SELECT 'WiFi' AS amenity_name, 'fas fa-wifi' AS amenity_icon, 'tech' AS amenity_category, 1 AS is_active, 1 AS display_order
    UNION SELECT 'TV', 'fas fa-tv', 'tech', 1, 2
    UNION SELECT 'Air Conditioning', 'fas fa-snowflake', 'climate', 1, 3
    UNION SELECT 'Mini Bar', 'fas fa-glass-martini', 'food', 1, 4
    UNION SELECT 'Safe Box', 'fas fa-lock', 'security', 1, 5
    UNION SELECT 'Hair Dryer', 'fas fa-wind', 'bathroom', 1, 6
    UNION SELECT 'Bathtub', 'fas fa-bath', 'bathroom', 1, 7
    UNION SELECT 'Shower', 'fas fa-shower', 'bathroom', 1, 8
    UNION SELECT 'Coffee Maker', 'fas fa-coffee', 'food', 1, 9
    UNION SELECT 'Electric Kettle', 'fas fa-mug-hot', 'food', 1, 10
    UNION SELECT 'Work Desk', 'fas fa-desktop', 'work', 1, 11
    UNION SELECT 'Balcony', 'fas fa-home', 'room', 1, 12
    UNION SELECT 'Room', 'fas fa-bed', 'room', 1, 13
    UNION SELECT 'Bed', 'fas fa-bed', 'room', 1, 14
    UNION SELECT 'Couch', 'fas fa-couch', 'room', 1, 15
    UNION SELECT 'Chair', 'fas fa-chair', 'room', 1, 16
    UNION SELECT 'Swimming Pool', 'fas fa-swimming-pool', 'service', 1, 17
    UNION SELECT 'Spa', 'fas fa-spa', 'service', 1, 18
    UNION SELECT 'Gym', 'fas fa-dumbbell', 'service', 1, 19
    UNION SELECT 'Parking', 'fas fa-parking', 'transport', 1, 20
    UNION SELECT 'Car', 'fas fa-car', 'transport', 1, 21
    UNION SELECT 'Music', 'fas fa-music', 'entertainment', 1, 22
    UNION SELECT 'Gamepad', 'fas fa-gamepad', 'entertainment', 1, 23
    UNION SELECT 'Flat-screen TV', 'fas fa-tv', 'tech', 1, 24
    UNION SELECT 'Television', 'fas fa-tv', 'tech', 1, 25
    UNION SELECT 'Living Room', 'fas fa-couch', 'room', 1, 26
    UNION SELECT 'Private Pool', 'fas fa-swimming-pool', 'service', 1, 27
    UNION SELECT 'Kitchen', 'fas fa-utensils', 'food', 1, 28
    UNION SELECT 'Beach Access', 'fas fa-umbrella-beach', 'service', 1, 29
    UNION SELECT 'Safe', 'fas fa-lock', 'security', 1, 30
    UNION SELECT 'Internet', 'fas fa-wifi', 'tech', 1, 31
    UNION SELECT 'AC', 'fas fa-snowflake', 'climate', 1, 32
    UNION SELECT 'Bar', 'fas fa-glass-martini', 'food', 1, 33
    UNION SELECT 'Tub', 'fas fa-bath', 'bathroom', 1, 34
    UNION SELECT 'Kettle', 'fas fa-mug-hot', 'food', 1, 35
    UNION SELECT 'Work', 'fas fa-desktop', 'work', 1, 36
    UNION SELECT 'Sofa', 'fas fa-couch', 'room', 1, 37
    UNION SELECT 'Pool', 'fas fa-swimming-pool', 'service', 1, 38
    UNION SELECT 'Fitness', 'fas fa-dumbbell', 'service', 1, 39
    UNION SELECT 'ลิฟท์', 'fas fa-elevator', 'facility', 1, 40
    UNION SELECT 'Elevator', 'fas fa-elevator', 'facility', 1, 41
) AS new_amenities
WHERE NOT EXISTS (
    SELECT 1 FROM bk_amenities ba 
    WHERE ba.amenity_name = new_amenities.amenity_name
       OR ba.amenity_icon = new_amenities.amenity_icon
);

-- Check results
SELECT COUNT(*) AS total_amenities FROM bk_amenities;
SELECT * FROM bk_amenities ORDER BY amenity_name;
