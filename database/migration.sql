-- ============================================================
-- Inventár KTPE — kompletná štruktúra databázy
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS loans;
DROP TABLE IF EXISTS items;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS triedenie_settings;
DROP TABLE IF EXISTS app_settings;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- Používatelia
-- ------------------------------------------------------------
CREATE TABLE users (
                       id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                       name         VARCHAR(100) NOT NULL,
                       email        VARCHAR(150) NOT NULL UNIQUE,
                       password     VARCHAR(255) NOT NULL,
                       role         ENUM('user','admin') NOT NULL DEFAULT 'user',
                       active       TINYINT(1)   NOT NULL DEFAULT 1,
                       abbreviation VARCHAR(10)  DEFAULT NULL,
                       created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Miestnosti
-- ------------------------------------------------------------
CREATE TABLE rooms (
                       id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                       code     VARCHAR(20)  DEFAULT NULL,
                       new_code VARCHAR(20)  DEFAULT NULL,
                       name     VARCHAR(100) NOT NULL
);

-- ------------------------------------------------------------
-- Zariadenia
-- ------------------------------------------------------------
CREATE TABLE items (
                       id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                       sap_num          VARCHAR(50)  NOT NULL,
                       category_symbol  VARCHAR(10)  DEFAULT NULL,
                       category_num1    VARCHAR(50)  DEFAULT NULL,
                       category_num2    VARCHAR(50)  DEFAULT NULL,
                       category_num3    VARCHAR(50)  DEFAULT NULL,
                       write_year       VARCHAR(50)  DEFAULT NULL,
                       name             VARCHAR(255) NOT NULL,
                       triedenie        VARCHAR(100) DEFAULT NULL,
                       personal         VARCHAR(150) DEFAULT NULL,
                       sap_position     VARCHAR(100) DEFAULT NULL,
                       new_sap_position VARCHAR(100) DEFAULT NULL,
                       closet           VARCHAR(50)  DEFAULT NULL,
                       shelf            VARCHAR(50)  DEFAULT NULL,
                       temp_room        VARCHAR(50)  DEFAULT NULL,
                       temp_closet      VARCHAR(50)  DEFAULT NULL,
                       temp_shelf       VARCHAR(50)  DEFAULT NULL,
                       note             TEXT         DEFAULT NULL,
                       status           ENUM('available','borrowed','maintenance') NOT NULL DEFAULT 'available',
                       vyucba_label     VARCHAR(50)  DEFAULT NULL,
                       description      TEXT         DEFAULT NULL,
                       archived         TINYINT(1)  NOT NULL DEFAULT 0,
                       created_at       TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                       updated_at       TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Výpožičky
-- ------------------------------------------------------------
CREATE TABLE loans (
                       id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                       item_id     INT UNSIGNED NOT NULL,
                       user_id     INT UNSIGNED NOT NULL,
                       borrowed_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                       due_date    DATE         DEFAULT NULL,
                       returned_at TIMESTAMP    DEFAULT NULL,
                       notes       TEXT         DEFAULT NULL,
                       FOREIGN KEY (item_id) REFERENCES items(id),
                       FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ------------------------------------------------------------
-- Komentáre
-- ------------------------------------------------------------
CREATE TABLE comments (
                          id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                          item_id    INT UNSIGNED NOT NULL,
                          user_id    INT UNSIGNED NOT NULL,
                          comment    TEXT         NOT NULL,
                          created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                          FOREIGN KEY (item_id) REFERENCES items(id),
                          FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ------------------------------------------------------------
-- Nastavenia viditeľnosti triedení
-- ------------------------------------------------------------
CREATE TABLE triedenie_settings (
                                    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                    triedenie VARCHAR(100) NOT NULL UNIQUE,
                                    visible   TINYINT(1)  NOT NULL DEFAULT 1
);

-- ------------------------------------------------------------
-- Globálne nastavenia aplikácie
-- ------------------------------------------------------------
CREATE TABLE app_settings (
    `key`   VARCHAR(50)  NOT NULL PRIMARY KEY,
    `value` VARCHAR(255) DEFAULT NULL
);

-- ============================================================
-- TESTOVACIE DÁTA
-- ============================================================

-- Používatelia (heslo: 1234)
INSERT INTO users (name, email, password, role, active, abbreviation) VALUES
                                                                          ('Admin',      'admin@ktpe.sk',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, 'Ad'),
                                                                          ('Ján Kováč',  'kovac@ktpe.sk',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',  1, 'Kv'),
                                                                          ('Mária Novák','novak@ktpe.sk',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',  1, 'Mn'),
                                                                          ('Peter Horný', 'horny@ktpe.sk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',  0, 'Ph');

-- Miestnosti
INSERT INTO rooms (code, new_code, name) VALUES
                                             ('4',   '001/A', 'L2'),
                                             ('7',   '003',   'L1'),
                                             ('12',  '005',   'L3'),
                                             ('12A', '005/A', 'L4'),
                                             ('19',  '105',   'L7'),
                                             ('25',  '205',   'Kováč'),
                                             ('9',   '004',   'garáž');

-- Globálne nastavenia
INSERT INTO app_settings (`key`, `value`) VALUES ('show_no_triedenie', '1');

-- Zariadenia
INSERT INTO items (sap_num, category_symbol, category_num1, category_num2, write_year, name, triedenie, sap_position, closet, shelf, status) VALUES
                                                                                                                                                 ('90001001', 'D-', '1001', '90', '2015', 'Osciloskop Rigol DS1054Z',     'A', '12',  '1A', '3',  'available'),
                                                                                                                                                 ('90001002', 'D-', '1002', '90', '2016', 'Multimeter Fluke 87V',          'A', '12',  '1A', '4',  'available'),
                                                                                                                                                 ('90001003', 'K-', '2001', '91', '2018', 'Napájací zdroj Korad KA3005D',  'B', '4',   '2B', '1',  'available'),
                                                                                                                                                 ('90001004', 'K-', '2002', '91', '2018', 'Napájací zdroj Korad KA3005D',  'B', '4',   '2B', '2',  'borrowed'),
                                                                                                                                                 ('90001005', 'F-', '3001', '92', '2020', 'Funkčný generátor Rigol DG1022','A', '12A', '3C', '5',  'maintenance'),
                                                                                                                                                 ('90001006', 'D-', '1003', '90', '2019', 'Osciloskop Tektronix TBS1052B', 'A', '19',  '1D', '2',  'available'),
                                                                                                                                                 ('90001007', 'K-', '2003', '91', '2017', 'Laboratórny zdroj GW Instek',   'B', '7',   '2A', '3',  'available'),
                                                                                                                                                 ('90001008', 'L-', '4001', '93', '2021', 'LCR meter Hioki IM3523',        'C', '25',  '4E', '1',  'available'),
                                                                                                                                                 ('90001009', 'D-', '1004', '90', '2015', 'Spektrálny analyzátor',         'A', '12',  '1A', '5',  'available'),
                                                                                                                                                 ('90001010', 'K-', '2004', '91', '2022', '3D tlačiareň Prusa MK3S+',      NULL,'9',   NULL, NULL, 'available');

-- Pridelené na osobnú kartu
UPDATE items SET personal = 'Kv' WHERE sap_num IN ('90001001', '90001002');

-- Poznámky z Excelu
UPDATE items SET note = 'Skontrolovať kalibráciu' WHERE sap_num = '90001003';
UPDATE items SET note = 'Chýba kábel BNC'         WHERE sap_num = '90001006';

-- Dočasné premiestnenie
UPDATE items SET temp_room = '005', temp_closet = '2A', temp_shelf = '1' WHERE sap_num = '90001007';

-- Výučba
UPDATE items SET status = 'maintenance', vyucba_label = 'Mn' WHERE sap_num = '90001005';

-- Výpožičky
INSERT INTO loans (item_id, user_id, borrowed_at, notes) VALUES
                                                             (4, 2, '2026-05-01 10:00:00', 'Na projekt č.3'),
                                                             (4, 3, '2026-05-10 09:30:00', NULL);
UPDATE loans SET returned_at = '2026-05-08 14:00:00' WHERE id = 1;

-- Komentáre
INSERT INTO comments (item_id, user_id, comment, created_at) VALUES
                                                                 (1, 2, 'Sonda č.2 je poškodená, treba objednať náhradu.',   '2026-05-02 11:00:00'),
                                                                 (3, 3, 'Zdroj funguje dobre, odporúčam na dlhodobé merania.','2026-05-05 14:30:00'),
                                                                 (4, 2, 'Vrátim do piatku.',                                  '2026-05-11 08:00:00');

-- Triedenie
INSERT INTO triedenie_settings (triedenie, visible) VALUES
                                                        ('A', 1),
                                                        ('B', 1),
                                                        ('C', 1);