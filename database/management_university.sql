-- ============================================================
-- MANAGEMENT UNIVERSITY - DATABASE SCHEMA
-- PostgreSQL Script - Complete Setup
-- Date: 2026-02-23
-- ============================================================

-- ============================================================
-- 1. UNIVERSITIES (Multi-tenant foundation)
-- ============================================================
CREATE TABLE universities (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    timezone VARCHAR(50) DEFAULT 'UTC',
    address TEXT,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

CREATE INDEX idx_universities_name ON universities(name);

-- ============================================================
-- 2. USERS (Core authentication with multi-tenant)
-- ============================================================
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    university_id INTEGER NOT NULL REFERENCES universities(id) ON DELETE CASCADE,
    name VARCHAR(255),
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(255),
    last_name VARCHAR(255),
    phone VARCHAR(20),
    address TEXT,
    role VARCHAR(50) DEFAULT 'student',
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP NULL,
    email_verified_at TIMESTAMP NULL,
    two_factor_secret TEXT,
    two_factor_recovery_codes TEXT,
    two_factor_confirmed_at TIMESTAMP NULL,
    remember_token VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE(university_id, email)
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_university_id ON users(university_id);
CREATE INDEX idx_users_role ON users(role);

-- ============================================================
-- 3. JWT TOKENS (Stateless authentication)
-- ============================================================
CREATE TABLE jwt_tokens (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    university_id INTEGER NOT NULL REFERENCES universities(id) ON DELETE CASCADE,
    type VARCHAR(20) NOT NULL CHECK (type IN ('access', 'refresh')),
    token TEXT NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    revoked_at TIMESTAMP NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_jwt_tokens_user_id_type ON jwt_tokens(user_id, type);
CREATE INDEX idx_jwt_tokens_user_id_revoked ON jwt_tokens(user_id, revoked_at);
CREATE INDEX idx_jwt_tokens_expires_at ON jwt_tokens(expires_at);

-- ============================================================
-- 4. AUDIT LOGS (Compliance & traceability)
-- ============================================================
CREATE TABLE audit_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    university_id INTEGER NOT NULL REFERENCES universities(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    resource_type VARCHAR(50) NOT NULL,
    resource_id INTEGER,
    action VARCHAR(50) NOT NULL,
    old_values JSONB,
    new_values JSONB,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_audit_logs_user_id_created ON audit_logs(user_id, created_at);
CREATE INDEX idx_audit_logs_resource_type_id ON audit_logs(resource_type, resource_id);
CREATE INDEX idx_audit_logs_action_created ON audit_logs(action, created_at);

-- ============================================================
-- 5. TEACHERS (Enseignants)
-- ============================================================
CREATE TABLE teachers (
    id SERIAL PRIMARY KEY,
    university_id INTEGER NOT NULL REFERENCES universities(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    specialty VARCHAR(255),
    grade VARCHAR(50),
    hire_date DATE,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

CREATE INDEX idx_teachers_university_id_status ON teachers(university_id, status);
CREATE INDEX idx_teachers_user_id ON teachers(user_id);

-- ============================================================
-- 6. STUDENTS (Étudiants)
-- ============================================================
CREATE TABLE students (
    id SERIAL PRIMARY KEY,
    university_id INTEGER NOT NULL REFERENCES universities(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    registration_number VARCHAR(255) NOT NULL,
    birth_date DATE,
    level VARCHAR(50),
    enrollment_year INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE(university_id, registration_number)
);

CREATE INDEX idx_students_university_id_level ON students(university_id, level);
CREATE INDEX idx_students_user_id ON students(user_id);
CREATE INDEX idx_students_registration_number ON students(registration_number);

-- ============================================================
-- 7. COURSES (Cours)
-- ============================================================
CREATE TABLE courses (
    id SERIAL PRIMARY KEY,
    university_id INTEGER NOT NULL REFERENCES universities(id) ON DELETE CASCADE,
    teacher_id INTEGER REFERENCES teachers(id) ON DELETE SET NULL,
    code VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    credits INTEGER,
    level VARCHAR(50),
    semester VARCHAR(50),
    max_lateness_minutes INTEGER DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE(university_id, code)
);

CREATE INDEX idx_courses_university_id_teacher_id ON courses(university_id, teacher_id);
CREATE INDEX idx_courses_code ON courses(code);

-- ============================================================
-- 8. CLASSROOMS (Salles)
-- ============================================================
CREATE TABLE classrooms (
    id SERIAL PRIMARY KEY,
    university_id INTEGER NOT NULL REFERENCES universities(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    building VARCHAR(255),
    room_number VARCHAR(255),
    capacity INTEGER,
    equipment TEXT,
    type VARCHAR(50) DEFAULT 'classroom',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE(university_id, name)
);

CREATE INDEX idx_classrooms_university_id_is_active ON classrooms(university_id, is_active);

-- ============================================================
-- 9. TIMETABLE ENTRIES (Sessions de cours)
-- ============================================================
CREATE TABLE timetable_entries (
    id SERIAL PRIMARY KEY,
    university_id INTEGER NOT NULL REFERENCES universities(id) ON DELETE CASCADE,
    course_id INTEGER NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
    classroom_id INTEGER NOT NULL REFERENCES classrooms(id) ON DELETE CASCADE,
    day_of_week VARCHAR(50) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    session_type VARCHAR(50) DEFAULT 'lecture',
    recurrence_pattern VARCHAR(255),
    start_date DATE NOT NULL,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

CREATE INDEX idx_timetable_entries_university_day_start ON timetable_entries(university_id, day_of_week, start_time);
CREATE INDEX idx_timetable_entries_course_id ON timetable_entries(course_id);
CREATE INDEX idx_timetable_entries_classroom_id ON timetable_entries(classroom_id);

-- ============================================================
-- 10. COURSE ENROLLMENTS (Inscriptions)
-- ============================================================
CREATE TABLE course_enrollments (
    id SERIAL PRIMARY KEY,
    university_id INTEGER NOT NULL REFERENCES universities(id) ON DELETE CASCADE,
    student_id INTEGER NOT NULL REFERENCES students(id) ON DELETE CASCADE,
    course_id INTEGER NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
    status VARCHAR(50) DEFAULT 'enrolled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    UNIQUE(student_id, course_id)
);

CREATE INDEX idx_course_enrollments_university_student_status ON course_enrollments(university_id, student_id, status);
CREATE INDEX idx_course_enrollments_course_id ON course_enrollments(course_id);

-- ============================================================
-- 11. ATTENDANCES (Présences)
-- ============================================================
CREATE TABLE attendances (
    id SERIAL PRIMARY KEY,
    university_id INTEGER NOT NULL REFERENCES universities(id) ON DELETE CASCADE,
    timetable_entry_id INTEGER NOT NULL REFERENCES timetable_entries(id) ON DELETE CASCADE,
    student_id INTEGER NOT NULL REFERENCES students(id) ON DELETE CASCADE,
    status VARCHAR(50) NOT NULL CHECK (status IN ('present', 'absent', 'late', 'justified')) DEFAULT 'absent',
    scanned_at TIMESTAMP NULL,
    verification_method VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE(timetable_entry_id, student_id)
);

CREATE INDEX idx_attendances_university_student_status ON attendances(university_id, student_id, status);
CREATE INDEX idx_attendances_timetable_entry_status ON attendances(timetable_entry_id, status);

-- ============================================================
-- 12. RFID CARDS (Cartes RFID avec imprédictibilité UUID)
-- ============================================================
CREATE TABLE rfid_cards (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    university_id INTEGER NOT NULL REFERENCES universities(id) ON DELETE CASCADE,
    student_id INTEGER NOT NULL UNIQUE REFERENCES students(id) ON DELETE CASCADE,
    card_number VARCHAR(255) NOT NULL UNIQUE,
    is_active BOOLEAN DEFAULT TRUE,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deactivated_at TIMESTAMP NULL,
    deactivation_reason VARCHAR(255),
    last_scanned_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_rfid_cards_university_is_active ON rfid_cards(university_id, is_active);
CREATE INDEX idx_rfid_cards_student_id_is_active ON rfid_cards(student_id, is_active);
CREATE INDEX idx_rfid_cards_card_number ON rfid_cards(card_number);

-- ============================================================
-- 13. ACCESS LOGS (Logs d'accès RFID)
-- ============================================================
CREATE TABLE access_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    university_id INTEGER NOT NULL REFERENCES universities(id) ON DELETE CASCADE,
    rfid_card_id UUID NOT NULL REFERENCES rfid_cards(id) ON DELETE CASCADE,
    classroom_id INTEGER NOT NULL REFERENCES classrooms(id) ON DELETE CASCADE,
    scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) NOT NULL CHECK (status IN ('granted', 'refused')),
    refusal_reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_access_logs_rfid_card_id_scanned ON access_logs(rfid_card_id, scanned_at);
CREATE INDEX idx_access_logs_classroom_id_scanned ON access_logs(classroom_id, scanned_at);
CREATE INDEX idx_access_logs_status_scanned ON access_logs(status, scanned_at);

-- ============================================================
-- CACHE & SESSIONS (Laravel Native)
-- ============================================================
CREATE TABLE cache (
    key VARCHAR(255) PRIMARY KEY,
    value TEXT NOT NULL,
    expiration INTEGER
);

CREATE TABLE password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL
);

CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload TEXT NOT NULL,
    last_activity INTEGER
);

CREATE INDEX idx_sessions_user_id ON sessions(user_id);
CREATE INDEX idx_sessions_last_activity ON sessions(last_activity);

-- ============================================================
-- JOBS (Queue system)
-- ============================================================
CREATE TABLE jobs (
    id BIGSERIAL PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload TEXT NOT NULL,
    attempts SMALLINT NOT NULL DEFAULT 0,
    reserved_at INT,
    available_at INT NOT NULL,
    created_at INT NOT NULL
);

CREATE INDEX idx_jobs_queue_reserved_available ON jobs(queue, reserved_at, available_at);

-- ============================================================
-- SUMMARY OF KEY FEATURES
-- ============================================================
/*
MULTI-TENANT (university_id everywhere):
- Each university has isolated data
- Middleware filters by university_id

AUTHENTICATION (JWT with refresh tokens):
- jwt_tokens table stores access/refresh tokens
- UUID for unpredictability
- revoked_at for instant logout

AUDIT LOGS (JSONB for flexibility):
- old_values/new_values track all changes
- action = create, update, delete, login, rfid_scan, access_denied
- ip_address for security

RFID SYSTEM:
- rfid_cards = physical cards assigned to students (UUID)
- access_logs = every scan attempt (granted/refused)
- Lateness logic = scan_time > start_time + course.max_lateness_minutes

SOFT DELETES (GDPR compliance):
- deleted_at timestamp instead of permanent deletion
- Exceptions: jwt_tokens, audit_logs, access_logs (immutable history)

INDEXES (Performance):
- Multi-column indexes for common queries
- UNIQUE constraints where needed
*/

-- ============================================================
-- EXAMPLE QUERIES FOR TESTING
-- ============================================================

-- Get all students with their universities
-- SELECT u.first_name, u.last_name, s.registration_number, uni.name
-- FROM users u
-- JOIN students s ON u.id = s.user_id
-- JOIN universities uni ON u.university_id = uni.id;

-- Get attendance rate by student for a course
-- SELECT s.id, u.first_name, u.last_name,
--        COUNT(CASE WHEN a.status = 'present' THEN 1 END)::float / COUNT(*) * 100 as attendance_rate
-- FROM students s
-- JOIN users u ON s.user_id = u.id
-- JOIN attendances a ON s.id = a.student_id
-- GROUP BY s.id, u.first_name, u.last_name;

-- Get RFID access attempts with refusal reasons
-- SELECT al.scanned_at, rc.student_id, al.status, al.refusal_reason, c.name
-- FROM access_logs al
-- JOIN rfid_cards rc ON al.rfid_card_id = rc.id
-- JOIN classrooms c ON al.classroom_id = c.id
-- WHERE al.status = 'refused'
-- ORDER BY al.scanned_at DESC;
