-- =====================================================================
-- Hệ thống quản lý hoạt động đầu giờ - Tin học 6
-- CSDL: MySQL / MariaDB (XAMPP), InnoDB, utf8mb4. 24 bảng.
-- Cách chạy: phpMyAdmin > Import schema.sql, rồi seed.sql.
-- Dùng CREATE TABLE IF NOT EXISTS nên chạy lại không làm mất dữ liệu.
-- Muốn làm lại từ đầu: DROP DATABASE hoatdongdaugio; rồi import lại.
-- =====================================================================
CREATE DATABASE IF NOT EXISTS hoatdongdaugio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hoatdongdaugio;

-- ---------------------------------------------------------------------
-- 1. Vai trò và phân quyền (module-auth, module-user)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code       VARCHAR(20)  NOT NULL,                 -- ADMIN, PHT, TTCM, GV
  name       VARCHAR(100) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_roles_code (code)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS functions (
  code       SMALLINT UNSIGNED NOT NULL,            -- số dòng trong bảng phân quyền (1-25)
  name       VARCHAR(200) NOT NULL,
  group_name VARCHAR(100) NOT NULL,
  PRIMARY KEY (code)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS role_permissions (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_id       BIGINT UNSIGNED NOT NULL,
  function_code SMALLINT UNSIGNED NOT NULL,
  level         ENUM('Q','D','T','C','K') NOT NULL, -- Q toàn quyền, D duyệt, T thao tác, C xem, K không
  scope         ENUM('all','own','class','team','team_level','school') NOT NULL DEFAULT 'all',
  PRIMARY KEY (id),
  UNIQUE KEY uq_role_function (role_id, function_code),
  CONSTRAINT fk_rp_role     FOREIGN KEY (role_id)       REFERENCES roles (id)     ON DELETE CASCADE,
  CONSTRAINT fk_rp_function FOREIGN KEY (function_code) REFERENCES functions (code) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. Tài khoản
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  username      VARCHAR(50)  NOT NULL,
  email         VARCHAR(150) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  full_name     VARCHAR(150) NOT NULL,
  phone         VARCHAR(20)  NULL,
  avatar        VARCHAR(255) NULL,
  role_id       BIGINT UNSIGNED NOT NULL,
  status        ENUM('active','locked') NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role_id),
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_resets (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    BIGINT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,                     -- SHA-256 của token gửi qua email
  expires_at DATETIME NOT NULL,
  used_at    DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pr_token (token_hash),
  KEY idx_pr_user (user_id),
  CONSTRAINT fk_pr_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. Danh mục dùng chung (module-category, module-student)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS classes (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code        VARCHAR(20)  NOT NULL,
  name        VARCHAR(100) NOT NULL,
  grade       TINYINT UNSIGNED NOT NULL DEFAULT 6,
  description VARCHAR(255) NULL,
  status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_classes_code (code)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teacher_classes (        -- "lớp dạy" (giả định G3)
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  teacher_id BIGINT UNSIGNED NOT NULL,
  class_id   BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_teacher_class (teacher_id, class_id),
  CONSTRAINT fk_tc_teacher FOREIGN KEY (teacher_id) REFERENCES users (id)   ON DELETE CASCADE,
  CONSTRAINT fk_tc_class   FOREIGN KEY (class_id)   REFERENCES classes (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS students (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_code VARCHAR(30)  NOT NULL,
  full_name    VARCHAR(150) NOT NULL,
  dob          DATE NULL,
  gender       ENUM('M','F') NULL,
  class_id     BIGINT UNSIGNED NOT NULL,
  status       ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_students_code (student_code),
  KEY idx_students_class (class_id),
  CONSTRAINT fk_students_class FOREIGN KEY (class_id) REFERENCES classes (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS topics (                 -- chủ đề: danh mục lớn chứa các bài học
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(200) NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  status     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lessons (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  topic_id   BIGINT UNSIGNED NOT NULL,
  name       VARCHAR(200) NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  status     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_lessons_topic (topic_id),
  CONSTRAINT fk_lessons_topic FOREIGN KEY (topic_id) REFERENCES topics (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS activity_types (         -- hình thức: trò chơi, câu đố, thảo luận
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(100) NOT NULL,
  description VARCHAR(255) NULL,
  status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_activity_types_name (name)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. Hoạt động đầu giờ (module-activity, module-approval)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS activities (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  title       VARCHAR(255) NOT NULL,
  description TEXT NULL,
  content     MEDIUMTEXT NULL,
  topic_id    BIGINT UNSIGNED NOT NULL,
  lesson_id   BIGINT UNSIGNED NOT NULL,
  type_id     BIGINT UNSIGNED NOT NULL,
  created_by  BIGINT UNSIGNED NOT NULL,
  -- Vòng đời (giả định G1, G2): pending_team -> pending_school -> approved;
  -- rejected / need_edit ở mỗi cấp; inactive = xóa mềm
  status      ENUM('pending_team','pending_school','approved','rejected','need_edit','inactive')
              NOT NULL DEFAULT 'pending_team',
  deleted_at  DATETIME NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_activities_filter (topic_id, lesson_id, type_id),
  KEY idx_activities_status (status),
  KEY idx_activities_creator (created_by),
  CONSTRAINT fk_act_topic   FOREIGN KEY (topic_id)   REFERENCES topics (id),
  CONSTRAINT fk_act_lesson  FOREIGN KEY (lesson_id)  REFERENCES lessons (id),
  CONSTRAINT fk_act_type    FOREIGN KEY (type_id)    REFERENCES activity_types (id),
  CONSTRAINT fk_act_creator FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS activity_attachments (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  activity_id   BIGINT UNSIGNED NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name   VARCHAR(255) NOT NULL,
  mime_type     VARCHAR(100) NULL,
  file_size     BIGINT UNSIGNED NOT NULL DEFAULT 0,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_att_activity (activity_id),
  CONSTRAINT fk_att_activity FOREIGN KEY (activity_id) REFERENCES activities (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS activity_questions (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  activity_id   BIGINT UNSIGNED NOT NULL,
  question_text TEXT NOT NULL,
  explanation   TEXT NULL,
  sort_order    INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_q_activity (activity_id),
  CONSTRAINT fk_q_activity FOREIGN KEY (activity_id) REFERENCES activities (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS activity_answers (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  question_id BIGINT UNSIGNED NOT NULL,
  answer_text TEXT NOT NULL,
  is_correct  TINYINT(1) NOT NULL DEFAULT 0,
  sort_order  INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_a_question (question_id),
  CONSTRAINT fk_a_question FOREIGN KEY (question_id) REFERENCES activity_questions (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS activity_reviews (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  activity_id BIGINT UNSIGNED NOT NULL,
  reviewer_id BIGINT UNSIGNED NOT NULL,
  level       ENUM('team','school') NOT NULL,
  decision    ENUM('approve','reject','request_edit') NOT NULL,
  note        TEXT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_rev_activity (activity_id),
  CONSTRAINT fk_rev_activity FOREIGN KEY (activity_id) REFERENCES activities (id) ON DELETE CASCADE,
  CONSTRAINT fk_rev_reviewer FOREIGN KEY (reviewer_id) REFERENCES users (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (          -- chỉ dùng cho kết quả duyệt
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     BIGINT UNSIGNED NOT NULL,
  activity_id BIGINT UNSIGNED NULL,
  type        VARCHAR(30)  NOT NULL,
  title       VARCHAR(200) NOT NULL,
  message     VARCHAR(500) NULL,
  is_read     TINYINT(1) NOT NULL DEFAULT 0,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notif_user (user_id, is_read),
  CONSTRAINT fk_notif_user     FOREIGN KEY (user_id)     REFERENCES users (id)      ON DELETE CASCADE,
  CONSTRAINT fk_notif_activity FOREIGN KEY (activity_id) REFERENCES activities (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. Tổ chức hoạt động và kết quả học sinh (module-session, module-result)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS class_sessions (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  activity_id      BIGINT UNSIGNED NOT NULL,
  teacher_id       BIGINT UNSIGNED NOT NULL,
  class_id         BIGINT UNSIGNED NULL,            -- gán khi thiết lập thông số
  duration_minutes SMALLINT UNSIGNED NULL,
  question_count   SMALLINT UNSIGNED NULL,
  play_mode        VARCHAR(255) NULL,
  config_json      JSON NULL,
  status           ENUM('selected','configured','running','paused','finished') NOT NULL DEFAULT 'selected',
  started_at       DATETIME NULL,
  elapsed_seconds  INT UNSIGNED NOT NULL DEFAULT 0,
  ended_at         DATETIME NULL,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_sess_teacher (teacher_id),
  CONSTRAINT fk_sess_activity FOREIGN KEY (activity_id) REFERENCES activities (id),
  CONSTRAINT fk_sess_teacher  FOREIGN KEY (teacher_id)  REFERENCES users (id),
  CONSTRAINT fk_sess_class    FOREIGN KEY (class_id)    REFERENCES classes (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS results (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  session_id   BIGINT UNSIGNED NOT NULL,
  student_id   BIGINT UNSIGNED NOT NULL,
  activity_id  BIGINT UNSIGNED NOT NULL,
  class_id     BIGINT UNSIGNED NOT NULL,
  score        DECIMAL(4,2) NULL,
  rating_level VARCHAR(50) NULL,
  comment      TEXT NULL,
  save_status  ENUM('draft','saved','failed') NOT NULL DEFAULT 'draft',
  recorded_by  BIGINT UNSIGNED NOT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_result_session_student (session_id, student_id),
  KEY idx_results_class (class_id),
  KEY idx_results_activity (activity_id),
  CONSTRAINT fk_res_session  FOREIGN KEY (session_id)  REFERENCES class_sessions (id),
  CONSTRAINT fk_res_student  FOREIGN KEY (student_id)  REFERENCES students (id),
  CONSTRAINT fk_res_activity FOREIGN KEY (activity_id) REFERENCES activities (id),
  CONSTRAINT fk_res_class    FOREIGN KEY (class_id)    REFERENCES classes (id),
  CONSTRAINT fk_res_user     FOREIGN KEY (recorded_by) REFERENCES users (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS result_histories (       -- chi tiết từng lần đánh giá (MDVI2.2)
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  result_id    BIGINT UNSIGNED NOT NULL,
  score        DECIMAL(4,2) NULL,
  rating_level VARCHAR(50) NULL,
  comment      TEXT NULL,
  changed_by   BIGINT UNSIGNED NOT NULL,
  changed_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_rh_result (result_id),
  CONSTRAINT fk_rh_result FOREIGN KEY (result_id) REFERENCES results (id) ON DELETE CASCADE,
  CONSTRAINT fk_rh_user   FOREIGN KEY (changed_by) REFERENCES users (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. Sao lưu, phục hồi, nhật ký (module-backup, module-log)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS backups (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  file_name  VARCHAR(255) NOT NULL,
  file_path  VARCHAR(500) NOT NULL,
  scope      ENUM('full','activities','results') NOT NULL DEFAULT 'full',
  type       ENUM('manual','auto') NOT NULL DEFAULT 'manual',
  note       VARCHAR(255) NULL,
  file_size  BIGINT UNSIGNED NOT NULL DEFAULT 0,
  checksum   CHAR(64) NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_backups_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS backup_settings (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  frequency    ENUM('daily','weekly') NOT NULL DEFAULT 'daily',
  run_time     TIME NOT NULL DEFAULT '02:00:00',
  storage_path VARCHAR(255) NOT NULL DEFAULT 'storage/backups',
  max_keep     SMALLINT UNSIGNED NOT NULL DEFAULT 10,
  enabled      TINYINT(1) NOT NULL DEFAULT 0,
  updated_by   BIGINT UNSIGNED NULL,
  updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_bs_user FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS backup_logs (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  backup_id    BIGINT UNSIGNED NULL,
  operation    ENUM('backup','restore') NOT NULL,
  status       ENUM('success','failed') NOT NULL,
  started_at   DATETIME NOT NULL,
  finished_at  DATETIME NULL,
  file_size    BIGINT UNSIGNED NULL,
  message      VARCHAR(500) NULL,
  performed_by BIGINT UNSIGNED NULL,
  PRIMARY KEY (id),
  KEY idx_bl_started (started_at),
  CONSTRAINT fk_bl_backup FOREIGN KEY (backup_id)    REFERENCES backups (id) ON DELETE SET NULL,
  CONSTRAINT fk_bl_user   FOREIGN KEY (performed_by) REFERENCES users (id)   ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS system_logs (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     BIGINT UNSIGNED NULL,
  action      VARCHAR(50)  NOT NULL,
  module      VARCHAR(50)  NOT NULL,
  target_type VARCHAR(50)  NULL,
  target_id   BIGINT UNSIGNED NULL,
  detail      VARCHAR(500) NULL,
  ip_address  VARCHAR(45)  NULL,
  status      VARCHAR(20)  NOT NULL DEFAULT 'success',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_sl_created (created_at),
  KEY idx_sl_user (user_id),
  KEY idx_sl_action (action),
  CONSTRAINT fk_sl_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB;
