-- IT資産管理システム：データベース定義
DROP DATABASE IF EXISTS it_asset;
CREATE DATABASE it_asset CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE it_asset;

-- 社員
CREATE TABLE employees (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(50)  NOT NULL,
  department VARCHAR(50)  NOT NULL,
  email      VARCHAR(100) NOT NULL UNIQUE
);

-- IT資産
CREATE TABLE assets (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  asset_code     VARCHAR(20)  NOT NULL UNIQUE,
  category       ENUM('pc', 'monitor', 'peripheral', 'software') NOT NULL,
  name           VARCHAR(100) NOT NULL,
  status         ENUM('available', 'loaned', 'repair', 'disposed') NOT NULL DEFAULT 'available',
  purchase_date  DATE,
  license_expiry DATE NULL,
  note           TEXT,
  created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 貸出履歴
CREATE TABLE loans (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  asset_id    INT      NOT NULL,
  employee_id INT      NOT NULL,
  loaned_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  due_date    DATE     NOT NULL,
  returned_at DATETIME NULL,
  FOREIGN KEY (asset_id)    REFERENCES assets(id),
  FOREIGN KEY (employee_id) REFERENCES employees(id)
);
-- サンプルデータ
USE it_asset;

INSERT INTO employees (name, department, email) VALUES
('山田 太郎', '営業部', 'yamada@example.com'),
('佐藤 花子', '総務部', 'sato@example.com'),
('鈴木 一郎', '開発部', 'suzuki@example.com');

INSERT INTO assets (asset_code, category, name, status, purchase_date, license_expiry) VALUES
('PC-0001', 'pc',         'MacBook Air 13インチ',   'loaned',    '2025-04-01', NULL),
('PC-0002', 'pc',         'Dell Latitude 5440',     'available', '2025-04-01', NULL),
('MN-0001', 'monitor',    'EIZO 24インチモニター',  'available', '2024-10-15', NULL),
('PR-0001', 'peripheral', 'ワイヤレスマウス',       'repair',    '2024-06-01', NULL),
('SW-0001', 'software',   'Microsoft 365 Business', 'available', '2025-01-10', DATE_ADD(CURDATE(), INTERVAL 20 DAY)),
('SW-0002', 'software',   'Adobe Acrobat Pro',      'available', '2025-03-01', DATE_ADD(CURDATE(), INTERVAL 200 DAY));

-- 貸出中（まだ返却されていない）
INSERT INTO loans (asset_id, employee_id, loaned_at, due_date) VALUES
(1, 1, DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY));

-- 返却済みの履歴
INSERT INTO loans (asset_id, employee_id, loaned_at, due_date, returned_at) VALUES
(2, 2, '2026-08-01 10:00:00', '2026-08-31', '2026-08-28 17:00:00');