-- ============================================================
-- IILMS - Intelligent Inclusive Learning Management System
-- Database Schema (MySQL)
-- ============================================================

-- Create database (optional)
CREATE DATABASE IF NOT EXISTS iilms
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE iilms;

-- ============================================================
-- 1. disabilities
-- Stores disability types and accessibility preferences
-- ============================================================
CREATE TABLE disabilities (
    disability_id INT PRIMARY KEY AUTO_INCREMENT,
    type ENUM('blind', 'deaf', 'speech_impaired', 'physical', 'none') NOT NULL DEFAULT 'none',
    description TEXT,
    accessibility_preferences JSON,          -- e.g. {"voice_nav":true, "large_fonts":false, "sign_language":true}
    learning_preferences JSON,               -- e.g. {"preferred_format":"audio","pace":"slow"}
    emergency_contact VARCHAR(100),
    assistive_devices JSON,                  -- e.g. ["wheelchair","braille_machine"]
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- 2. users
-- All system users (students, lecturers, admins, parents, support)
-- ============================================================
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'lecturer', 'student', 'parent', 'support') NOT NULL DEFAULT 'student',
    disability_id INT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (disability_id) REFERENCES disabilities(disability_id) ON DELETE SET NULL,
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_email (email)
);

-- ============================================================
-- 3. departments
-- Academic departments
-- ============================================================
CREATE TABLE departments (
    department_id INT PRIMARY KEY AUTO_INCREMENT,
    department_name VARCHAR(100) UNIQUE NOT NULL,
    code VARCHAR(10) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 4. semesters
-- Academic semesters (e.g., 1st, 2nd)
-- ============================================================
CREATE TABLE semesters (
    semester_id INT PRIMARY KEY AUTO_INCREMENT,
    semester_name VARCHAR(20) NOT NULL,  -- e.g., "First Semester", "Second Semester"
    semester_code VARCHAR(10) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 5. academic_sessions
-- Academic years/sessions (e.g., 2024/2025)
-- ============================================================
CREATE TABLE academic_sessions (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    session_name VARCHAR(20) NOT NULL,  -- e.g., "2024/2025"
    is_current BOOLEAN DEFAULT FALSE,
    start_date DATE,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_session_name (session_name)
);

-- ============================================================
-- 6. courses
-- Main course catalog
-- ============================================================
CREATE TABLE courses (
    course_id INT PRIMARY KEY AUTO_INCREMENT,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    course_title VARCHAR(200) NOT NULL,
    credit_unit INT NOT NULL,
    lecturer_id INT NULL,               -- references users.user_id (must have role='lecturer')
    semester_id INT NOT NULL,
    department_id INT NULL,
    session_id INT NULL,                -- current academic session
    accessibility_compliance BOOLEAN DEFAULT FALSE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lecturer_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (semester_id) REFERENCES semesters(semester_id),
    FOREIGN KEY (department_id) REFERENCES departments(department_id),
    FOREIGN KEY (session_id) REFERENCES academic_sessions(session_id),
    INDEX idx_lecturer (lecturer_id),
    INDEX idx_semester (semester_id)
);

-- ============================================================
-- 7. course_materials
-- Uploaded materials and their accessibility conversions
-- ============================================================
CREATE TABLE course_materials (
    material_id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    original_file VARCHAR(255) NOT NULL,        -- path to original (PDF/DOC/PPT/Video)
    audio_file VARCHAR(255),                    -- generated audio (MP3)
    braille_file VARCHAR(255),                  -- .brf file
    captioned_video VARCHAR(255),               -- captioned video file
    simplified_text TEXT,                       -- easy English or Hausa
    summary_text TEXT,                          -- AI-generated summary
    flashcards JSON,                            -- [{ "question": "...", "answer": "..." }]
    quiz_questions JSON,                        -- for CBT
    mind_map TEXT,                              -- textual representation
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    INDEX idx_course (course_id)
);

-- ============================================================
-- 8. assignments
-- Course assignments with disability-aware submission types
-- ============================================================
CREATE TABLE assignments (
    assignment_id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    due_date DATETIME NOT NULL,
    allowed_submission_types JSON,   -- e.g., ["voice","video","text","braille","file"]
    max_score DECIMAL(5,2) DEFAULT 100.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    INDEX idx_course_due (course_id, due_date)
);

-- ============================================================
-- 9. submissions
-- Student submissions for assignments
-- ============================================================
CREATE TABLE submissions (
    submission_id INT PRIMARY KEY AUTO_INCREMENT,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    submission_type ENUM('voice', 'video', 'text', 'braille', 'file') NOT NULL,
    file_path VARCHAR(255),                      -- if file/voice/video
    transcript TEXT,                             -- for voice/video transcription
    grade DECIMAL(5,2) NULL,
    feedback TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES assignments(assignment_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_assignment (assignment_id),
    INDEX idx_student (student_id)
);

-- ============================================================
-- 10. attendance
-- Tracks attendance via QR, Face, or Voice
-- ============================================================
CREATE TABLE attendance (
    attendance_id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    student_id INT NOT NULL,
    method ENUM('qr', 'face', 'voice') NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_course_student (course_id, student_id),
    INDEX idx_date (timestamp)
);

-- ============================================================
-- 11. ai_logs
-- Logs all AI interactions (tutor, summarizer, recommendations)
-- ============================================================
CREATE TABLE ai_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    query TEXT NOT NULL,
    response TEXT,
    disability_type VARCHAR(20),                -- context for adaptation
    module VARCHAR(50),                         -- 'tutor', 'summarizer', 'recommendation'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_module (module),
    INDEX idx_created (created_at)
);

-- ============================================================
-- 12. appointments
-- Counselling / support appointments
-- ============================================================
CREATE TABLE appointments (
    appointment_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    counsellor_id INT NULL,                     -- could be admin or support
    appointment_date DATETIME NOT NULL,
    type ENUM('academic', 'mental_health', 'disability', 'career', 'emergency') NOT NULL,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (counsellor_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_student (student_id),
    INDEX idx_date (appointment_date)
);

-- ============================================================
-- 13. support_tickets
-- Technical or accessibility support requests
-- ============================================================
CREATE TABLE support_tickets (
    ticket_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    assigned_to INT NULL,                       -- support staff
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_status (status)
);

-- ============================================================
-- 14. forum_posts
-- Discussion forum for courses
-- ============================================================
CREATE TABLE forum_posts (
    post_id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    author_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    is_announcement BOOLEAN DEFAULT FALSE,
    pinned BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_course (course_id),
    INDEX idx_pinned (pinned)
);

-- ============================================================
-- 15. messages
-- Private messages between users
-- ============================================================
CREATE TABLE messages (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    content TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    parent_message_id INT NULL,                 -- for threaded replies
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (parent_message_id) REFERENCES messages(message_id) ON DELETE CASCADE,
    INDEX idx_sender_receiver (sender_id, receiver_id),
    INDEX idx_read (is_read)
);

-- ============================================================
-- 16. chat_history (for live chat, if using separate table)
-- ============================================================
CREATE TABLE chat_history (
    chat_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_id VARCHAR(100),                   -- for grouping conversations
    message TEXT NOT NULL,
    is_from_user BOOLEAN DEFAULT TRUE,         -- true=user, false=AI/bot
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_session (user_id, session_id)
);

-- ============================================================
-- 17. ai_recommendation_logs (separate from ai_logs for analytics)
-- ============================================================
CREATE TABLE ai_recommendations (
    rec_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    recommended_resource VARCHAR(255),         -- video/book/exercise
    resource_type ENUM('video', 'book', 'exercise', 'tutor') NOT NULL,
    reason TEXT,
    effectiveness_score DECIMAL(3,2) NULL,     -- feedback
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
);

-- ============================================================
-- 18. learning_analytics
-- Aggregated performance/engagement data
-- ============================================================
CREATE TABLE learning_analytics (
    analytics_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    average_score DECIMAL(5,2),
    attendance_rate DECIMAL(5,2),
    engagement_score DECIMAL(5,2),              -- based on logins, forum activity
    risk_score DECIMAL(5,2),                    -- early warning (higher = at risk)
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    INDEX idx_user_course (user_id, course_id)
);

-- ============================================================
-- 19. audit_logs
-- For security and compliance (GDPR, NDPA)
-- ============================================================
CREATE TABLE audit_logs (
    audit_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_data JSON,
    new_data JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
);

-- ============================================================
-- Sample data (optional)
-- ============================================================
INSERT INTO disabilities (type, accessibility_preferences) VALUES
('none', '{"voice_nav":false, "large_fonts":false, "sign_language":false}'),
('blind', '{"voice_nav":true, "large_fonts":true, "sign_language":false}'),
('deaf', '{"voice_nav":false, "large_fonts":false, "sign_language":true}'),
('speech_impaired', '{"voice_nav":false, "large_fonts":false, "sign_language":false}'),
('physical', '{"voice_nav":true, "large_fonts":true, "sign_language":false}');

INSERT INTO semesters (semester_name, semester_code) VALUES
('First Semester', '1'),
('Second Semester', '2');

INSERT INTO academic_sessions (session_name, is_current) VALUES
('2024/2025', 1),
('2023/2024', 0);

INSERT INTO departments (department_name, code) VALUES
('Computer Science', 'CSC'),
('Mathematics', 'MTH'),
('Physics', 'PHY');

-- Create an admin user (password: admin123)
INSERT INTO users (full_name, email, password_hash, role, disability_id, status) VALUES
('System Admin', 'admin@iilms.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, 'active');
-- Password hash is for 'admin123' (using password_hash('admin123', PASSWORD_BCRYPT))

-- ============================================================
-- End of schema
-- ============================================================