-- 创建用户表
CREATE TABLE IF NOT EXISTS chat_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    display_name VARCHAR(50),
    avatar VARCHAR(255),
    is_admin BOOLEAN DEFAULT FALSE,
    is_banned BOOLEAN DEFAULT FALSE,
    warning_count INT DEFAULT 0,
    last_warning_reset TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    registration_ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 创建消息表
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT,
    message_type ENUM('text', 'image', 'video', 'file') DEFAULT 'text',
    file_path VARCHAR(255),
    file_name VARCHAR(255),
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES chat_users(id) ON DELETE CASCADE
);

-- 创建公告表
CREATE TABLE IF NOT EXISTS chat_announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_by INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES chat_users(id) ON DELETE CASCADE
);

-- 创建用户令牌表（记住我功能）
CREATE TABLE IF NOT EXISTS user_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES chat_users(id) ON DELETE CASCADE
);

-- 创建系统设置表
CREATE TABLE IF NOT EXISTS chat_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 创建用户行为记录表（用于反爬虫检测）
CREATE TABLE IF NOT EXISTS user_behavior (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type ENUM('message', 'login', 'register') NOT NULL,
    action_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    INDEX idx_user_action (user_id, action_type, action_time),
    INDEX idx_time (action_time),
    FOREIGN KEY (user_id) REFERENCES chat_users(id) ON DELETE CASCADE
);

-- 插入默认设置
INSERT IGNORE INTO chat_settings (setting_key, setting_value) VALUES
('chat_rules', '欢迎使用聊天室！请遵守以下规则：\n1. 尊重他人，文明交流\n2. 禁止发布违法、不良信息\n3. 禁止恶意刷屏\n4. 遵守管理员的管理'),
('background_color', '#667eea'),
('welcome_message', '欢迎来到高级聊天室！'),
('background_image', '');

-- 创建默认管理员账户 (用户名: admin, 密码: admin123)
INSERT IGNORE INTO chat_users (username, password, display_name, is_admin) VALUES
('admin', '$2y$10$NwsAIaSs1BbpW/uQyIACHOtqUehU3P43XCWvkV2KQqCRxNlV372Fq', '管理员', TRUE);
