CREATE DATABASE IF NOT EXISTS barberia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE barberia;

CREATE TABLE IF NOT EXISTS settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(80) NOT NULL UNIQUE,
    setting_value TEXT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'admin',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS barbers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    phone VARCHAR(40) NULL,
    bio VARCHAR(255) NULL,
    color VARCHAR(20) NOT NULL DEFAULT '#1f2937',
    work_days VARCHAR(30) NOT NULL DEFAULT '1,2,3,4,5,6',
    work_start TIME NOT NULL DEFAULT '09:00:00',
    work_end TIME NOT NULL DEFAULT '19:00:00',
    lunch_start TIME NULL,
    lunch_end TIME NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    price INT UNSIGNED NOT NULL DEFAULT 0,
    duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS barber_services (
    barber_id INT UNSIGNED NOT NULL,
    service_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (barber_id, service_id),
    CONSTRAINT fk_bs_barber FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE CASCADE,
    CONSTRAINT fk_bs_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    category VARCHAR(80) NULL,
    price INT UNSIGNED NOT NULL DEFAULT 0,
    cost INT UNSIGNED NOT NULL DEFAULT 0,
    stock INT NOT NULL DEFAULT 0,
    min_stock INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    email VARCHAR(160) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_customer_phone (phone)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS appointments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    barber_id INT UNSIGNED NOT NULL,
    service_id INT UNSIGNED NOT NULL,
    appointment_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    price_at_booking INT UNSIGNED NOT NULL DEFAULT 0,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    notes TEXT NULL,
    payment_method VARCHAR(30) NULL,
    paid TINYINT(1) NOT NULL DEFAULT 0,
    public_token VARCHAR(80) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_appointment_customer FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT fk_appointment_barber FOREIGN KEY (barber_id) REFERENCES barbers(id),
    CONSTRAINT fk_appointment_service FOREIGN KEY (service_id) REFERENCES services(id),
    INDEX idx_appointment_date (appointment_date),
    INDEX idx_barber_date (barber_id, appointment_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS appointment_products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    unit_price INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_ap_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    CONSTRAINT fk_ap_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('business_name', 'fioreee_barber'),
('business_subtitle', 'ReservÃ¡ tu corte con Fiorella'),
('business_phone', ''),
('business_address', 'CÃ³rdoba, Argentina'),
('booking_notice', 'LlegÃ¡ 5 minutos antes de tu turno.');

INSERT IGNORE INTO users (id, name, email, password, role, active) VALUES
(1, 'Administrador', 'fiorebaber', '$2y$12$n.18hV.8xA04YWMZU8ndaOv8D/PHHYfNpKGgnnAV2hI2SUQEZo5Cy', 'admin', 1);

INSERT IGNORE INTO services (id, name, description, price, duration_minutes, active) VALUES
(1, 'Corte de pelo', 'Corte de pelo con terminaciÃ³n y detalles.', 12000, 30, 1);

INSERT IGNORE INTO barbers (id, name, phone, bio, color, work_days, work_start, work_end, lunch_start, lunch_end, active) VALUES
(1, 'Fiorella', NULL, 'Peluquera de fioreee_barber.', '#111827', '1,2,3,4,5,6', '09:00:00', '19:00:00', '13:00:00', '14:00:00', 1);

INSERT IGNORE INTO barber_services (barber_id, service_id) VALUES
(1, 1);

-- Los productos se cargan desde el panel cuando sean necesarios.

