-- Schema and seed data for the SNEM starter app.
--
--   mysql -u root -p < schema.sql
--
-- Table names carry the `st_` prefix configured as `db.tablePrefix`; SQL going
-- through $snem->db writes them as #__users / #__posts instead.

CREATE DATABASE IF NOT EXISTS snem_starter
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE snem_starter;

DROP TABLE IF EXISTS st_posts;
DROP TABLE IF EXISTS st_users;

CREATE TABLE st_users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username      VARCHAR(64)  NOT NULL,
    display_name  VARCHAR(128) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at    DATETIME     NULL,
    updated_at    DATETIME     NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE st_posts (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    author_id  INT UNSIGNED NOT NULL,
    title      VARCHAR(200) NOT NULL,
    body       TEXT         NULL,
    published  TINYINT(1)   NOT NULL DEFAULT 1,
    created_at DATETIME     NULL,
    updated_at DATETIME     NULL,
    PRIMARY KEY (id),
    KEY idx_posts_author (author_id),
    CONSTRAINT fk_posts_author FOREIGN KEY (author_id) REFERENCES st_users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Password for both accounts is: demo1234
INSERT INTO st_users (username, display_name, password_hash, created_at) VALUES
    ('demo', 'Demo Author', '$2y$10$TCBIN9ROnoE8FoXQKwveFOZDNcPK7Nx8thIAv9x73bmWKGxXpSUhS', NOW()),
    ('nina', 'Nina Tran',   '$2y$10$TCBIN9ROnoE8FoXQKwveFOZDNcPK7Nx8thIAv9x73bmWKGxXpSUhS', NOW());

INSERT INTO st_posts (author_id, title, body, published, created_at) VALUES
    (1, 'Hello from SNEM',
        'This post is served by DefaultController::actionDefault() in pages/posts/controllers/. The SQL that fetched it uses the #__ prefix placeholder, and the row you are reading was rendered by a partial included once per record.',
        1, NOW() - INTERVAL 2 DAY),
    (1, 'Routing is just three query parameters',
        'page picks the directory under pages/, task picks the controller class, and action picks the method. Anything missing falls back to a sensible default, which is why ?page=posts alone reaches this list.',
        1, NOW() - INTERVAL 1 DAY),
    (2, 'Models come in two flavours',
        'SEModel is a plain object with magic accessors. SEActiveRecordModel wraps ADOdb Active Record, so relations resolve lazily and save() fills in the timestamps for you.',
        1, NOW()),
    (2, 'A draft nobody should see',
        'published = 0, so the listing query filters this one out.',
        0, NOW());
