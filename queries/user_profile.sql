CREATE TABLE user_profile (
  id          INT          NOT NULL AUTO_INCREMENT,
  user_id     INT          NOT NULL,
  avoid       TEXT         NULL,
  time        INT          NULL,
  budget      INT          NULL,
  servings    INT          NULL,
  updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
