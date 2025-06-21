-- Base de datos para sistema de citas veterinaria

-- Tabla de usuarios
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de citas
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pet_name VARCHAR(100) NOT NULL,
    service VARCHAR(100) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabla de horarios disponibles
CREATE TABLE available_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    time TIME NOT NULL,
    is_available BOOLEAN DEFAULT TRUE
);

-- Insertar algunos horarios de ejemplo
INSERT INTO available_slots (date, time) VALUES
('2025-06-23', '09:00:00'),
('2025-06-23', '10:00:00'),
('2025-06-23', '11:00:00'),
('2025-06-23', '14:00:00'),
('2025-06-23', '15:00:00'),
('2025-06-24', '09:00:00'),
('2025-06-24', '10:00:00'),
('2025-06-24', '11:00:00'),
('2025-06-24', '14:00:00'),
('2025-06-24', '15:00:00'),
('2025-06-25', '09:00:00'),
('2025-06-25', '10:00:00'),
('2025-06-25', '11:00:00'),
('2025-06-25', '14:00:00'),
('2025-06-25', '15:00:00');