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