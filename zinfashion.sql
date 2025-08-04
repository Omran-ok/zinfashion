-- =============================================
-- ZIN FASHION MULTILINGUAL DATABASE SCHEMA
-- Version: 2.0 (Multi-Language Support)
-- Database: zin_fashion_multilingual
-- Languages: German (Primary), English, Arabic
-- =============================================

CREATE DATABASE IF NOT EXISTS zin_fashion_multilingual
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE zin_fashion_multilingual;

-- Set proper configuration
SET NAMES utf8mb4;
SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- =============================================
-- 1. LANGUAGE CONFIGURATION
-- =============================================

-- Language settings table
CREATE TABLE language_settings (
    language_id INT PRIMARY KEY AUTO_INCREMENT,
    language_code VARCHAR(5) UNIQUE NOT NULL,
    language_name VARCHAR(50) NOT NULL,
    native_name VARCHAR(50) NOT NULL,
    direction ENUM('ltr', 'rtl') DEFAULT 'ltr',
    is_active BOOLEAN DEFAULT TRUE,
    is_default BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active_lang (is_active, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Static translations table
CREATE TABLE translations (
    translation_id INT PRIMARY KEY AUTO_INCREMENT,
    translation_key VARCHAR(255) NOT NULL,
    language_code VARCHAR(5) NOT NULL,
    translation_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_translation (translation_key, language_code),
    INDEX idx_trans_key (translation_key),
    INDEX idx_trans_lang (language_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 2. CATEGORY STRUCTURE (MULTILINGUAL)
-- =============================================

-- Main categories with translations
CREATE TABLE categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(50) NOT NULL, -- Default German name
    category_slug VARCHAR(50) UNIQUE NOT NULL,
    translations JSON, -- {"en": "Men", "ar": "رجال"}
    slug_translations JSON, -- {"en": "men", "ar": "rijal"}
    category_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category_active (is_active, category_order),
    INDEX idx_category_slug (category_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Subcategories with translations
CREATE TABLE subcategories (
    subcategory_id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    subcategory_name VARCHAR(50) NOT NULL, -- Default German name
    subcategory_slug VARCHAR(50) UNIQUE NOT NULL,
    translations JSON, -- {"en": "T-Shirts", "ar": "تي شيرت"}
    slug_translations JSON,
    subcategory_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE,
    INDEX idx_subcategory_active (is_active, subcategory_order),
    INDEX idx_subcategory_slug (subcategory_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 3. PRODUCT MANAGEMENT (MULTILINGUAL)
-- =============================================

-- Products table with translations
CREATE TABLE products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    subcategory_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL, -- Default German name
    product_slug VARCHAR(255) UNIQUE NOT NULL,
    sku VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    name_translations JSON, -- {"en": "Cotton T-Shirt", "ar": "قميص قطني"}
    description_translations JSON,
    short_description_translations JSON,
    slug_translations JSON,
    regular_price DECIMAL(10, 2) NOT NULL,
    sale_price DECIMAL(10, 2),
    material VARCHAR(100) DEFAULT 'Cotton',
    material_translations JSON, -- {"en": "Cotton", "ar": "قطن"}
    care_instructions TEXT,
    care_instructions_translations JSON,
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    meta_title VARCHAR(255),
    meta_description TEXT,
    meta_title_translations JSON,
    meta_description_translations JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(subcategory_id) ON DELETE RESTRICT,
    INDEX idx_product_active (is_active),
    INDEX idx_sku (sku),
    INDEX idx_product_slug (product_slug),
    FULLTEXT idx_product_search (product_name, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product images with alt text translations
CREATE TABLE product_images (
    image_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    image_alt VARCHAR(255),
    alt_translations JSON, -- {"en": "Blue T-Shirt Front", "ar": "قميص أزرق أمامي"}
    is_primary BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    INDEX idx_product_primary (product_id, is_primary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 4. SIZE & COLOR MANAGEMENT (MULTILINGUAL)
-- =============================================

-- Size categories with translations
CREATE TABLE size_categories (
    size_category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    translations JSON, -- {"en": "Men's T-Shirts", "ar": "قمصان رجالية"}
    subcategory_type VARCHAR(50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sizes with translations
CREATE TABLE sizes (
    size_id INT PRIMARY KEY AUTO_INCREMENT,
    size_category_id INT NOT NULL,
    size_name VARCHAR(20) NOT NULL,
    size_name_translations JSON, -- Some sizes might need translation
    size_order INT DEFAULT 0,
    measurements JSON,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (size_category_id) REFERENCES size_categories(size_category_id) ON DELETE CASCADE,
    UNIQUE KEY unique_size_category (size_category_id, size_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Colors with translations
CREATE TABLE colors (
    color_id INT PRIMARY KEY AUTO_INCREMENT,
    color_name VARCHAR(50) NOT NULL,
    translations JSON, -- {"en": "Black", "ar": "أسود"}
    color_code VARCHAR(7),
    is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 5. INVENTORY (SAME AS BEFORE)
-- =============================================

-- Product variants
CREATE TABLE product_variants (
    variant_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    color_id INT,
    size_id INT NOT NULL,
    variant_sku VARCHAR(100) UNIQUE,
    stock_quantity INT DEFAULT 0,
    low_stock_threshold INT DEFAULT 10,
    is_available BOOLEAN DEFAULT TRUE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (color_id) REFERENCES colors(color_id) ON DELETE RESTRICT,
    FOREIGN KEY (size_id) REFERENCES sizes(size_id) ON DELETE RESTRICT,
    UNIQUE KEY unique_variant (product_id, color_id, size_id),
    INDEX idx_stock_check (is_available, stock_quantity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock movements
CREATE TABLE stock_movements (
    movement_id INT PRIMARY KEY AUTO_INCREMENT,
    variant_id INT NOT NULL,
    movement_type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    reference_type VARCHAR(50),
    reference_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id) ON DELETE CASCADE,
    INDEX idx_movement_date (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 6. USER MANAGEMENT (MULTILINGUAL SUPPORT)
-- =============================================

-- Users table with language preference
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    date_of_birth DATE,
    preferred_language VARCHAR(5) DEFAULT 'de',
    is_active BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255),
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_verification (verification_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User addresses
CREATE TABLE user_addresses (
    address_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    address_type ENUM('billing', 'shipping', 'both') DEFAULT 'both',
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    street_address VARCHAR(255) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    city VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_addresses (user_id, is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password reset tokens
CREATE TABLE password_reset_tokens (
    token_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash),
    INDEX idx_token_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 7. GDPR COMPLIANCE (MULTILINGUAL)
-- =============================================

-- User consent management
CREATE TABLE user_consent (
    consent_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    consent_type ENUM('privacy_policy', 'terms_of_service', 'newsletter', 'cookies') NOT NULL,
    consent_version VARCHAR(20),
    consent_language VARCHAR(5), -- Language of consent document
    consent_given BOOLEAN DEFAULT FALSE,
    ip_address VARCHAR(45),
    consent_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    withdrawn_date TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_consent (user_id, consent_type),
    INDEX idx_consent_date (consent_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data deletion requests
CREATE TABLE data_deletion_requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    reason TEXT,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    scheduled_for TIMESTAMP NOT NULL,
    completed_at TIMESTAMP NULL,
    status ENUM('pending', 'processing', 'completed') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_deletion_status (status, scheduled_for)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data export requests
CREATE TABLE data_export_requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    download_link VARCHAR(500),
    expires_at TIMESTAMP NULL,
    status ENUM('pending', 'processing', 'completed', 'expired') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_export_status (status, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 8. SHOPPING CART (SAME AS BEFORE)
-- =============================================

CREATE TABLE cart_items (
    cart_item_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    session_id VARCHAR(255),
    variant_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id) ON DELETE CASCADE,
    INDEX idx_user_cart (user_id),
    INDEX idx_session_cart (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 9. WISHLIST (SAME AS BEFORE)
-- =============================================

CREATE TABLE wishlists (
    wishlist_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notified_on_sale BOOLEAN DEFAULT FALSE,
    notified_back_in_stock BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist_item (user_id, product_id),
    INDEX idx_wishlist_user (user_id),
    INDEX idx_wishlist_notifications (notified_on_sale, notified_back_in_stock)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 10. ORDERS (WITH LANGUAGE TRACKING)
-- =============================================

-- Orders table
CREATE TABLE orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    user_id INT,
    guest_email VARCHAR(255),
    order_language VARCHAR(5) DEFAULT 'de', -- Language used for order
    order_status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50),
    subtotal DECIMAL(10, 2) NOT NULL,
    tax_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    shipping_cost DECIMAL(10, 2) DEFAULT 0,
    total_amount DECIMAL(10, 2) NOT NULL,
    customer_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_order_number (order_number),
    INDEX idx_order_status (order_status),
    INDEX idx_user_orders (user_id),
    INDEX idx_order_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order items with stored translations
CREATE TABLE order_items (
    order_item_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL, -- Name in order language
    product_sku VARCHAR(100) NOT NULL,
    color_name VARCHAR(50), -- Color in order language
    size_name VARCHAR(20) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT,
    FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id) ON DELETE RESTRICT,
    INDEX idx_order_items (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order addresses
CREATE TABLE order_addresses (
    order_address_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    address_type ENUM('billing', 'shipping') NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    street_address VARCHAR(255) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    city VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(255),
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    INDEX idx_order_addresses (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 11. SIMPLE ADMIN (SAME AS BEFORE)
-- =============================================

-- Admin users
CREATE TABLE admin_users (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    preferred_language VARCHAR(5) DEFAULT 'de',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Critical admin activity log
CREATE TABLE admin_activity_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    action_description TEXT,
    affected_user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(admin_id) ON DELETE CASCADE,
    INDEX idx_activity_date (created_at DESC),
    INDEX idx_activity_critical (action_type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 12. REVIEWS (MULTILINGUAL)
-- =============================================

CREATE TABLE product_reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    user_id INT,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    review_language VARCHAR(5) DEFAULT 'de',
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_product_reviews (product_id, is_approved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 13. SHIPPING (MULTILINGUAL)
-- =============================================

CREATE TABLE shipping_methods (
    shipping_method_id INT PRIMARY KEY AUTO_INCREMENT,
    method_name VARCHAR(100) NOT NULL,
    translations JSON, -- {"en": "Standard Shipping", "ar": "الشحن القياسي"}
    base_cost DECIMAL(10, 2),
    free_shipping_threshold DECIMAL(10, 2),
    is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 14. NEWSLETTER (WITH LANGUAGE PREFERENCE)
-- =============================================

CREATE TABLE newsletter_subscribers (
    subscriber_id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    preferred_language VARCHAR(5) DEFAULT 'de',
    is_active BOOLEAN DEFAULT TRUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    confirmed_at TIMESTAMP NULL,
    unsubscribed_at TIMESTAMP NULL,
    confirmation_token VARCHAR(255),
    INDEX idx_active_subscribers (is_active, email),
    INDEX idx_subscriber_token (confirmation_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 15. SYSTEM SETTINGS (SAME AS BEFORE)
-- =============================================

CREATE TABLE system_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 16. ERROR LOGGING (SAME AS BEFORE)
-- =============================================

CREATE TABLE error_logs (
    error_id INT PRIMARY KEY AUTO_INCREMENT,
    error_level ENUM('warning', 'error', 'critical') NOT NULL,
    error_message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_error_date (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 17. EMAIL TEMPLATES (MULTILINGUAL)
-- =============================================

CREATE TABLE email_templates (
    template_id INT PRIMARY KEY AUTO_INCREMENT,
    template_code VARCHAR(50) NOT NULL,
    language_code VARCHAR(5) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body_html TEXT NOT NULL,
    body_text TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_template_lang (template_code, language_code),
    INDEX idx_template_active (template_code, language_code, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 18. INITIAL DATA WITH TRANSLATIONS
-- =============================================

-- Insert supported languages
INSERT INTO language_settings (language_code, language_name, native_name, direction, is_active, is_default, display_order) VALUES
('de', 'German', 'Deutsch', 'ltr', TRUE, TRUE, 1),
('en', 'English', 'English', 'ltr', TRUE, FALSE, 2),
('ar', 'Arabic', 'العربية', 'rtl', TRUE, FALSE, 3);

-- Insert categories with translations
INSERT INTO categories (category_name, category_slug, translations, slug_translations, category_order) VALUES
('Herren', 'herren', '{"en": "Men", "ar": "رجال"}', '{"en": "men", "ar": "rijal"}', 1),
('Damen', 'damen', '{"en": "Women", "ar": "نساء"}', '{"en": "women", "ar": "nisaa"}', 2),
('Kinder', 'kinder', '{"en": "Kids", "ar": "أطفال"}', '{"en": "kids", "ar": "atfal"}', 3);

-- Insert subcategories with translations
INSERT INTO subcategories (category_id, subcategory_name, subcategory_slug, translations, slug_translations, subcategory_order) VALUES
(1, 'T-Shirts', 'herren-tshirts', '{"en": "T-Shirts", "ar": "تي شيرت"}', '{"en": "men-tshirts", "ar": "tshirt-rijal"}', 1),
(1, 'Hosen', 'herren-hosen', '{"en": "Pants", "ar": "بنطلون"}', '{"en": "men-pants", "ar": "bantalon-rijal"}', 2),
(2, 'T-Shirts', 'damen-tshirts', '{"en": "T-Shirts", "ar": "تي شيرت"}', '{"en": "women-tshirts", "ar": "tshirt-nisaa"}', 1),
(3, 'T-Shirts', 'kinder-tshirts', '{"en": "T-Shirts", "ar": "تي شيرت"}', '{"en": "kids-tshirts", "ar": "tshirt-atfal"}', 1);

-- Insert size categories with translations
INSERT INTO size_categories (category_name, translations, subcategory_type) VALUES
('Herren T-Shirts', '{"en": "Men''s T-Shirts", "ar": "قمصان رجالية"}', 'herren-tshirts'),
('Herren Hosen', '{"en": "Men''s Pants", "ar": "بنطلونات رجالية"}', 'herren-hosen'),
('Damen T-Shirts', '{"en": "Women''s T-Shirts", "ar": "قمصان نسائية"}', 'damen-tshirts'),
('Kinder T-Shirts', '{"en": "Kids T-Shirts", "ar": "قمصان أطفال"}', 'kinder-tshirts');

-- Insert sizes (mostly same in all languages)
INSERT INTO sizes (size_category_id, size_name, size_order, measurements) VALUES
(1, '6XL', 1, '{"chest": "152-157", "length": "84", "sleeve": "25"}'),
(1, '7XL', 2, '{"chest": "157-162", "length": "86", "sleeve": "26"}'),
(1, '8XL', 3, '{"chest": "162-167", "length": "88", "sleeve": "27"}'),
(1, '9XL', 4, '{"chest": "167-172", "length": "90", "sleeve": "28"}');

-- Insert men's pants sizes
INSERT INTO sizes (size_category_id, size_name, size_order, measurements) VALUES
(2, 'L', 1, '{"waist": "86-91", "inseam": "81", "hip": "112-117"}'),
(2, 'XL', 2, '{"waist": "91-97", "inseam": "81", "hip": "117-122"}'),
(2, '2XL', 3, '{"waist": "97-102", "inseam": "81", "hip": "122-127"}'),
(2, '3XL', 4, '{"waist": "102-107", "inseam": "81", "hip": "127-132"}'),
(2, '4XL', 5, '{"waist": "107-112", "inseam": "81", "hip": "132-137"}'),
(2, '5XL', 6, '{"waist": "112-117", "inseam": "81", "hip": "137-142"}'),
(2, '6XL', 7, '{"waist": "117-122", "inseam": "81", "hip": "142-147"}'),
(2, '7XL', 8, '{"waist": "122-127", "inseam": "81", "hip": "147-152"}'),
(2, '8XL', 9, '{"waist": "127-132", "inseam": "81", "hip": "152-157"}'),
(2, '9XL', 10, '{"waist": "132-137", "inseam": "81", "hip": "157-162"}'),
(2, '10XL', 11, '{"waist": "137-142", "inseam": "81", "hip": "162-167"}');

-- Insert colors with translations
INSERT INTO colors (color_name, translations, color_code) VALUES
('Schwarz', '{"en": "Black", "ar": "أسود"}', '#000000'),
('Weiß', '{"en": "White", "ar": "أبيض"}', '#FFFFFF'),
('Navy', '{"en": "Navy", "ar": "كحلي"}', '#000080'),
('Grau', '{"en": "Gray", "ar": "رمادي"}', '#808080'),
('Blau', '{"en": "Blue", "ar": "أزرق"}', '#0000FF'),
('Rot', '{"en": "Red", "ar": "أحمر"}', '#FF0000'),
('Grün', '{"en": "Green", "ar": "أخضر"}', '#008000'),
('Beige', '{"en": "Beige", "ar": "بيج"}', '#F5F5DC');

-- Insert shipping methods with translations
INSERT INTO shipping_methods (method_name, translations, base_cost, free_shipping_threshold) VALUES
('Standardversand (2-3 Tage)', '{"en": "Standard Shipping (2-3 Days)", "ar": "الشحن القياسي (2-3 أيام)"}', 4.99, 50.00),
('Abholung im Geschäft', '{"en": "Store Pickup", "ar": "الاستلام من المتجر"}', 0.00, NULL),
('Lokale Lieferung (Gleicher Tag)', '{"en": "Local Delivery (Same Day)", "ar": "التوصيل المحلي (نفس اليوم)"}', 9.99, 100.00);

-- Insert system settings
INSERT INTO system_settings (setting_key, setting_value) VALUES
('site_name', 'Zin Fashion'),
('contact_email', 'info@zinfashion.de'),
('tax_rate', '19'),
('tax_included', 'true'),
('order_prefix', 'ZF-'),
('low_stock_alert', '10'),
('gdpr_deletion_days', '30'),
('currency', 'EUR'),
('store_address', 'Musterstraße 123, 12345 Musterstadt'),
('store_phone', '+49 123 456789'),
('supported_languages', 'de,en,ar'),
('default_language', 'de');

-- Insert common translations
INSERT INTO translations (translation_key, language_code, translation_value) VALUES
-- Navigation
('nav.home', 'de', 'Startseite'),
('nav.home', 'en', 'Home'),
('nav.home', 'ar', 'الصفحة الرئيسية'),
('nav.products', 'de', 'Produkte'),
('nav.products', 'en', 'Products'),
('nav.products', 'ar', 'المنتجات'),
('nav.cart', 'de', 'Warenkorb'),
('nav.cart', 'en', 'Cart'),
('nav.cart', 'ar', 'سلة التسوق'),
('nav.account', 'de', 'Mein Konto'),
('nav.account', 'en', 'My Account'),
('nav.account', 'ar', 'حسابي'),

-- Cart
('cart.empty', 'de', 'Ihr Warenkorb ist leer'),
('cart.empty', 'en', 'Your cart is empty'),
('cart.empty', 'ar', 'سلة التسوق فارغة'),
('cart.total', 'de', 'Gesamt'),
('cart.total', 'en', 'Total'),
('cart.total', 'ar', 'المجموع'),

-- Buttons
('button.add_to_cart', 'de', 'In den Warenkorb'),
('button.add_to_cart', 'en', 'Add to Cart'),
('button.add_to_cart', 'ar', 'أضف إلى السلة'),
('button.checkout', 'de', 'Zur Kasse'),
('button.checkout', 'en', 'Checkout'),
('button.checkout', 'ar', 'الدفع'),
('button.continue_shopping', 'de', 'Weiter einkaufen'),
('button.continue_shopping', 'en', 'Continue Shopping'),
('button.continue_shopping', 'ar', 'متابعة التسوق'),

-- Forms
('form.email', 'de', 'E-Mail'),
('form.email', 'en', 'Email'),
('form.email', 'ar', 'البريد الإلكتروني'),
('form.password', 'de', 'Passwort'),
('form.password', 'en', 'Password'),
('form.password', 'ar', 'كلمة المرور'),
('form.first_name', 'de', 'Vorname'),
('form.first_name', 'en', 'First Name'),
('form.first_name', 'ar', 'الاسم الأول'),
('form.last_name', 'de', 'Nachname'),
('form.last_name', 'en', 'Last Name'),
('form.last_name', 'ar', 'اسم العائلة'),

-- Messages
('message.success', 'de', 'Erfolgreich'),
('message.success', 'en', 'Success'),
('message.success', 'ar', 'نجح'),
('message.error', 'de', 'Fehler'),
('message.error', 'en', 'Error'),
('message.error', 'ar', 'خطأ'),
('message.product_added', 'de', 'Produkt wurde zum Warenkorb hinzugefügt'),
('message.product_added', 'en', 'Product added to cart'),
('message.product_added', 'ar', 'تمت إضافة المنتج إلى السلة');

-- Insert email templates for each language
INSERT INTO email_templates (template_code, language_code, subject, body_html, body_text) VALUES
-- German templates
('order_confirmation', 'de', 'Bestellbestätigung - {{order_number}}', 
'<h1>Vielen Dank für Ihre Bestellung!</h1><p>Ihre Bestellung {{order_number}} wurde bestätigt.</p>', 
'Vielen Dank für Ihre Bestellung! Ihre Bestellung {{order_number}} wurde bestätigt.'),

-- English templates
('order_confirmation', 'en', 'Order Confirmation - {{order_number}}', 
'<h1>Thank you for your order!</h1><p>Your order {{order_number}} has been confirmed.</p>', 
'Thank you for your order! Your order {{order_number}} has been confirmed.'),

-- Arabic templates
('order_confirmation', 'ar', 'تأكيد الطلب - {{order_number}}', 
'<h1>شكراً لطلبك!</h1><p>تم تأكيد طلبك {{order_number}}.</p>', 
'شكراً لطلبك! تم تأكيد طلبك {{order_number}}.');

-- Create default admin user
INSERT INTO admin_users (username, email, password_hash, first_name, last_name) VALUES
('admin', 'admin@zinfashion.de', '$2y$10$YourHashedPasswordHere', 'Admin', 'User');

-- =============================================
-- 19. VIEWS FOR MULTILINGUAL DATA
-- =============================================

-- Inventory status view
CREATE VIEW inventory_status AS
SELECT 
    p.product_id,
    p.product_name,
    p.name_translations,
    p.sku,
    pv.variant_id,
    c.color_name,
    c.translations as color_translations,
    s.size_name,
    pv.stock_quantity,
    pv.low_stock_threshold,
    CASE 
        WHEN pv.stock_quantity = 0 THEN 'out_of_stock'
        WHEN pv.stock_quantity <= pv.low_stock_threshold THEN 'low_stock'
        ELSE 'in_stock'
    END as stock_status
FROM product_variants pv
JOIN products p ON pv.product_id = p.product_id
LEFT JOIN colors c ON pv.color_id = c.color_id
JOIN sizes s ON pv.size_id = s.size_id
WHERE p.is_active = TRUE;

-- =============================================
-- 20. STORED PROCEDURES FOR MULTILINGUAL
-- =============================================

DELIMITER //

-- Get translated product name
CREATE FUNCTION get_translated_name(
    p_product_id INT,
    p_language VARCHAR(5)
) RETURNS VARCHAR(255)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE v_name VARCHAR(255);
    DECLARE v_translations JSON;
    
    SELECT product_name, name_translations 
    INTO v_name, v_translations
    FROM products 
    WHERE product_id = p_product_id;
    
    IF v_translations IS NOT NULL AND JSON_EXTRACT(v_translations, CONCAT('$.', p_language)) IS NOT NULL THEN
        RETURN JSON_UNQUOTE(JSON_EXTRACT(v_translations, CONCAT('$.', p_language)));
    ELSE
        RETURN v_name;
    END IF;
END//

-- Update stock with localized notes
CREATE PROCEDURE update_stock_after_order(IN p_order_id INT)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_variant_id INT;
    DECLARE v_quantity INT;
    DECLARE v_language VARCHAR(5);
    
    DECLARE cur CURSOR FOR 
        SELECT oi.variant_id, oi.quantity, o.order_language
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.order_id
        WHERE oi.order_id = p_order_id;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    START TRANSACTION;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO v_variant_id, v_quantity, v_language;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        UPDATE product_variants 
        SET stock_quantity = stock_quantity - v_quantity
        WHERE variant_id = v_variant_id;
        
        INSERT INTO stock_movements (variant_id, movement_type, quantity, reference_type, reference_id)
        VALUES (v_variant_id, 'out', v_quantity, 'order', p_order_id);
        
    END LOOP;
    
    CLOSE cur;
    COMMIT;
END//

-- GDPR data export with translations
CREATE PROCEDURE export_user_data(IN p_user_id INT, IN p_language VARCHAR(5))
BEGIN
    -- User info
    SELECT 'personal_data' as data_type, u.* 
    FROM users u WHERE user_id = p_user_id;
    
    -- Addresses
    SELECT 'addresses' as data_type, ua.* 
    FROM user_addresses ua WHERE user_id = p_user_id;
    
    -- Orders with translated product names
    SELECT 'orders' as data_type, o.*, oi.product_name 
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.user_id = p_user_id;
    
    -- Wishlist with translated names
    SELECT 'wishlist' as data_type, w.*, 
           get_translated_name(p.product_id, p_language) as product_name
    FROM wishlists w
    JOIN products p ON w.product_id = p.product_id
    WHERE w.user_id = p_user_id;
    
    -- Consent history
    SELECT 'consent_history' as data_type, uc.* 
    FROM user_consent uc WHERE user_id = p_user_id;
END//

DELIMITER ;

-- =============================================
-- 21. INDEXES FOR MULTILINGUAL SEARCH
-- =============================================

-- Add indexes for JSON searches (MySQL 5.7+)
ALTER TABLE products ADD INDEX idx_name_trans_de ((CAST(name_translations->>'$.de' AS CHAR(255))));
ALTER TABLE products ADD INDEX idx_name_trans_en ((CAST(name_translations->>'$.en' AS CHAR(255))));
ALTER TABLE products ADD INDEX idx_name_trans_ar ((CAST(name_translations->>'$.ar' AS CHAR(255))));

-- =============================================
-- 22. TRIGGERS FOR MULTILINGUAL
-- =============================================

DELIMITER //

-- Ensure default language values
CREATE TRIGGER ensure_translation_defaults
BEFORE INSERT ON products
FOR EACH ROW
BEGIN
    IF NEW.name_translations IS NULL THEN
        SET NEW.name_translations = JSON_OBJECT('de', NEW.product_name);
    END IF;
    IF NEW.slug_translations IS NULL THEN
        SET NEW.slug_translations = JSON_OBJECT('de', NEW.product_slug);
    END IF;
END//

DELIMITER ;

-- =============================================
-- DATABASE SETUP COMPLETE
-- =============================================

-- Summary:
-- ✅ Full multi-language support (German, English, Arabic)
-- ✅ JSON columns for efficient translation storage
-- ✅ RTL support for Arabic
-- ✅ Language preferences for users
-- ✅ Translated email templates
-- ✅ GDPR compliance maintained
-- ✅ Optimized for Hostinger Cloud Startup