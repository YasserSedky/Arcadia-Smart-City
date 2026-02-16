USE arcadia_smart_city;

-- Bank accounts and transactions
CREATE TABLE IF NOT EXISTS bank_accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  owner_user_id INT DEFAULT NULL,
  account_no VARCHAR(30) UNIQUE NOT NULL,
  account_name VARCHAR(200) NOT NULL,
  type ENUM('resident','business','city') NOT NULL DEFAULT 'resident',
  balance DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (owner_user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS bank_transactions (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  account_id INT NOT NULL,
  ts DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  kind ENUM('deposit','withdraw','transfer_in','transfer_out','charge','payment') NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  description VARCHAR(300) DEFAULT NULL,
  FOREIGN KEY (account_id) REFERENCES bank_accounts(id)
);



