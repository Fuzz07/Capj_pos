<?php

namespace Database\Seeders;

use App\Models\Inventory;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // CREAMY MILKTEA
            ['name' => 'ABRAHAM (Taro)Small', 'description' => 'Creamy Milktea - Taro Small', 'price' => 75.00, 'stock_qty' => 30],
            ['name' => 'ABRAHAM (Taro)Large', 'description' => 'Creamy Milktea - Taro Large', 'price' => 95.00, 'stock_qty' => 25],
            ['name' => 'ABBA (Brown sugar)Small', 'description' => 'Creamy Milktea - Brown sugar Small', 'price' => 75.00, 'stock_qty' => 30],
            ['name' => 'ABBA (Brown sugar)Large', 'description' => 'Creamy Milktea - Brown sugar Large', 'price' => 95.00, 'stock_qty' => 25],
            ['name' => 'ABEL (Okinawa)Small', 'description' => 'Creamy Milktea - Okinawa Small', 'price' => 75.00, 'stock_qty' => 30],
            ['name' => 'ABEL (Okinawa)Large', 'description' => 'Creamy Milktea - Okinawa Large', 'price' => 95.00, 'stock_qty' => 25],
            ['name' => 'AVA (Cookies N Cream)Small', 'description' => 'Creamy Milktea - Cookies N Cream Small', 'price' => 75.00, 'stock_qty' => 30],
            ['name' => 'AVA (Cookies N Cream)Large', 'description' => 'Creamy Milktea - Cookies N Cream Large', 'price' => 95.00, 'stock_qty' => 25],
            ['name' => 'ABBY (Dark Chocolate)Small', 'description' => 'Creamy Milktea - Dark Chocolate Small', 'price' => 75.00, 'stock_qty' => 30],
            ['name' => 'ABBY (Dark Chocolate)Large', 'description' => 'Creamy Milktea - Dark Chocolate Large', 'price' => 95.00, 'stock_qty' => 25],
            ['name' => 'SCARLET (Red Velvet)Small', 'description' => 'Creamy Milktea - Red Velvet Small', 'price' => 75.00, 'stock_qty' => 30],
            ['name' => 'SCARLET (Red Velvet)Large', 'description' => 'Creamy Milktea - Red Velvet Large', 'price' => 95.00, 'stock_qty' => 25],

            // SALTY CHEESE
            ['name' => 'JANE (Dark Chocolate)Small', 'description' => 'Salty Cheese - Dark Chocolate Small', 'price' => 95.00, 'stock_qty' => 25],
            ['name' => 'JANE (Dark Chocolate)Large', 'description' => 'Salty Cheese - Dark Chocolate Large', 'price' => 115.00, 'stock_qty' => 20],
            ['name' => 'YOLANDA (Wintermelon)Small', 'description' => 'Salty Cheese - Wintermelon Small', 'price' => 95.00, 'stock_qty' => 25],
            ['name' => 'YOLANDA (Wintermelon)Large', 'description' => 'Salty Cheese - Wintermelon Large', 'price' => 115.00, 'stock_qty' => 20],
            ['name' => 'SHENA (Okinawa)Small', 'description' => 'Salty Cheese - Okinawa Small', 'price' => 95.00, 'stock_qty' => 25],
            ['name' => 'SHENA (Okinawa)Large', 'description' => 'Salty Cheese - Okinawa Large', 'price' => 115.00, 'stock_qty' => 20],
            ['name' => 'JEA (Matcha)Small', 'description' => 'Salty Cheese - Matcha Small', 'price' => 95.00, 'stock_qty' => 25],
            ['name' => 'JEA (Matcha)Large', 'description' => 'Salty Cheese - Matcha Large', 'price' => 115.00, 'stock_qty' => 20],
            ['name' => 'JANET (Cookies N Cream)Small', 'description' => 'Salty Cheese - Cookies N Cream Small', 'price' => 95.00, 'stock_qty' => 25],
            ['name' => 'JANET (Cookies N Cream)Large', 'description' => 'Salty Cheese - Cookies N Cream Large', 'price' => 115.00, 'stock_qty' => 20],
            ['name' => 'JEAM (Brown sugar)Small', 'description' => 'Salty Cheese - Brown sugar Small', 'price' => 95.00, 'stock_qty' => 25],
            ['name' => 'JEAM (Brown sugar)Large', 'description' => 'Salty Cheese - Brown sugar Large', 'price' => 115.00, 'stock_qty' => 20],

            // CHEESECAKE
            ['name' => 'JARVIS (Oreo)Small', 'description' => 'Cheesecake - Oreo Small', 'price' => 110.00, 'stock_qty' => 20],
            ['name' => 'JARVIS (Oreo)Large', 'description' => 'Cheesecake - Oreo Large', 'price' => 130.00, 'stock_qty' => 15],
            ['name' => 'LOVELY (Red Velvet)Small', 'description' => 'Cheesecake - Red Velvet Small', 'price' => 110.00, 'stock_qty' => 20],
            ['name' => 'LOVELY (Red Velvet)Large', 'description' => 'Cheesecake - Red Velvet Large', 'price' => 130.00, 'stock_qty' => 15],
            ['name' => 'JYNHEL (Dark Chocolate)Small', 'description' => 'Cheesecake - Dark Chocolate Small', 'price' => 110.00, 'stock_qty' => 20],
            ['name' => 'JYNHEL (Dark Chocolate)Large', 'description' => 'Cheesecake - Dark Chocolate Large', 'price' => 130.00, 'stock_qty' => 15],
            ['name' => 'KIANNA (Matcha)Small', 'description' => 'Cheesecake - Matcha Small', 'price' => 110.00, 'stock_qty' => 20],
            ['name' => 'KIANNA (Matcha)Large', 'description' => 'Cheesecake - Matcha Large', 'price' => 130.00, 'stock_qty' => 15],
            ['name' => 'JOLLY (Okinawa)Small', 'description' => 'Cheesecake - Okinawa Small', 'price' => 110.00, 'stock_qty' => 20],
            ['name' => 'JOLLY (Okinawa)Large', 'description' => 'Cheesecake - Okinawa Large', 'price' => 130.00, 'stock_qty' => 15],
            ['name' => 'JESSA (Wintermelon)Small', 'description' => 'Cheesecake - Wintermelon Small', 'price' => 110.00, 'stock_qty' => 20],
            ['name' => 'JESSA (Wintermelon)Large', 'description' => 'Cheesecake - Wintermelon Large', 'price' => 130.00, 'stock_qty' => 15],
            ['name' => 'INDAY (Brown sugar)Small', 'description' => 'Cheesecake - Brown sugar Small', 'price' => 110.00, 'stock_qty' => 20],
            ['name' => 'INDAY (Brown sugar)Large', 'description' => 'Cheesecake - Brown sugar Large', 'price' => 130.00, 'stock_qty' => 15],

            // FRAPPE
            ['name' => 'Oreo Cheesecake Frap Small', 'description' => 'Frappe - Oreo Cheesecake Small', 'price' => 125.00, 'stock_qty' => 20],
            ['name' => 'Oreo Cheesecake Frap Large', 'description' => 'Frappe - Oreo Cheesecake Large', 'price' => 145.00, 'stock_qty' => 18],
            ['name' => 'Oreo Strawberry Frap Small', 'description' => 'Frappe - Oreo Strawberry Small', 'price' => 125.00, 'stock_qty' => 20],
            ['name' => 'Oreo Strawberry Frap Large', 'description' => 'Frappe - Oreo Strawberry Large', 'price' => 145.00, 'stock_qty' => 18],
            ['name' => 'Oreo Chocolate Chip Frap Small', 'description' => 'Frappe - Oreo Chocolate Chip Small', 'price' => 125.00, 'stock_qty' => 20],
            ['name' => 'Oreo Chocolate Chip Frap Large', 'description' => 'Frappe - Oreo Chocolate Chip Large', 'price' => 145.00, 'stock_qty' => 18],
            ['name' => 'Ube Cheesecake Frap Small', 'description' => 'Frappe - Ube Cheesecake Small', 'price' => 125.00, 'stock_qty' => 20],
            ['name' => 'Ube Cheesecake Frap Large', 'description' => 'Frappe - Ube Cheesecake Large', 'price' => 145.00, 'stock_qty' => 18],
            ['name' => 'Biscoff Cheesecake Frap Small', 'description' => 'Frappe - Biscoff Cheesecake Small', 'price' => 125.00, 'stock_qty' => 20],
            ['name' => 'Biscoff Cheesecake Frap Large', 'description' => 'Frappe - Biscoff Cheesecake Large', 'price' => 145.00, 'stock_qty' => 18],

            // SODA FLOAT
            ['name' => 'EDWIN Small', 'description' => 'Soda Float - Mango Small', 'price' => 75.00, 'stock_qty' => 25],
            ['name' => 'EDWIN Large', 'description' => 'Soda Float - Mango Large', 'price' => 95.00, 'stock_qty' => 20],
            ['name' => 'AMYTHS Small', 'description' => 'Soda Float - Kiwi Small', 'price' => 75.00, 'stock_qty' => 25],
            ['name' => 'AMYTHS Large', 'description' => 'Soda Float - Kiwi Large', 'price' => 95.00, 'stock_qty' => 20],
            ['name' => 'ANNA Small', 'description' => 'Soda Float - Blueberry Small', 'price' => 75.00, 'stock_qty' => 25],
            ['name' => 'ANNA Large', 'description' => 'Soda Float - Blueberry Large', 'price' => 95.00, 'stock_qty' => 20],
            ['name' => 'GRACE Small', 'description' => 'Soda Float - Green Apple Small', 'price' => 75.00, 'stock_qty' => 25],
            ['name' => 'GRACE Large', 'description' => 'Soda Float - Green Apple Large', 'price' => 95.00, 'stock_qty' => 20],
            ['name' => 'SOPHIA Small', 'description' => 'Soda Float - Orange Small', 'price' => 75.00, 'stock_qty' => 25],
            ['name' => 'SOPHIA Large', 'description' => 'Soda Float - Orange Large', 'price' => 95.00, 'stock_qty' => 20],
            ['name' => 'CHLOE Small', 'description' => 'Soda Float - Strawberry Small', 'price' => 75.00, 'stock_qty' => 25],
            ['name' => 'CHLOE Large', 'description' => 'Soda Float - Strawberry Large', 'price' => 95.00, 'stock_qty' => 20],
            ['name' => 'ELLA Small', 'description' => 'Soda Float - Strawberry Toast Small', 'price' => 75.00, 'stock_qty' => 25],
            ['name' => 'ELLA Large', 'description' => 'Soda Float - Strawberry Toast Large', 'price' => 95.00, 'stock_qty' => 20],

            // FRUIT LATTE
            ['name' => 'PAOLA Small', 'description' => 'Fruit Latte - Kiwi Small', 'price' => 65.00, 'stock_qty' => 30],
            ['name' => 'PAOLA Large', 'description' => 'Fruit Latte - Kiwi Large', 'price' => 85.00, 'stock_qty' => 25],
            ['name' => 'FE Small', 'description' => 'Fruit Latte - Strawberry Small', 'price' => 65.00, 'stock_qty' => 30],
            ['name' => 'FE Large', 'description' => 'Fruit Latte - Strawberry Large', 'price' => 85.00, 'stock_qty' => 25],
            ['name' => 'ROSE Small', 'description' => 'Fruit Latte - Mango Small', 'price' => 65.00, 'stock_qty' => 30],
            ['name' => 'ROSE Large', 'description' => 'Fruit Latte - Mango Large', 'price' => 85.00, 'stock_qty' => 25],
            ['name' => 'ANGEL Small', 'description' => 'Fruit Latte - Blueberry Small', 'price' => 65.00, 'stock_qty' => 30],
            ['name' => 'ANGEL Large', 'description' => 'Fruit Latte - Blueberry Large', 'price' => 85.00, 'stock_qty' => 25],

            // FRUIT SMOOTHIE
            ['name' => 'FRANCIS Small', 'description' => 'Fruit Smoothie - Strawberry Small', 'price' => 80.00, 'stock_qty' => 25],
            ['name' => 'FRANCIS Large', 'description' => 'Fruit Smoothie - Strawberry Large', 'price' => 100.00, 'stock_qty' => 20],
            ['name' => 'JOY Small', 'description' => 'Fruit Smoothie - Blueberry Small', 'price' => 80.00, 'stock_qty' => 25],
            ['name' => 'JOY Large', 'description' => 'Fruit Smoothie - Blueberry Large', 'price' => 100.00, 'stock_qty' => 20],
            ['name' => 'JAMES Small', 'description' => 'Fruit Smoothie - Mango Small', 'price' => 80.00, 'stock_qty' => 25],
            ['name' => 'JAMES Large', 'description' => 'Fruit Smoothie - Mango Large', 'price' => 100.00, 'stock_qty' => 20],
            ['name' => 'GLER Small', 'description' => 'Fruit Smoothie - Kiwi Small', 'price' => 80.00, 'stock_qty' => 25],
            ['name' => 'GLER Large', 'description' => 'Fruit Smoothie - Kiwi Large', 'price' => 100.00, 'stock_qty' => 20],

            // FRUITSHAKE
            ['name' => 'Mango Shake Small', 'description' => 'Fruit Shake - Mango Small', 'price' => 80.00, 'stock_qty' => 20],
            ['name' => 'Mango Shake Large', 'description' => 'Fruit Shake - Mango Large', 'price' => 100.00, 'stock_qty' => 15],
            ['name' => 'Banana Shake Small', 'description' => 'Fruit Shake - Banana Small', 'price' => 80.00, 'stock_qty' => 20],
            ['name' => 'Banana Shake Large', 'description' => 'Fruit Shake - Banana Large', 'price' => 100.00, 'stock_qty' => 15],
            ['name' => 'Avocado Shake Small', 'description' => 'Fruit Shake - Avocado Small', 'price' => 80.00, 'stock_qty' => 20],
            ['name' => 'Avocado Shake Large', 'description' => 'Fruit Shake - Avocado Large', 'price' => 100.00, 'stock_qty' => 15],
            ['name' => 'Watermelon Shake Small', 'description' => 'Fruit Shake - Watermelon Small', 'price' => 80.00, 'stock_qty' => 20],
            ['name' => 'Watermelon Shake Large', 'description' => 'Fruit Shake - Watermelon Large', 'price' => 100.00, 'stock_qty' => 15],

            // HALO-HALO
            ['name' => 'Ambisyosang Fruitsalad Small', 'description' => 'Halo-Halo - Fruitsalad Small', 'price' => 100.00, 'stock_qty' => 15],
            ['name' => 'Ambisyosang Fruitsalad Large', 'description' => 'Halo-Halo - Fruitsalad Large', 'price' => 120.00, 'stock_qty' => 12],
            ['name' => 'Ube De Leche Ka Small', 'description' => 'Halo-Halo - Ube De Leche Small', 'price' => 100.00, 'stock_qty' => 15],
            ['name' => 'Ube De Leche Ka Large', 'description' => 'Halo-Halo - Ube De Leche Large', 'price' => 120.00, 'stock_qty' => 12],
            ['name' => 'Chismosang Mango Graham Small', 'description' => 'Halo-Halo - Mango Graham Small', 'price' => 100.00, 'stock_qty' => 15],
            ['name' => 'Chismosang Mango Graham Large', 'description' => 'Halo-Halo - Mango Graham Large', 'price' => 120.00, 'stock_qty' => 12],
            ['name' => 'Pakialamang Mais Con Yelo Small', 'description' => 'Halo-Halo - Mais Con Yelo Small', 'price' => 120.00, 'stock_qty' => 15],
            ['name' => 'Pakialamang Mais Con Yelo Large', 'description' => 'Halo-Halo - Mais Con Yelo Large', 'price' => 140.00, 'stock_qty' => 12],
            ['name' => 'Halo-Halong Kalandian Small', 'description' => 'Halo-Halo - Kalandian Small', 'price' => 125.00, 'stock_qty' => 15],
            ['name' => 'Halo-Halong Kalandian Large', 'description' => 'Halo-Halo - Kalandian Large', 'price' => 145.00, 'stock_qty' => 12],

            // SNACKS
            ['name' => 'TEMPURA', 'description' => 'Snacks - Tempura', 'price' => 50.00, 'stock_qty' => 30],
            ['name' => 'SQUIDBALL', 'description' => 'Snacks - Squidball', 'price' => 60.00, 'stock_qty' => 25],
            ['name' => 'CHEESESTICKS', 'description' => 'Snacks - Cheesesticks', 'price' => 75.00, 'stock_qty' => 25],
            ['name' => 'VOLCANO BITES', 'description' => 'Snacks - Volcano Bites', 'price' => 75.00, 'stock_qty' => 25],
            ['name' => 'NACHOS', 'description' => 'Snacks - Nachos', 'price' => 75.00, 'stock_qty' => 20],

            // FRENCH FRIES
            ['name' => 'French Fries Small', 'description' => 'French Fries - Small (Cheese, Truffle, Chili BBQ, Sour Cream)', 'price' => 55.00, 'stock_qty' => 30],
            ['name' => 'French Fries Medium', 'description' => 'French Fries - Medium (Cheese, Truffle, Chili BBQ, Sour Cream)', 'price' => 95.00, 'stock_qty' => 25],
            ['name' => 'French Fries Large', 'description' => 'French Fries - Large (Cheese, Truffle, Chili BBQ, Sour Cream)', 'price' => 125.00, 'stock_qty' => 20],

            // ADD ONS
            ['name' => 'Pearls', 'description' => 'Add-on - Pearls', 'price' => 15.00, 'stock_qty' => 50],
            ['name' => 'Coffee Jelly', 'description' => 'Add-on - Coffee Jelly', 'price' => 15.00, 'stock_qty' => 50],
            ['name' => 'Rainbow Jelly', 'description' => 'Add-on - Rainbow Jelly', 'price' => 15.00, 'stock_qty' => 50],
            ['name' => 'Egg Pudding', 'description' => 'Add-on - Egg Pudding', 'price' => 20.00, 'stock_qty' => 40],
            ['name' => 'Salted Cream Cheese', 'description' => 'Add-on - Salted Cream Cheese', 'price' => 25.00, 'stock_qty' => 35],
            ['name' => 'Cheesecake', 'description' => 'Add-on - Cheesecake', 'price' => 35.00, 'stock_qty' => 30],

            // RICE MEAL
            ['name' => 'Chinese Ngohiong Meal', 'description' => 'Rice Meal - Chinese Ngohiong', 'price' => 79.00, 'stock_qty' => 20],
            ['name' => 'Pork Lumpia', 'description' => 'Rice Meal - Pork Lumpia', 'price' => 99.00, 'stock_qty' => 20],
            ['name' => 'Longganisa', 'description' => 'Rice Meal - Longganisa', 'price' => 99.00, 'stock_qty' => 20],
            ['name' => 'Luncheon Meat', 'description' => 'Rice Meal - Luncheon Meat', 'price' => 130.00, 'stock_qty' => 15],
            ['name' => 'Century Tuna', 'description' => 'Rice Meal - Century Tuna', 'price' => 99.00, 'stock_qty' => 20],
            ['name' => 'Sisig', 'description' => 'Rice Meal - Sisig', 'price' => 149.00, 'stock_qty' => 15],
            ['name' => 'Pork Chop', 'description' => 'Rice Meal - Pork Chop', 'price' => 149.00, 'stock_qty' => 15],
            ['name' => 'Fried Fish Labingaw', 'description' => 'Rice Meal - Fried Fish Labingaw', 'price' => 130.00, 'stock_qty' => 15],
            ['name' => 'Hungarian Sausage', 'description' => 'Rice Meal - Hungarian Sausage', 'price' => 130.00, 'stock_qty' => 15],
            ['name' => 'Premium Corned Beef', 'description' => 'Rice Meal - Premium Corned Beef', 'price' => 180.00, 'stock_qty' => 10],
            ['name' => 'Braised Pork Humba', 'description' => 'Rice Meal - Braised Pork Humba', 'price' => 150.00, 'stock_qty' => 15],

            // EXTRAS
            ['name' => 'Chinese Ngohiong Extra', 'description' => 'Extra - Chinese Ngohiong', 'price' => 22.00, 'stock_qty' => 40],
            ['name' => 'Egg Omelette', 'description' => 'Extra - Egg Omelette', 'price' => 40.00, 'stock_qty' => 30],
            ['name' => 'Softdrink', 'description' => 'Extra - Softdrink', 'price' => 15.00, 'stock_qty' => 50],

            // HOT COFFEE
            ['name' => 'Espresso Small', 'description' => 'Hot Coffee - Espresso Small', 'price' => 70.00, 'stock_qty' => 30],
            ['name' => 'Espresso Tall', 'description' => 'Hot Coffee - Espresso Tall', 'price' => 100.00, 'stock_qty' => 25],
            ['name' => 'Americano Small', 'description' => 'Hot Coffee - Americano Small', 'price' => 70.00, 'stock_qty' => 30],
            ['name' => 'Americano Tall', 'description' => 'Hot Coffee - Americano Tall', 'price' => 100.00, 'stock_qty' => 25],
            ['name' => 'Café Latte Small', 'description' => 'Hot Coffee - Café Latte Small', 'price' => 95.00, 'stock_qty' => 30],
            ['name' => 'Café Latte Tall', 'description' => 'Hot Coffee - Café Latte Tall', 'price' => 115.00, 'stock_qty' => 25],
            ['name' => 'Caramel Latte Small', 'description' => 'Hot Coffee - Caramel Latte Small', 'price' => 95.00, 'stock_qty' => 25],
            ['name' => 'Caramel Latte Tall', 'description' => 'Hot Coffee - Caramel Latte Tall', 'price' => 115.00, 'stock_qty' => 20],
            ['name' => 'Captain J Latte Small', 'description' => 'Hot Coffee - Captain J Latte Small', 'price' => 95.00, 'stock_qty' => 25],
            ['name' => 'Captain J Latte Tall', 'description' => 'Hot Coffee - Captain J Latte Tall', 'price' => 115.00, 'stock_qty' => 20],
            ['name' => 'Spanish Latte Small', 'description' => 'Hot Coffee - Spanish Latte Small', 'price' => 95.00, 'stock_qty' => 25],
            ['name' => 'Spanish Latte Tall', 'description' => 'Hot Coffee - Spanish Latte Tall', 'price' => 115.00, 'stock_qty' => 20],

            // ICED COFFEE
            ['name' => 'Americano Iced', 'description' => 'Iced Coffee - Americano', 'price' => 105.00, 'stock_qty' => 25],
            ['name' => 'Spanish Latte Iced', 'description' => 'Iced Coffee - Spanish Latte', 'price' => 105.00, 'stock_qty' => 25],
            ['name' => 'Captain J Latte Iced', 'description' => 'Iced Coffee - Captain J Latte', 'price' => 115.00, 'stock_qty' => 20],
            ['name' => 'Vanilla Latte Iced', 'description' => 'Iced Coffee - Vanilla Latte', 'price' => 115.00, 'stock_qty' => 20],
            ['name' => 'Matcha Latte Iced', 'description' => 'Iced Coffee - Matcha Latte', 'price' => 115.00, 'stock_qty' => 20],

            // TAKOYAKI
            ['name' => 'Octo Cheese 3pcs', 'description' => 'Takoyaki - Octo Cheese 3pcs', 'price' => 59.00, 'stock_qty' => 25],
            ['name' => 'Octo Cheese 6pcs', 'description' => 'Takoyaki - Octo Cheese 6pcs', 'price' => 115.00, 'stock_qty' => 20],
            ['name' => 'Bacon 9pcs', 'description' => 'Takoyaki - Bacon 9pcs', 'price' => 110.00, 'stock_qty' => 20],
            ['name' => 'Veggies 12pcs', 'description' => 'Takoyaki - Veggies 12pcs', 'price' => 189.00, 'stock_qty' => 15],

            // LEMONADE
            ['name' => 'Classic Lemonade Medium', 'description' => 'Lemonade - Classic Medium', 'price' => 69.00, 'stock_qty' => 30],
            ['name' => 'Classic Lemonade Large', 'description' => 'Lemonade - Classic Large', 'price' => 89.00, 'stock_qty' => 25],
            ['name' => 'Cucumber Lemonade Medium', 'description' => 'Lemonade - Cucumber Medium', 'price' => 69.00, 'stock_qty' => 30],
            ['name' => 'Cucumber Lemonade Large', 'description' => 'Lemonade - Cucumber Large', 'price' => 89.00, 'stock_qty' => 25],
            ['name' => 'Watermelon Lemonade Medium', 'description' => 'Lemonade - Watermelon Medium', 'price' => 69.00, 'stock_qty' => 30],
            ['name' => 'Watermelon Lemonade Large', 'description' => 'Lemonade - Watermelon Large', 'price' => 89.00, 'stock_qty' => 25],
        ];

        foreach ($items as $item) {
            Inventory::updateOrCreate(['name' => $item['name']], $item);
        }
    }
}
