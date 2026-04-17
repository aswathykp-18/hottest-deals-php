-- WhatsApp Marketing Platform Database Schema
-- Compatible with MySQL/MariaDB on XAMPP

CREATE DATABASE IF NOT EXISTS whatsapp_platform;
USE whatsapp_platform;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin','agent','manager') DEFAULT 'admin',
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Contacts
CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    tags VARCHAR(500),
    notes TEXT,
    status ENUM('active','blocked','unsubscribed') DEFAULT 'active',
    last_message_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_phone (phone)
) ENGINE=InnoDB;

-- Contact Groups
CREATE TABLE IF NOT EXISTS contact_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    color VARCHAR(7) DEFAULT '#3b82f6',
    contact_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Contact-Group Mapping
CREATE TABLE IF NOT EXISTS contact_group_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact_id INT NOT NULL,
    group_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES contact_groups(id) ON DELETE CASCADE,
    UNIQUE KEY unique_member (contact_id, group_id)
) ENGINE=InnoDB;

-- Message Templates
CREATE TABLE IF NOT EXISTS message_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category ENUM('marketing','utility','authentication','service') DEFAULT 'marketing',
    language VARCHAR(10) DEFAULT 'en',
    header_type ENUM('none','text','image','video','document') DEFAULT 'none',
    header_content TEXT,
    body_text TEXT NOT NULL,
    footer_text VARCHAR(255),
    buttons JSON,
    variables JSON,
    status ENUM('draft','pending','approved','rejected') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Broadcast Campaigns
CREATE TABLE IF NOT EXISTS broadcast_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    template_id INT,
    target_type ENUM('all','group','segment') DEFAULT 'all',
    target_group_id INT NULL,
    scheduled_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    status ENUM('draft','scheduled','sending','completed','failed') DEFAULT 'draft',
    total_recipients INT DEFAULT 0,
    sent_count INT DEFAULT 0,
    delivered_count INT DEFAULT 0,
    read_count INT DEFAULT 0,
    failed_count INT DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES message_templates(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Broadcast Recipients
CREATE TABLE IF NOT EXISTS broadcast_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    contact_id INT NOT NULL,
    status ENUM('pending','sent','delivered','read','failed') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    error_message VARCHAR(255),
    FOREIGN KEY (campaign_id) REFERENCES broadcast_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Conversations
CREATE TABLE IF NOT EXISTS conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact_id INT NOT NULL,
    status ENUM('open','closed','pending') DEFAULT 'open',
    assigned_to INT NULL,
    last_message TEXT,
    last_message_at TIMESTAMP NULL,
    unread_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Messages
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    contact_id INT NOT NULL,
    direction ENUM('inbound','outbound') NOT NULL,
    message_type ENUM('text','image','video','document','template','interactive') DEFAULT 'text',
    content TEXT,
    media_url VARCHAR(500),
    template_id INT NULL,
    status ENUM('pending','sent','delivered','read','failed') DEFAULT 'pending',
    wa_message_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Chatbot Flows
CREATE TABLE IF NOT EXISTS chatbot_flows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    trigger_keyword VARCHAR(100),
    is_active TINYINT(1) DEFAULT 0,
    flow_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Analytics Events
CREATE TABLE IF NOT EXISTS analytics_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    contact_id INT NULL,
    campaign_id INT NULL,
    data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Webhook Logs
CREATE TABLE IF NOT EXISTS webhook_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    payload TEXT,
    response TEXT,
    status ENUM('success','failed') DEFAULT 'success',
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_wh_event_type (event_type),
    INDEX idx_wh_status (status),
    INDEX idx_wh_created_at (created_at)
) ENGINE=InnoDB;

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, full_name, email, role) VALUES
('admin', '$2y$10$lLRUgG5UMQFBB0uP4HqA0e7lCubnRiYJG1urwjPoPOp.D/oMc1pU6', 'Administrator', 'admin@example.com', 'admin');

-- Insert sample contact groups
INSERT INTO contact_groups (name, description, color) VALUES
('VIP Clients', 'High-value property buyers', '#f59e0b'),
('New Leads', 'Recently added leads', '#10b981'),
('Dubai Hills Interested', 'Interested in Dubai Hills properties', '#3b82f6'),
('Investors', 'Property investors', '#8b5cf6');

-- Insert sample contacts
INSERT INTO contacts (phone, name, email, tags, status) VALUES
('+971501234567', 'Ahmed Hassan', 'ahmed@example.com', 'buyer,vip', 'active'),
('+971502345678', 'Sarah Khan', 'sarah@example.com', 'investor,dubai-hills', 'active'),
('+971503456789', 'Mohammed Ali', 'mohammed@example.com', 'new-lead', 'active'),
('+971504567890', 'Fatima Omar', 'fatima@example.com', 'buyer,emaar', 'active'),
('+971505678901', 'John Smith', 'john@example.com', 'investor,vip', 'active'),
('+971506789012', 'Aisha Rahman', 'aisha@example.com', 'new-lead,townhouse', 'active'),
('+971507890123', 'David Chen', 'david@example.com', 'buyer,villa', 'active'),
('+971508901234', 'Mariam Al-Sayed', 'mariam@example.com', 'investor', 'active'),
('+971509012345', 'Robert Wilson', 'robert@example.com', 'buyer,apartment', 'active'),
('+971500123456', 'Layla Ibrahim', 'layla@example.com', 'vip,villa', 'active');

-- Add contacts to groups
INSERT INTO contact_group_members (contact_id, group_id) VALUES
(1, 1), (5, 1), (10, 1),
(3, 2), (6, 2),
(2, 3), (4, 3),
(2, 4), (5, 4), (8, 4);

-- Update group counts
UPDATE contact_groups SET contact_count = (SELECT COUNT(*) FROM contact_group_members WHERE group_id = contact_groups.id);

-- Insert sample message templates
INSERT INTO message_templates (name, category, body_text, footer_text, status) VALUES
('Welcome Message', 'utility', 'Hello {{1}}! Welcome to Billionaire Homes Dubai. We are excited to help you find your dream property. How can we assist you today?', 'Billionaire Homes Dubai', 'approved'),
('New Property Alert', 'marketing', 'Hi {{1}}, we have a new exclusive property listing:\n\nProject: {{2}}\nArea: {{3}}\nPrice: {{4}}\n\nInterested? Reply YES to know more!', 'Reply STOP to unsubscribe', 'approved'),
('Viewing Reminder', 'utility', 'Reminder: Hi {{1}}, your property viewing is scheduled for {{2}} at {{3}}. Please confirm by replying YES.', 'Billionaire Homes Dubai', 'approved'),
('Follow Up', 'marketing', 'Hi {{1}}, just following up on the {{2}} property we discussed. Are you still interested? We have some exciting updates to share!', 'Reply STOP to unsubscribe', 'approved'),
('Payment Reminder', 'utility', 'Dear {{1}}, this is a friendly reminder about your upcoming payment of {{2}} due on {{3}}. Please ensure timely payment.', 'Billionaire Homes Dubai', 'approved');

-- Insert sample broadcast campaign
INSERT INTO broadcast_campaigns (name, template_id, target_type, status, total_recipients, sent_count, delivered_count, read_count, created_by, sent_at) VALUES
('March Property Launch', 2, 'all', 'completed', 10, 10, 8, 5, 1, '2026-03-15 10:00:00'),
('VIP Exclusive Offers', 4, 'group', 'completed', 3, 3, 3, 2, 1, '2026-04-01 14:00:00');

-- Insert sample conversations and messages
INSERT INTO conversations (contact_id, status, last_message, last_message_at, unread_count) VALUES
(1, 'open', 'Yes, I am interested in the Dubai Hills property', NOW() - INTERVAL 2 HOUR, 1),
(2, 'open', 'Can you send me more details about the villa?', NOW() - INTERVAL 30 MINUTE, 1),
(3, 'pending', 'Thank you for the information', NOW() - INTERVAL 1 DAY, 0),
(5, 'open', 'What is the payment plan for Greenway 2?', NOW() - INTERVAL 15 MINUTE, 1);

INSERT INTO messages (conversation_id, contact_id, direction, message_type, content, status, created_at) VALUES
(1, 1, 'outbound', 'template', 'Hi Ahmed, we have a new exclusive property listing in Dubai Hills!', 'delivered', NOW() - INTERVAL 3 HOUR),
(1, 1, 'inbound', 'text', 'Yes, I am interested in the Dubai Hills property', 'read', NOW() - INTERVAL 2 HOUR),
(2, 2, 'outbound', 'text', 'Hello Sarah! We have some amazing villa options for you.', 'delivered', NOW() - INTERVAL 1 HOUR),
(2, 2, 'inbound', 'text', 'Can you send me more details about the villa?', 'read', NOW() - INTERVAL 30 MINUTE),
(3, 3, 'outbound', 'template', 'Welcome to Billionaire Homes Dubai!', 'delivered', NOW() - INTERVAL 2 DAY),
(3, 3, 'inbound', 'text', 'Thank you for the information', 'read', NOW() - INTERVAL 1 DAY),
(4, 5, 'inbound', 'text', 'What is the payment plan for Greenway 2?', 'read', NOW() - INTERVAL 15 MINUTE);

-- Insert sample chatbot flow
INSERT INTO chatbot_flows (name, description, trigger_keyword, is_active, flow_data) VALUES
('Welcome Flow', 'Greets new contacts and asks about their interest', 'hello', 1, '{"nodes":[{"id":"node_1","type":"send_message","data":{"message":"Welcome to Billionaire Homes Dubai! How can we help you today?"},"position":{"x":250,"y":50}},{"id":"node_2","type":"ask_question","data":{"question":"What are you looking for?","options":["Buy Property","Rent Property","Investment","Other"]},"position":{"x":250,"y":200}},{"id":"node_3","type":"condition","data":{"variable":"answer","conditions":[{"value":"Buy Property","next":"node_4"},{"value":"Investment","next":"node_5"}]},"position":{"x":250,"y":380}},{"id":"node_4","type":"send_message","data":{"message":"Great! We have amazing properties for sale. An agent will contact you shortly."},"position":{"x":100,"y":530}},{"id":"node_5","type":"send_message","data":{"message":"Excellent! We have the best investment opportunities in Dubai. Let me connect you with our investment specialist."},"position":{"x":400,"y":530}}],"edges":[{"from":"node_1","to":"node_2"},{"from":"node_2","to":"node_3"},{"from":"node_3","to":"node_4"},{"from":"node_3","to":"node_5"}]}'),
('Property Inquiry Flow', 'Handles property inquiries', 'property', 0, '{"nodes":[{"id":"node_1","type":"send_message","data":{"message":"Which area are you interested in?"},"position":{"x":250,"y":50}},{"id":"node_2","type":"ask_question","data":{"question":"Select area:","options":["Dubai Hills","Emaar South","Downtown","Palm Jumeirah"]},"position":{"x":250,"y":200}},{"id":"node_3","type":"send_message","data":{"message":"We have several properties in that area. Let me share the latest listings with you."},"position":{"x":250,"y":380}},{"id":"node_4","type":"delay","data":{"seconds":2},"position":{"x":250,"y":500}},{"id":"node_5","type":"api_call","data":{"url":"/api/properties","method":"GET"},"position":{"x":250,"y":620}}],"edges":[{"from":"node_1","to":"node_2"},{"from":"node_2","to":"node_3"},{"from":"node_3","to":"node_4"},{"from":"node_4","to":"node_5"}]}');

-- Insert analytics events
INSERT INTO analytics_events (event_type, data, created_at) VALUES
('message_sent', '{"count": 45}', NOW() - INTERVAL 6 DAY),
('message_sent', '{"count": 62}', NOW() - INTERVAL 5 DAY),
('message_sent', '{"count": 38}', NOW() - INTERVAL 4 DAY),
('message_sent', '{"count": 71}', NOW() - INTERVAL 3 DAY),
('message_sent', '{"count": 55}', NOW() - INTERVAL 2 DAY),
('message_sent', '{"count": 89}', NOW() - INTERVAL 1 DAY),
('message_sent', '{"count": 43}', NOW()),
('message_delivered', '{"count": 40}', NOW() - INTERVAL 6 DAY),
('message_delivered', '{"count": 58}', NOW() - INTERVAL 5 DAY),
('message_delivered', '{"count": 35}', NOW() - INTERVAL 4 DAY),
('message_delivered', '{"count": 67}', NOW() - INTERVAL 3 DAY),
('message_delivered', '{"count": 50}', NOW() - INTERVAL 2 DAY),
('message_delivered', '{"count": 82}', NOW() - INTERVAL 1 DAY),
('message_delivered', '{"count": 39}', NOW()),
('message_read', '{"count": 28}', NOW() - INTERVAL 6 DAY),
('message_read', '{"count": 41}', NOW() - INTERVAL 5 DAY),
('message_read', '{"count": 22}', NOW() - INTERVAL 4 DAY),
('message_read', '{"count": 52}', NOW() - INTERVAL 3 DAY),
('message_read', '{"count": 38}', NOW() - INTERVAL 2 DAY),
('message_read', '{"count": 65}', NOW() - INTERVAL 1 DAY),
('message_read', '{"count": 30}', NOW());
