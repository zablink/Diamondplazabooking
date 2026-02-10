-- Simple SQL Script to Migrate Amenities (No collation issues)
-- This version avoids collation problems by using simple string operations

-- Step 1: Insert common amenities with icons (you can customize this list)
-- This will insert only if they don't already exist
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
('Room', 'fas fa-bed', 'room', 1, 15),
('Bed', 'fas fa-bed', 'room', 1, 16),
('Couch', 'fas fa-couch', 'room', 1, 17),
('Chair', 'fas fa-chair', 'room', 1, 18),
('Swimming Pool', 'fas fa-swimming-pool', 'service', 1, 19),
('Spa', 'fas fa-spa', 'service', 1, 20),
('Gym', 'fas fa-dumbbell', 'service', 1, 21),
('Parking', 'fas fa-parking', 'transport', 1, 22),
('Car', 'fas fa-car', 'transport', 1, 23),
('Music', 'fas fa-music', 'entertainment', 1, 24),
('Gamepad', 'fas fa-gamepad', 'entertainment', 1, 25),
('Flat-screen TV', 'fas fa-tv', 'tech', 1, 26),
('Television', 'fas fa-tv', 'tech', 1, 27),
('Living Room', 'fas fa-couch', 'room', 1, 28),
('Private Pool', 'fas fa-swimming-pool', 'service', 1, 29),
('Kitchen', 'fas fa-utensils', 'food', 1, 30),
('Beach Access', 'fas fa-umbrella-beach', 'service', 1, 31);

-- Check results
SELECT * FROM bk_amenities ORDER BY amenity_name;
