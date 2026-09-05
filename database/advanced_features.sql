-- ============================================
-- FYP ADVANCED FEATURES - DATABASE TABLES
-- ============================================

-- User Feedback (thumbs up/down + admin approval flow)
CREATE TABLE IF NOT EXISTS chatbot_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    user_id INT NULL,
    message_sent TEXT NOT NULL,
    response_given TEXT NOT NULL,
    feedback TINYINT NOT NULL COMMENT '1=helpful, 0=not helpful',
    admin_reviewed TINYINT DEFAULT 0,
    admin_action ENUM('pending','approved','rejected','added_to_kb') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (session_id),
    INDEX (admin_action)
) ENGINE=InnoDB;

-- Vehicle Service History
CREATE TABLE IF NOT EXISTS vehicle_service_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    session_id VARCHAR(64) NOT NULL,
    vehicle_brand VARCHAR(50),
    vehicle_model VARCHAR(50),
    vehicle_year VARCHAR(4),
    engine_size VARCHAR(20),
    fuel_type VARCHAR(20),
    mileage VARCHAR(30),
    service_type VARCHAR(100) NOT NULL,
    problem_description TEXT,
    diagnosis TEXT,
    parts_used TEXT,
    cost_pkr INT DEFAULT 0,
    workshop_name VARCHAR(100),
    service_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id),
    INDEX (session_id)
) ENGINE=InnoDB;

-- Intent Detection Log
CREATE TABLE IF NOT EXISTS chatbot_intents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    message TEXT NOT NULL,
    detected_intent VARCHAR(50) NOT NULL,
    confidence FLOAT DEFAULT 0,
    sub_intent VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (detected_intent)
) ENGINE=InnoDB;

-- Emergency Cases (tracked separately)
CREATE TABLE IF NOT EXISTS chatbot_emergency (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    user_id INT NULL,
    message TEXT NOT NULL,
    emergency_type VARCHAR(50),
    location TEXT,
    contact VARCHAR(30),
    status ENUM('active','resolved','escalated') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (status)
) ENGINE=InnoDB;

-- Knowledge Expansion (approved user feedback → new KB entries)
CREATE TABLE IF NOT EXISTS kb_pending_review (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_type ENUM('user_feedback','admin_entry','auto_extract') NOT NULL,
    source_id INT,
    `system` VARCHAR(60),
    problem VARCHAR(255),
    symptoms TEXT,
    causes TEXT,
    solution TEXT,
    reviewer_id INT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (status)
) ENGINE=InnoDB;

-- Cost Estimates Log
CREATE TABLE IF NOT EXISTS chatbot_cost_estimates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    problem TEXT,
    parts_cost INT DEFAULT 0,
    labor_cost INT DEFAULT 0,
    total_cost INT DEFAULT 0,
    confidence FLOAT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
