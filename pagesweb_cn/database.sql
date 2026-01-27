-- database.sql
CREATE DATABASE IF NOT EXISTS inventeur_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE inventeur_app;

-- Admins
CREATE TABLE admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Maisons (entités indépendantes)
CREATE TABLE houses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  code VARCHAR(50) NOT NULL UNIQUE, -- code maison pour attacher vendeur
  type VARCHAR(100),
  address VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Agents / vendeurs
CREATE TABLE agents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(200) NOT NULL,
  phone VARCHAR(50),
  physical_address VARCHAR(255),
  house_code VARCHAR(50), -- lien via code
  vendor_number VARCHAR(100) UNIQUE NOT NULL, -- numéro vendeur utilisé pour login
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS agents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_id INT NOT NULL,
    fullname VARCHAR(150) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    address VARCHAR(255) NOT NULL,
    seller_code VARCHAR(50) NOT NULL UNIQUE,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Produits master (catalogue)
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  unit_price DECIMAL(12,2) NOT NULL,
  details TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  house_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  description TEXT,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE
);


-- Stock par maison (quantités stockées dans chaque maison)
CREATE TABLE house_stock (
  id INT AUTO_INCREMENT PRIMARY KEY,
  house_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT DEFAULT 0,
  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE (house_id, product_id),
  FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Mouvements de stock (entrées/sorties)
CREATE TABLE stock_movements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  house_id INT NOT NULL,
  product_id INT NOT NULL,
  change_qty INT NOT NULL, -- positif pour entrée, négatif pour sortie
  reason VARCHAR(255),
  created_by VARCHAR(150),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Ventes
CREATE TABLE sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  house_id INT NOT NULL,
  agent_id INT NOT NULL,
  total DECIMAL(12,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Détails des ventes
CREATE TABLE sale_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_id INT NOT NULL,
  product_id INT NOT NULL,
  qty INT NOT NULL,
  unit_price DECIMAL(12,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
);

-- Insert admin exemple (mot de passe : changez-le)
INSERT INTO admins (name, username, password_hash)
VALUES ('Test','admin', 
  -- mot de passe exemple 'Admin@123' hashé avec password_hash côté PHP (remplacer par hash généré)
  '$2y$10$XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
);

-- Exemple de produits (optionnel)
INSERT INTO products (name, unit_price, details) VALUES
('Paracétamol 500mg 20 tab', 2.50, 'Paracétamol générique'),
('Savon hygiène 100g', 1.20, 'Savon doux');

Remarque : remplace le hash admin par password_hash('tonMotDePasse', PASSWORD_DEFAULT) via un script PHP (ex. php -r "echo password_hash('Admin@123', PASSWORD_DEFAULT);").



CREATE TABLE IF NOT EXISTS house_delete_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  house_id INT NOT NULL,
  email VARCHAR(255) NOT NULL,
  code VARCHAR(10) NOT NULL,
  expires_at INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



///////// update tables existantes si besoin

-- products table
CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  house_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  description TEXT,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- house_stock: quantity per product per house (denormalized for quick access)
CREATE TABLE IF NOT EXISTS house_stock (
  id INT AUTO_INCREMENT PRIMARY KEY,
  house_id INT NOT NULL,
  product_id INT NOT NULL,
  qty INT NOT NULL DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY (house_id, product_id),
  FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- product_movements: history of stock movements
CREATE TABLE IF NOT EXISTS product_movements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  house_id INT NOT NULL,
  product_id INT NOT NULL,
  qty_change INT NOT NULL,
  type ENUM('in','out') NOT NULL,
  note VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_id INT NOT NULL,
    product_id INT NOT NULL,
    type ENUM('in','out') NOT NULL,
    qty INT NOT NULL,
    note VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY(product_id) REFERENCES products(id),
    FOREIGN KEY(house_id) REFERENCES houses(id)
);

******** Admin + Vendeur*****
CREATE TABLE sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  house_id INT NOT NULL,
  agent_id INT NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE,
  FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE
);

CREATE TABLE sale_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_id INT NOT NULL,
  product_id INT NOT NULL,
  qty INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

*********************************************************************************

-- sales + sale_items + product_movements (historique stock)
CREATE TABLE IF NOT EXISTS sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  house_id INT NOT NULL,
  agent_id INT NOT NULL,
  total_amount DECIMAL(12,2) NOT NULL,
  discount DECIMAL(12,2) DEFAULT 0,
  payment_method ENUM('cash','mobile','credit') DEFAULT 'cash',
  customer_name VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(house_id), INDEX(agent_id),
  FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE,
  FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sale_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_id INT NOT NULL,
  product_id INT NOT NULL,
  qty INT NOT NULL,
  price DECIMAL(12,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_movements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  house_id INT NOT NULL,
  product_id INT NOT NULL,
  type ENUM('in','out','sale') NOT NULL,
  qty INT NOT NULL,
  note VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;






CREATE TABLE seller_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    house_id INT NOT NULL,
    product_id INT NOT NULL,
    qty INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- pagesweb_cn/sql_create_seller_stock.sql
CREATE TABLE IF NOT EXISTS seller_stock (
  id INT AUTO_INCREMENT PRIMARY KEY,
  seller_id INT NOT NULL,
  house_id INT NOT NULL,
  product_id INT NOT NULL,
  qty INT NOT NULL DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_seller_prod (seller_id, house_id, product_id),
  INDEX idx_product (product_id),
  INDEX idx_house (house_id),
  INDEX idx_seller (seller_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


**** correction table sql existantes ****
ALTER TABLE products
ADD buy_price_cdf DECIMAL(12,2) NOT NULL AFTER name,
ADD sell_price_cdf DECIMAL(12,2) NOT NULL AFTER buy_price_cdf;


***
ALTER TABLE product_movements
ADD agent_id INT NULL AFTER product_id,
ADD INDEX idx_agent_id (agent_id);


***
ALTER TABLE product_movements
ADD COLUMN agent_id INT NULL,
ADD COLUMN unit_buy_price_cdf DECIMAL(12,2) NULL,
ADD COLUMN unit_sell_price_cdf DECIMAL(12,2) NULL;


******************************************************************************************************************
ALTER TABLE product_movements
ADD discount DECIMAL(10,2) DEFAULT 0,
ADD payment_method VARCHAR(20) DEFAULT 'cash',
ADD customer_name VARCHAR(100) DEFAULT NULL;
*****************************************************************************************************************
ALTER TABLE product_movements
ADD kit_id INT DEFAULT NULL,
ADD is_kit TINYINT(1) DEFAULT 0;
*****************************************************************************************************************






