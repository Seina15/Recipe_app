CREATE TABLE recommend_recipe (
  category_id      VARCHAR(32)  NOT NULL,
  recipe_id        VARCHAR(32)  NOT NULL,
  title            VARCHAR(255) NOT NULL,
  recipe_url       VARCHAR(520) NOT NULL,
  image_url        VARCHAR(520) NULL,
  indication_min   INT          NULL,
  recipe_cost      TINYINT      NULL,
  fetched_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (category_id, recipe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
