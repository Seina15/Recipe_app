CREATE TABLE user_profile (
  id          INT          NOT NULL AUTO_INCREMENT,
  user_id     INT          NOT NULL,
  profile_name  VARCHAR(100) NOT NULL,
  avoid       TEXT         NULL,
  cook_time   INT     NULL,
  budget      INT          NULL,
  updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_user_profile_user_id (user_id),
  CONSTRAINT fk_user_profile_user
  FOREIGN KEY (user_id) REFERENCES users(id)
  ON DELETE CASCADE
  ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
