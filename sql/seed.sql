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