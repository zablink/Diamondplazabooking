-- Database Schema for Hotel Booking System
-- Similar to Agoda

CREATE DATABASE IF NOT EXISTS hotel_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotel_booking;

-- Users Table
CREATE TABLE bk_users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255), -- NULL allowed for social login
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('customer', 'admin', 'hotel_owner') DEFAULT 'customer',
    -- Social Login Fields
    auth_provider ENUM('local', 'google', 'facebook', 'apple') DEFAULT 'local',
    social_id VARCHAR(255), -- ID from social provider
    profile_picture VARCHAR(500), -- URL to profile picture
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_social_id (social_id),
    UNIQUE KEY unique_social (auth_provider, social_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hotels Table
CREATE TABLE bk_hotels (
    hotel_id INT PRIMARY KEY AUTO_INCREMENT,
    hotel_name VARCHAR(255) NOT NULL,
    description TEXT,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    country VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    star_rating DECIMAL(2,1) DEFAULT 0,
    phone VARCHAR(20),
    email VARCHAR(255),
    amenities TEXT, -- JSON format
    images TEXT, -- JSON array of image URLs
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_city (city),
    INDEX idx_star_rating (star_rating),
    FULLTEXT idx_search (hotel_name, description, address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Room Types Table
CREATE TABLE bk_room_types (
    room_type_id INT PRIMARY KEY AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    room_type_name VARCHAR(255) NOT NULL,
    description TEXT,
    size_sqm INT,
    max_occupancy INT NOT NULL,
    bed_type VARCHAR(100),
    amenities TEXT, -- JSON format
    images TEXT, -- JSON array
    base_price DECIMAL(10,2) NOT NULL,
    total_rooms INT NOT NULL,
    status ENUM('available', 'unavailable') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES bk_hotels(hotel_id) ON DELETE CASCADE,
    INDEX idx_hotel (hotel_id),
    INDEX idx_price (base_price)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Room Inventory (daily availability)
CREATE TABLE bk_room_inventory (
    inventory_id INT PRIMARY KEY AUTO_INCREMENT,
    room_type_id INT NOT NULL,
    date DATE NOT NULL,
    available_rooms INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (room_type_id) REFERENCES bk_room_types(room_type_id) ON DELETE CASCADE,
    UNIQUE KEY unique_room_date (room_type_id, date),
    INDEX idx_date (date),
    INDEX idx_room_type (room_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bookings Table
CREATE TABLE bk_bookings (
    booking_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    hotel_id INT NOT NULL,
    room_type_id INT NOT NULL,
    booking_reference VARCHAR(50) UNIQUE NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    num_rooms INT NOT NULL DEFAULT 1,
    num_adults INT NOT NULL,
    num_children INT DEFAULT 0,
    total_price DECIMAL(10,2) NOT NULL,
    special_requests TEXT,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES bk_users(user_id),
    FOREIGN KEY (hotel_id) REFERENCES bk_hotels(hotel_id),
    FOREIGN KEY (room_type_id) REFERENCES bk_room_types(room_type_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_dates (check_in, check_out),
    INDEX idx_reference (booking_reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reviews Table
CREATE TABLE bk_reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    hotel_id INT NOT NULL,
    rating DECIMAL(2,1) NOT NULL CHECK (rating >= 0 AND rating <= 5),
    title VARCHAR(255),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bk_bookings(booking_id),
    FOREIGN KEY (user_id) REFERENCES bk_users(user_id),
    FOREIGN KEY (hotel_id) REFERENCES bk_hotels(hotel_id),
    INDEX idx_hotel (hotel_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Wishlist Table
CREATE TABLE bk_wishlist (
    wishlist_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    hotel_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES bk_users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (hotel_id) REFERENCES bk_hotels(hotel_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_hotel (user_id, hotel_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Sample Admin User (password: admin123)
INSERT INTO bk_users (email, password, first_name, last_name, role) 
VALUES ('admin@hotel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin');

-- Insert Sample Hotels
INSERT INTO bk_hotels (hotel_name, description, address, city, country, star_rating, phone, amenities, images) VALUES
('Diamond Plaza Surat', 'Experience luxury in the heart with stunning city views, world-class dining, and exceptional service.', 
 '123 test test test', 'Surat Thani', 'Thailand', 5.0, '+66-2-123-4567',
 '["Free WiFi", "Swimming Pool", "Fitness Center", "Spa", "Restaurant", "Bar", "Room Service", "Parking", "Airport Shuttle"]',
 '["hotel1_1.jpg", "hotel1_2.jpg", "hotel1_3.jpg"]'),

('Phuket Beach Resort', 'Beachfront paradise with direct access to pristine beaches, water sports, and tropical gardens.',
 '456 Beach Road, Patong', 'Phuket', 'Thailand', 4.5, '+66-76-345-678',
 '["Free WiFi", "Beach Access", "Swimming Pool", "Restaurant", "Water Sports", "Spa", "Kids Club"]',
 '["hotel2_1.jpg", "hotel2_2.jpg", "hotel2_3.jpg"]'),

('Chiang Mai Boutique Hotel', 'Charming boutique hotel nestled in the cultural heart of Northern Thailand.',
 '789 Old City Road, Mueang', 'Chiang Mai', 'Thailand', 4.0, '+66-53-456-789',
 '["Free WiFi", "Restaurant", "Bicycle Rental", "Garden", "Terrace", "Cultural Tours"]',
 '["hotel3_1.jpg", "hotel3_2.jpg", "hotel3_3.jpg"]');

-- Insert Sample Room Types
INSERT INTO bk_room_types (hotel_id, room_type_name, description, size_sqm, max_occupancy, bed_type, amenities, images, base_price, total_rooms) VALUES
(1, 'Deluxe King Room', 'Spacious room with king bed and city view', 35, 2, 'King Bed', 
 '["Air Conditioning", "Flat-screen TV", "Mini Bar", "Safe", "Coffee Maker"]',
 '["room1_1.jpg", "room1_2.jpg"]', 3500.00, 20),
(1, 'Executive Suite', 'Luxurious suite with separate living area', 65, 3, 'King Bed + Sofa Bed',
 '["Air Conditioning", "Flat-screen TV", "Mini Bar", "Safe", "Coffee Maker", "Living Room", "Bathtub"]',
 '["room2_1.jpg", "room2_2.jpg"]', 7500.00, 10),

(2, 'Ocean View Room', 'Room with stunning ocean views', 30, 2, 'Queen Bed',
 '["Air Conditioning", "Balcony", "Mini Bar", "TV", "Safe"]',
 '["room3_1.jpg", "room3_2.jpg"]', 2800.00, 30),
(2, 'Beach Villa', 'Private villa with direct beach access', 80, 4, '2 Queen Beds',
 '["Air Conditioning", "Private Pool", "Kitchen", "Living Room", "Beach Access"]',
 '["room4_1.jpg", "room4_2.jpg"]', 9500.00, 5),

(3, 'Superior Room', 'Comfortable room with traditional Thai decor', 28, 2, 'Double Bed',
 '["Air Conditioning", "TV", "WiFi", "Tea/Coffee Maker"]',
 '["room5_1.jpg", "room5_2.jpg"]', 1800.00, 15),
(3, 'Deluxe Double Room', 'Spacious room with garden view', 32, 2, 'King Bed',
 '["Air Conditioning", "Balcony", "TV", "WiFi", "Mini Bar"]',
 '["room6_1.jpg", "room6_2.jpg"]', 2500.00, 12);
