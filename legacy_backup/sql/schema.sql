CREATE DATABASE IF NOT EXISTS tea_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tea_shop;

-- Users (admin / staff)
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  full_name VARCHAR(150),
  role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inventory
CREATE TABLE inventory (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  stock_qty INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
);

-- Orders
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  customer_name VARCHAR(150),
  total_amount DECIMAL(10,2) NOT NULL,
  status ENUM('pending','completed','cancelled') NOT NULL DEFAULT 'completed',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
ALTER TABLE orders 
ADD COLUMN order_type ENUM('Dine-in', 'Take-out') DEFAULT 'Dine-in' AFTER customer_name,
ADD COLUMN takeout_fee DECIMAL(10,2) DEFAULT 0 AFTER order_type,
ADD COLUMN amount_paid DECIMAL(10,2) DEFAULT 0 AFTER total_amount,
ADD COLUMN change_due DECIMAL(10,2) DEFAULT 0 AFTER amount_paid;


-- Order items
CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  inventory_id INT NOT NULL,
  qty INT NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  line_total DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  message TEXT NOT NULL,
  is_read BOOLEAN DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Optional: Default admin account (username: admin / password: admin123)
INSERT INTO users (username, password, full_name, role)
VALUES (
  'admin',
  '$2y$10$vSP0jgTYTN5OQUwiuZiMIeitObhl6SIdf5PPPheRDZOSpaGEojNZW', -- hash for 'admin123'
  'Administrator',
  'admin'
);

-- Sample inventory items
INSERT INTO inventory (name, description, price, stock_qty) VALUES
('ABRAHAM (Taro)Small', 'Creamy Milktea - Taro Small', 75.00, 30),
('ABRAHAM (Taro)Large', 'Creamy Milktea - Taro Large', 95.00, 25),
('ABBA (Brown sugar)Small', 'Creamy Milktea - Brown sugar Small', 75.00, 30),
('ABBA (Brown sugar)Large', 'Creamy Milktea - Brown sugar Large', 95.00, 25),
('ABEL (Okinawa)Small', 'Creamy Milktea - Okinawa Small', 75.00, 30),
('ABEL (Okinawa)Large', 'Creamy Milktea - Okinawa Large', 95.00, 25),
('AVA (Cookies N Cream)Small', 'Creamy Milktea - Cookies N Cream Small', 75.00, 30),
('AVA (Cookies N Cream)Large', 'Creamy Milktea - Cookies N Cream Large', 95.00, 25),
('ABBY (Dark Chocolate)Small', 'Creamy Milktea - Dark Chocolate Small', 75.00, 30),
('ABBY (Dark Chocolate)Large', 'Creamy Milktea - Dark Chocolate Large', 95.00, 25),
('SCARLET (Red Velvet)Small', 'Creamy Milktea - Red Velvet Small', 75.00, 30),
('SCARLET (Red Velvet)Large', 'Creamy Milktea - Red Velvet Large', 95.00, 25),

-- SALTY CHEESE
('JANE (Dark Chocolate)Small', 'Salty Cheese - Dark Chocolate Small', 95.00, 25),
('JANE (Dark Chocolate)Large', 'Salty Cheese - Dark Chocolate Large', 115.00, 20),
('YOLANDA (Wintermelon)Small', 'Salty Cheese - Wintermelon Small', 95.00, 25),
('YOLANDA (Wintermelon)Large', 'Salty Cheese - Wintermelon Large', 115.00, 20),
('SHENA (Okinawa)Small', 'Salty Cheese - Okinawa Small', 95.00, 25),
('SHENA (Okinawa)Large', 'Salty Cheese - Okinawa Large', 115.00, 20),
('JEA (Matcha)Small', 'Salty Cheese - Matcha Small', 95.00, 25),
('JEA (Matcha)Large', 'Salty Cheese - Matcha Large', 115.00, 20),
('JANET (Cookies N Cream)Small', 'Salty Cheese - Cookies N Cream Small', 95.00, 25),
('JANET (Cookies N Cream)Large', 'Salty Cheese - Cookies N Cream Large', 115.00, 20),
('JEAM (Brown sugar)Small', 'Salty Cheese - Brown sugar Small', 95.00, 25),
('JEAM (Brown sugar)Large', 'Salty Cheese - Brown sugar Large', 115.00, 20),

-- CHEESECAKE
('JARVIS (Oreo)Small', 'Cheesecake - Oreo Small', 110.00, 20),
('JARVIS (Oreo)Large', 'Cheesecake - Oreo Large', 130.00, 15),
('LOVELY (Red Velvet)Small', 'Cheesecake - Red Velvet Small', 110.00, 20),
('LOVELY (Red Velvet)Large', 'Cheesecake - Red Velvet Large', 130.00, 15),
('JYNHEL (Dark Chocolate)Small', 'Cheesecake - Dark Chocolate Small', 110.00, 20),
('JYNHEL (Dark Chocolate)Large', 'Cheesecake - Dark Chocolate Large', 130.00, 15),
('KIANNA (Matcha)Small', 'Cheesecake - Matcha Small', 110.00, 20),
('KIANNA (Matcha)Large', 'Cheesecake - Matcha Large', 130.00, 15),
('JOLLY (Okinawa)Small', 'Cheesecake - Okinawa Small', 110.00, 20),
('JOLLY (Okinawa)Large', 'Cheesecake - Okinawa Large', 130.00, 15),
('JESSA (Wintermelon)Small', 'Cheesecake - Wintermelon Small', 110.00, 20),
('JESSA (Wintermelon)Large', 'Cheesecake - Wintermelon Large', 130.00, 15),
('INDAY (Brown sugar)Small', 'Cheesecake - Brown sugar Small', 110.00, 20),
('INDAY (Brown sugar)Large', 'Cheesecake - Brown sugar Large', 130.00, 15),

-- FRAPPE
('Oreo Cheesecake Frap Small', 'Frappe - Oreo Cheesecake Small', 125.00, 20),
('Oreo Cheesecake Frap Large', 'Frappe - Oreo Cheesecake Large', 145.00, 18),
('Oreo Strawberry Frap Small', 'Frappe - Oreo Strawberry Small', 125.00, 20),
('Oreo Strawberry Frap Large', 'Frappe - Oreo Strawberry Large', 145.00, 18),
('Oreo Chocolate Chip Frap Small', 'Frappe - Oreo Chocolate Chip Small', 125.00, 20),
('Oreo Chocolate Chip Frap Large', 'Frappe - Oreo Chocolate Chip Large', 145.00, 18),
('Ube Cheesecake Frap Small', 'Frappe - Ube Cheesecake Small', 125.00, 20),
('Ube Cheesecake Frap Large', 'Frappe - Ube Cheesecake Large', 145.00, 18),
('Biscoff Cheesecake Frap Small', 'Frappe - Biscoff Cheesecake Small', 125.00, 20),
('Biscoff Cheesecake Frap Large', 'Frappe - Biscoff Cheesecake Large', 145.00, 18),

-- SODA FLOAT
('EDWIN Small', 'Soda Float - Mango Small', 75.00, 25),
('EDWIN Large', 'Soda Float - Mango Large', 95.00, 20),
('AMYTHS Small', 'Soda Float - Kiwi Small', 75.00, 25),
('AMYTHS Large', 'Soda Float - Kiwi Large', 95.00, 20),
('ANNA Small', 'Soda Float - Blueberry Small', 75.00, 25),
('ANNA Large', 'Soda Float - Blueberry Large', 95.00, 20),
('GRACE Small', 'Soda Float - Green Apple Small', 75.00, 25),
('GRACE Large', 'Soda Float - Green Apple Large', 95.00, 20),
('SOPHIA Small', 'Soda Float - Orange Small', 75.00, 25),
('SOPHIA Large', 'Soda Float - Orange Large', 95.00, 20),
('CHLOE Small', 'Soda Float - Strawberry Small', 75.00, 25),
('CHLOE Large', 'Soda Float - Strawberry Large', 95.00, 20),
('ELLA Small', 'Soda Float - Strawberry Toast Small', 75.00, 25),
('ELLA Large', 'Soda Float - Strawberry Toast Large', 95.00, 20),

-- FRUIT LATTE
('PAOLA Small', 'Fruit Latte - Kiwi Small', 65.00, 30),
('PAOLA Large', 'Fruit Latte - Kiwi Large', 85.00, 25),
('FE Small', 'Fruit Latte - Strawberry Small', 65.00, 30),
('FE Large', 'Fruit Latte - Strawberry Large', 85.00, 25),
('ROSE Small', 'Fruit Latte - Mango Small', 65.00, 30),
('ROSE Large', 'Fruit Latte - Mango Large', 85.00, 25),
('ANGEL Small', 'Fruit Latte - Blueberry Small', 65.00, 30),
('ANGEL Large', 'Fruit Latte - Blueberry Large', 85.00, 25),

-- FRUIT SMOOTHIE
('FRANCIS Small', 'Fruit Smoothie - Strawberry Small', 80.00, 25),
('FRANCIS Large', 'Fruit Smoothie - Strawberry Large', 100.00, 20),
('JOY Small', 'Fruit Smoothie - Blueberry Small', 80.00, 25),
('JOY Large', 'Fruit Smoothie - Blueberry Large', 100.00, 20),
('JAMES Small', 'Fruit Smoothie - Mango Small', 80.00, 25),
('JAMES Large', 'Fruit Smoothie - Mango Large', 100.00, 20),
('GLER Small', 'Fruit Smoothie - Kiwi Small', 80.00, 25),
('GLER Large', 'Fruit Smoothie - Kiwi Large', 100.00, 20),

-- FRUITSHAKE
('Mango Shake Small', 'Fruit Shake - Mango Small', 80.00, 20),
('Mango Shake Large', 'Fruit Shake - Mango Large', 100.00, 15),
('Banana Shake Small', 'Fruit Shake - Banana Small', 80.00, 20),
('Banana Shake Large', 'Fruit Shake - Banana Large', 100.00, 15),
('Avocado Shake Small', 'Fruit Shake - Avocado Small', 80.00, 20),
('Avocado Shake Large', 'Fruit Shake - Avocado Large', 100.00, 15),
('Watermelon Shake Small', 'Fruit Shake - Watermelon Small', 80.00, 20),
('Watermelon Shake Large', 'Fruit Shake - Watermelon Large', 100.00, 15),

-- HALO-HALO
('Ambisyosang Fruitsalad Small', 'Halo-Halo - Fruitsalad Small', 100.00, 15),
('Ambisyosang Fruitsalad Large', 'Halo-Halo - Fruitsalad Large', 120.00, 12),
('Ube De Leche Ka Small', 'Halo-Halo - Ube De Leche Small', 100.00, 15),
('Ube De Leche Ka Large', 'Halo-Halo - Ube De Leche Large', 120.00, 12),
('Chismosang Mango Graham Small', 'Halo-Halo - Mango Graham Small', 100.00, 15),
('Chismosang Mango Graham Large', 'Halo-Halo - Mango Graham Large', 120.00, 12),
('Pakialamang Mais Con Yelo Small', 'Halo-Halo - Mais Con Yelo Small', 120.00, 15),
('Pakialamang Mais Con Yelo Large', 'Halo-Halo - Mais Con Yelo Large', 140.00, 12),
('Halo-Halong Kalandian Small', 'Halo-Halo - Kalandian Small', 125.00, 15),
('Halo-Halong Kalandian Large', 'Halo-Halo - Kalandian Large', 145.00, 12),

-- SNACKS
('TEMPURA', 'Snacks - Tempura', 50.00, 30),
('SQUIDBALL', 'Snacks - Squidball', 60.00, 25),
('CHEESESTICKS', 'Snacks - Cheesesticks', 75.00, 25),
('VOLCANO BITES', 'Snacks - Volcano Bites', 75.00, 25),
('NACHOS', 'Snacks - Nachos', 75.00, 20),

-- FRENCH FRIES
('French Fries Small', 'French Fries - Small (Cheese, Truffle, Chili BBQ, Sour Cream)', 55.00, 30),
('French Fries Medium', 'French Fries - Medium (Cheese, Truffle, Chili BBQ, Sour Cream)', 95.00, 25),
('French Fries Large', 'French Fries - Large (Cheese, Truffle, Chili BBQ, Sour Cream)', 125.00, 20),

-- ADD ONS
('Pearls', 'Add-on - Pearls', 15.00, 50),
('Coffee Jelly', 'Add-on - Coffee Jelly', 15.00, 50),
('Rainbow Jelly', 'Add-on - Rainbow Jelly', 15.00, 50),
('Egg Pudding', 'Add-on - Egg Pudding', 20.00, 40),
('Salted Cream Cheese', 'Add-on - Salted Cream Cheese', 25.00, 35),
('Cheesecake', 'Add-on - Cheesecake', 35.00, 30),

-- RICE MEAL (CAPTAIN J - ALL DAY BREAKFAST)
('Chinese Ngohiong Meal', 'Rice Meal - Chinese Ngohiong', 79.00, 20),
('Pork Lumpia', 'Rice Meal - Pork Lumpia', 99.00, 20),
('Longganisa', 'Rice Meal - Longganisa', 99.00, 20),
('Luncheon Meat', 'Rice Meal - Luncheon Meat', 130.00, 15),
('Century Tuna', 'Rice Meal - Century Tuna', 99.00, 20),
('Sisig', 'Rice Meal - Sisig', 149.00, 15),
('Pork Chop', 'Rice Meal - Pork Chop', 149.00, 15),
('Fried Fish Labingaw', 'Rice Meal - Fried Fish Labingaw', 130.00, 15),
('Hungarian Sausage', 'Rice Meal - Hungarian Sausage', 130.00, 15),
('Premium Corned Beef', 'Rice Meal - Premium Corned Beef', 180.00, 10),
('Braised Pork Humba', 'Rice Meal - Braised Pork Humba', 150.00, 15),

-- EXTRAS
('Chinese Ngohiong Extra', 'Extra - Chinese Ngohiong', 22.00, 40),
('Egg Omelette', 'Extra - Egg Omelette', 40.00, 30),
('Softdrink', 'Extra - Softdrink', 15.00, 50),

-- HOT COFFEE
('Espresso Small', 'Hot Coffee - Espresso Small', 70.00, 30),
('Espresso Tall', 'Hot Coffee - Espresso Tall', 100.00, 25),
('Americano Small', 'Hot Coffee - Americano Small', 70.00, 30),
('Americano Tall', 'Hot Coffee - Americano Tall', 100.00, 25),
('Café Latte Small', 'Hot Coffee - Café Latte Small', 95.00, 30),
('Café Latte Tall', 'Hot Coffee - Café Latte Tall', 115.00, 25),
('Caramel Latte Small', 'Hot Coffee - Caramel Latte Small', 95.00, 25),
('Caramel Latte Tall', 'Hot Coffee - Caramel Latte Tall', 115.00, 20),
('Captain J Latte Small', 'Hot Coffee - Captain J Latte Small', 95.00, 25),
('Captain J Latte Tall', 'Hot Coffee - Captain J Latte Tall', 115.00, 20),
('Spanish Latte Small', 'Hot Coffee - Spanish Latte Small', 95.00, 25),
('Spanish Latte Tall', 'Hot Coffee - Spanish Latte Tall', 115.00, 20),
('Vanilla Latte Small', 'Hot Coffee - Vanilla Latte Small', 95.00, 25),
('Vanilla Latte Tall', 'Hot Coffee - Vanilla Latte Tall', 115.00, 20),
('Butterscotch Latte Small', 'Hot Coffee - Butterscotch Latte Small', 95.00, 20),
('Butterscotch Latte Tall', 'Hot Coffee - Butterscotch Latte Tall', 115.00, 20),
('Salted Caramel Latte Small', 'Hot Coffee - Salted Caramel Latte Small', 95.00, 20),
('Salted Caramel Latte Tall', 'Hot Coffee - Salted Caramel Latte Tall', 115.00, 20),
('Dirty Matcha Latte Small', 'Hot Coffee - Dirty Matcha Latte Small', 95.00, 20),
('Dirty Matcha Latte Tall', 'Hot Coffee - Dirty Matcha Latte Tall', 115.00, 20),
('Matcha Latte Small', 'Hot Coffee - Matcha Latte Small', 95.00, 20),
('Matcha Latte Tall', 'Hot Coffee - Matcha Latte Tall', 115.00, 20),
('Chocolate Small', 'Hot Coffee - Chocolate Small', 95.00, 25),
('Chocolate Tall', 'Hot Coffee - Chocolate Tall', 115.00, 20),

-- ICED COFFEE
('Americano Iced', 'Iced Coffee - Americano', 105.00, 25),
('Spanish Latte Iced', 'Iced Coffee - Spanish Latte', 105.00, 25),
('Captain J Latte Iced', 'Iced Coffee - Captain J Latte', 115.00, 20),
('Vanilla Latte Iced', 'Iced Coffee - Vanilla Latte', 115.00, 20),
('Butterscotch Latte Iced', 'Iced Coffee - Butterscotch Latte', 115.00, 20),
('Dirty Matcha Latte Iced', 'Iced Coffee - Dirty Matcha Latte', 115.00, 20),
('Matcha Latte Iced', 'Iced Coffee - Matcha Latte', 115.00, 20),
('Chocolate Iced', 'Iced Coffee - Chocolate', 115.00, 20),

-- NCCA COFFEE
('Matcha Latte NCCA', 'NCCA Coffee - Matcha Latte', 115.00, 20),
('Frappe NCCA', 'NCCA Coffee - Frappe', 150.00, 15),

-- TAKOYAKI
('Octo Cheese 3pcs', 'Takoyaki - Octo Cheese 3pcs', 59.00, 25),
('Octo Cheese 6pcs', 'Takoyaki - Octo Cheese 6pcs', 115.00, 20),
('Bacon 9pcs', 'Takoyaki - Bacon 9pcs', 110.00, 20),
('Veggies 12pcs', 'Takoyaki - Veggies 12pcs', 189.00, 15),

-- LEMONADE
('Classic Lemonade Medium', 'Lemonade - Classic Medium', 69.00, 30),
('Classic Lemonade Large', 'Lemonade - Classic Large', 89.00, 25),
('Cucumber Lemonade Medium', 'Lemonade - Cucumber Medium', 69.00, 30),
('Cucumber Lemonade Large', 'Lemonade - Cucumber Large', 89.00, 25),
('Watermelon Lemonade Medium', 'Lemonade - Watermelon Medium', 69.00, 30),
('Watermelon Lemonade Large', 'Lemonade - Watermelon Large', 89.00, 25),
('Yakult Lemonade Medium', 'Lemonade - Yakult Medium', 69.00, 30),
('Yakult Lemonade Large', 'Lemonade - Yakult Large', 89.00, 25),
('Green Apple Lemonade Medium', 'Lemonade - Green Apple Medium', 69.00, 30),
('Green Apple Lemonade Large', 'Lemonade - Green Apple Large', 89.00, 25),
('Blueberry Lemonade Medium', 'Lemonade - Blueberry Medium', 69.00, 30),
('Blueberry Lemonade Large', 'Lemonade - Blueberry Large', 89.00, 25),
('Peppermint Lemonade Medium', 'Lemonade - Peppermint Medium', 90.00, 20),
('Peppermint Lemonade Large', 'Lemonade - Peppermint Large', 110.00, 20),
('Honey Lemonade Medium', 'Lemonade - Honey Medium', 90.00, 20),
('Honey Lemonade Large', 'Lemonade - Honey Large', 110.00, 20),
('Yakult & Cucumber Medium', 'Lemonade - Yakult & Cucumber Medium', 90.00, 20),
('Yakult & Cucumber Large', 'Lemonade - Yakult & Cucumber Large', 110.00, 20),
('Yakult & Chia Seeds Medium', 'Lemonade - Yakult & Chia Seeds Medium', 90.00, 20),
('Yakult & Chia Seeds Large', 'Lemonade - Yakult & Chia Seeds Large', 110.00, 20);
