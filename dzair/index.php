<?php
declare(strict_types=1);

$secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

date_default_timezone_set('Africa/Algiers');

final class Config
{
    public const APP_NAME = 'DzairEats';
    public const DB_HOST = '127.0.0.1';
    public const DB_PORT = '3306';
    public const DB_NAME = 'dzaireats_marketplace';
    public const DB_USER = 'root';
    public const DB_PASS = '';

    public static function db(): array
    {
        $saved = $_SESSION['db_config'] ?? [];

        return [
            'host' => getenv('FOOD_DB_HOST') ?: ($saved['host'] ?? self::DB_HOST),
            'port' => getenv('FOOD_DB_PORT') ?: ($saved['port'] ?? self::DB_PORT),
            'name' => getenv('FOOD_DB_NAME') ?: ($saved['name'] ?? self::DB_NAME),
            'user' => getenv('FOOD_DB_USER') ?: ($saved['user'] ?? self::DB_USER),
            'pass' => getenv('FOOD_DB_PASS') !== false ? getenv('FOOD_DB_PASS') : ($saved['pass'] ?? self::DB_PASS),
        ];
    }

    public static function rememberDb(array $data): void
    {
        $_SESSION['db_config'] = [
            'host' => trim((string)($data['db_host'] ?? self::DB_HOST)),
            'port' => trim((string)($data['db_port'] ?? self::DB_PORT)),
            'name' => trim((string)($data['db_name'] ?? self::DB_NAME)),
            'user' => trim((string)($data['db_user'] ?? self::DB_USER)),
            'pass' => (string)($data['db_pass'] ?? ''),
        ];
    }

    public static function debug(): bool
    {
        $env = getenv('APP_DEBUG');
        if ($env !== false) {
            return in_array(strtolower((string)$env), ['1', 'true', 'yes', 'on'], true);
        }

        return !empty($_SESSION['debug_mode']);
    }
}

final class Utility
{
    private static array $translations = [
        'en' => [
            'home' => 'Home',
            'restaurants' => 'Restaurants',
            'orders' => 'Orders',
            'dashboard' => 'Dashboard',
            'login' => 'Log in',
            'register' => 'Register',
            'logout' => 'Log out',
            'cart' => 'Cart',
            'search' => 'Search',
            'language' => 'Language',
            'delivery' => 'Delivery',
            'pickup' => 'Pickup',
            'dinein' => 'Dine-in',
            'checkout' => 'Checkout',
            'featured_restaurants' => 'Featured restaurants',
            'popular_restaurants' => 'Popular restaurants',
            'top_rated' => 'Top rated',
            'nearby' => 'Nearby',
            'categories' => 'Categories',
            'promotions' => 'Promotions',
            'testimonials' => 'Testimonials',
            'statistics' => 'Statistics',
            'order_now' => 'Order now',
            'add_to_cart' => 'Add to cart',
            'view_menu' => 'View menu',
            'place_order' => 'Place order',
            'status' => 'Status',
            'total' => 'Total',
            'subtotal' => 'Subtotal',
            'delivery_fee' => 'Delivery fee',
            'tax' => 'Tax',
            'discount' => 'Discount',
            'coupon' => 'Coupon',
            'payment' => 'Payment',
            'address' => 'Address',
            'phone' => 'Phone',
            'name' => 'Name',
            'email' => 'Email',
            'password' => 'Password',
            'remember_me' => 'Remember me',
            'forgot_password' => 'Forgot password',
            'profile' => 'Profile',
            'notifications' => 'Notifications',
            'admin' => 'Admin',
            'driver' => 'Driver',
            'restaurant' => 'Restaurant',
            'save' => 'Save',
            'update' => 'Update',
            'reviews' => 'Reviews',
            'analytics' => 'Analytics',
            'menu' => 'Menu',
            'staff' => 'Staff',
            'settings' => 'Settings',
        ],
        'fr' => [
            'home' => 'Accueil',
            'restaurants' => 'Restaurants',
            'orders' => 'Commandes',
            'dashboard' => 'Tableau de bord',
            'login' => 'Connexion',
            'register' => 'Inscription',
            'logout' => 'Deconnexion',
            'cart' => 'Panier',
            'search' => 'Rechercher',
            'language' => 'Langue',
            'delivery' => 'Livraison',
            'pickup' => 'A emporter',
            'dinein' => 'Sur place',
            'checkout' => 'Paiement',
            'featured_restaurants' => 'Restaurants en vedette',
            'popular_restaurants' => 'Restaurants populaires',
            'top_rated' => 'Mieux notes',
            'nearby' => 'A proximite',
            'categories' => 'Categories',
            'promotions' => 'Promotions',
            'testimonials' => 'Avis clients',
            'statistics' => 'Statistiques',
            'order_now' => 'Commander',
            'add_to_cart' => 'Ajouter',
            'view_menu' => 'Voir le menu',
            'place_order' => 'Valider',
            'status' => 'Statut',
            'total' => 'Total',
            'subtotal' => 'Sous-total',
            'delivery_fee' => 'Frais de livraison',
            'tax' => 'Taxe',
            'discount' => 'Remise',
            'coupon' => 'Coupon',
            'payment' => 'Paiement',
            'address' => 'Adresse',
            'phone' => 'Telephone',
            'name' => 'Nom',
            'email' => 'Email',
            'password' => 'Mot de passe',
            'remember_me' => 'Se souvenir',
            'forgot_password' => 'Mot de passe oublie',
            'profile' => 'Profil',
            'notifications' => 'Notifications',
            'admin' => 'Admin',
            'driver' => 'Livreur',
            'restaurant' => 'Restaurant',
            'save' => 'Enregistrer',
            'update' => 'Mettre a jour',
            'reviews' => 'Avis',
            'analytics' => 'Analytique',
            'menu' => 'Menu',
            'staff' => 'Equipe',
            'settings' => 'Parametres',
        ],
        'ar' => [
            'home' => 'الرئيسية',
            'restaurants' => 'المطاعم',
            'orders' => 'الطلبات',
            'dashboard' => 'لوحة التحكم',
            'login' => 'تسجيل الدخول',
            'register' => 'إنشاء حساب',
            'logout' => 'تسجيل الخروج',
            'cart' => 'السلة',
            'search' => 'بحث',
            'language' => 'اللغة',
            'delivery' => 'توصيل',
            'pickup' => 'استلام',
            'dinein' => 'داخل المطعم',
            'checkout' => 'الدفع',
            'featured_restaurants' => 'مطاعم مميزة',
            'popular_restaurants' => 'مطاعم رائجة',
            'top_rated' => 'الأعلى تقييما',
            'nearby' => 'قريبة منك',
            'categories' => 'التصنيفات',
            'promotions' => 'العروض',
            'testimonials' => 'آراء العملاء',
            'statistics' => 'الإحصائيات',
            'order_now' => 'اطلب الآن',
            'add_to_cart' => 'أضف للسلة',
            'view_menu' => 'عرض القائمة',
            'place_order' => 'تأكيد الطلب',
            'status' => 'الحالة',
            'total' => 'المجموع',
            'subtotal' => 'المجموع الفرعي',
            'delivery_fee' => 'رسوم التوصيل',
            'tax' => 'الضريبة',
            'discount' => 'الخصم',
            'coupon' => 'القسيمة',
            'payment' => 'الدفع',
            'address' => 'العنوان',
            'phone' => 'الهاتف',
            'name' => 'الاسم',
            'email' => 'البريد',
            'password' => 'كلمة المرور',
            'remember_me' => 'تذكرني',
            'forgot_password' => 'نسيت كلمة المرور',
            'profile' => 'الملف الشخصي',
            'notifications' => 'الإشعارات',
            'admin' => 'الإدارة',
            'driver' => 'السائق',
            'restaurant' => 'المطعم',
            'save' => 'حفظ',
            'update' => 'تحديث',
            'reviews' => 'التقييمات',
            'analytics' => 'التحليلات',
            'menu' => 'القائمة',
            'staff' => 'الفريق',
            'settings' => 'الإعدادات',
        ],
    ];

    public static function lang(): string
    {
        if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr', 'ar'], true)) {
            $_SESSION['lang'] = $_GET['lang'];
        }

        return $_SESSION['lang'] ?? 'en';
    }

    public static function t(string $key): string
    {
        $lang = self::lang();
        return self::$translations[$lang][$key] ?? self::$translations['en'][$key] ?? $key;
    }

    public static function rtl(): bool
    {
        return self::lang() === 'ar';
    }

    public static function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function money(float|int|string $amount): string
    {
        return number_format((float)$amount, 2, '.', ' ') . ' DZD';
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::e(self::csrfToken()) . '">';
    }

    public static function verifyCsrf(): void
    {
        $given = (string)($_POST['_csrf'] ?? '');
        if ($given === '' || !hash_equals(self::csrfToken(), $given)) {
            throw new RuntimeException('Security check failed. Please refresh the page and try again.');
        }
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function flashes(): array
    {
        $items = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $items;
    }

    public static function redirect(string $url): never
    {
        if (!str_starts_with($url, '?') && !str_starts_with($url, '/')) {
            $url = '?page=home';
        }
        header('Location: ' . $url);
        exit;
    }

    public static function slug(string $text): string
    {
        $text = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $text) ?? ''));
        return trim($text, '-') ?: bin2hex(random_bytes(3));
    }

    public static function int(mixed $value, int $default = 0): int
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int)$value : $default;
    }
}

function e(mixed $value): string
{
    return Utility::e($value);
}

function t(string $key): string
{
    return Utility::t($key);
}

final class Database
{
    private PDO $pdo;

    public function __construct(array $config)
    {
        $name = (string)$config['name'];
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            throw new InvalidArgumentException('Database name may contain letters, numbers, and underscores only.');
        }

        $serverDsn = sprintf(
            'mysql:host=%s;port=%s;charset=utf8mb4',
            $config['host'],
            $config['port']
        );
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $server = new PDO($serverDsn, (string)$config['user'], (string)$config['pass'], $options);
        $server->exec('CREATE DATABASE IF NOT EXISTS `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $dbDsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $name
        );
        $this->pdo = new PDO($dbDsn, (string)$config['user'], (string)$config['pass'], $options);
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public function all(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert(string $sql, array $params = []): int
    {
        $this->query($sql, $params);
        return (int)$this->pdo->lastInsertId();
    }

    public function migrate(): void
    {
        $tables = [
            "CREATE TABLE IF NOT EXISTS users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                role ENUM('customer','restaurant_owner','restaurant_staff','driver','admin') NOT NULL DEFAULT 'customer',
                name VARCHAR(160) NOT NULL,
                email VARCHAR(190) NOT NULL UNIQUE,
                phone VARCHAR(40) NULL,
                password_hash VARCHAR(255) NOT NULL,
                avatar_url VARCHAR(500) NULL,
                status ENUM('active','suspended','pending') NOT NULL DEFAULT 'active',
                preferred_language ENUM('en','fr','ar') NOT NULL DEFAULT 'en',
                remember_token VARCHAR(255) NULL,
                last_login_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_users_role_status (role, status),
                INDEX idx_users_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS categories (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name_en VARCHAR(120) NOT NULL,
                name_fr VARCHAR(120) NOT NULL,
                name_ar VARCHAR(120) NOT NULL,
                slug VARCHAR(140) NOT NULL UNIQUE,
                icon VARCHAR(80) NOT NULL DEFAULT 'bi-shop',
                image_url VARCHAR(500) NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_categories_active_sort (active, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS restaurants (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                owner_id INT UNSIGNED NULL,
                category_id INT UNSIGNED NULL,
                name VARCHAR(180) NOT NULL,
                slug VARCHAR(200) NOT NULL UNIQUE,
                description TEXT NOT NULL,
                address VARCHAR(255) NOT NULL,
                city VARCHAR(120) NOT NULL,
                latitude DECIMAL(10,7) NULL,
                longitude DECIMAL(10,7) NULL,
                phone VARCHAR(40) NULL,
                email VARCHAR(190) NULL,
                cover_image VARCHAR(500) NULL,
                logo VARCHAR(500) NULL,
                cuisine_type VARCHAR(120) NOT NULL,
                delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
                min_order DECIMAL(10,2) NOT NULL DEFAULT 0,
                avg_delivery_time INT NOT NULL DEFAULT 35,
                opening_hours VARCHAR(120) NOT NULL DEFAULT '09:00 - 23:00',
                status ENUM('open','closed','paused','pending') NOT NULL DEFAULT 'open',
                is_featured TINYINT(1) NOT NULL DEFAULT 0,
                rating_avg DECIMAL(3,2) NOT NULL DEFAULT 0,
                rating_count INT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_restaurant_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT fk_restaurant_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
                INDEX idx_restaurants_city (city),
                INDEX idx_restaurants_featured (is_featured, status),
                INDEX idx_restaurants_rating (rating_avg, rating_count),
                FULLTEXT INDEX ft_restaurants_search (name, description, cuisine_type, city)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS restaurant_staff (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                restaurant_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                staff_role VARCHAR(80) NOT NULL DEFAULT 'manager',
                permissions JSON NULL,
                status ENUM('active','inactive') NOT NULL DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_staff_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
                CONSTRAINT fk_staff_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY uq_staff_restaurant_user (restaurant_id, user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS drivers (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL UNIQUE,
                vehicle_type ENUM('bike','scooter','car') NOT NULL DEFAULT 'scooter',
                license_number VARCHAR(80) NULL,
                current_lat DECIMAL(10,7) NULL,
                current_lng DECIMAL(10,7) NULL,
                status ENUM('online','offline','busy') NOT NULL DEFAULT 'offline',
                earnings_balance DECIMAL(10,2) NOT NULL DEFAULT 0,
                rating_avg DECIMAL(3,2) NOT NULL DEFAULT 5,
                total_deliveries INT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_driver_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_drivers_status_location (status, current_lat, current_lng)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS products (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                restaurant_id INT UNSIGNED NOT NULL,
                category_id INT UNSIGNED NULL,
                name VARCHAR(180) NOT NULL,
                description TEXT NOT NULL,
                ingredients TEXT NULL,
                image_url VARCHAR(500) NULL,
                price DECIMAL(10,2) NOT NULL,
                discount_price DECIMAL(10,2) NULL,
                size_options JSON NULL,
                is_available TINYINT(1) NOT NULL DEFAULT 1,
                is_featured TINYINT(1) NOT NULL DEFAULT 0,
                rating_avg DECIMAL(3,2) NOT NULL DEFAULT 0,
                rating_count INT UNSIGNED NOT NULL DEFAULT 0,
                prep_time_minutes INT NOT NULL DEFAULT 15,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_product_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
                CONSTRAINT fk_product_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
                INDEX idx_products_restaurant_available (restaurant_id, is_available),
                INDEX idx_products_featured (is_featured),
                FULLTEXT INDEX ft_products_search (name, description, ingredients)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS product_images (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                product_id INT UNSIGNED NOT NULL,
                image_url VARCHAR(500) NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                CONSTRAINT fk_product_image_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                INDEX idx_product_images_product (product_id, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS product_extras (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                product_id INT UNSIGNED NOT NULL,
                name VARCHAR(150) NOT NULL,
                price DECIMAL(10,2) NOT NULL DEFAULT 0,
                is_required TINYINT(1) NOT NULL DEFAULT 0,
                max_quantity INT NOT NULL DEFAULT 1,
                CONSTRAINT fk_product_extra_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                INDEX idx_product_extras_product (product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS coupons (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(60) NOT NULL UNIQUE,
                type ENUM('percentage','fixed') NOT NULL,
                value DECIMAL(10,2) NOT NULL,
                min_order DECIMAL(10,2) NOT NULL DEFAULT 0,
                max_discount DECIMAL(10,2) NULL,
                restaurant_id INT UNSIGNED NULL,
                starts_at DATETIME NULL,
                ends_at DATETIME NULL,
                usage_limit INT UNSIGNED NULL,
                used_count INT UNSIGNED NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_coupon_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
                INDEX idx_coupons_code_active (code, is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS orders (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                order_number VARCHAR(40) NOT NULL UNIQUE,
                user_id INT UNSIGNED NULL,
                restaurant_id INT UNSIGNED NOT NULL,
                driver_id INT UNSIGNED NULL,
                coupon_id INT UNSIGNED NULL,
                delivery_type ENUM('delivery','pickup','dinein') NOT NULL DEFAULT 'delivery',
                status ENUM('pending','accepted','preparing','ready','assigned_driver','picked_up','delivered','cancelled','rejected') NOT NULL DEFAULT 'pending',
                customer_name VARCHAR(160) NOT NULL,
                customer_email VARCHAR(190) NULL,
                customer_phone VARCHAR(40) NOT NULL,
                delivery_address VARCHAR(255) NULL,
                table_number VARCHAR(40) NULL,
                subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
                discount_total DECIMAL(10,2) NOT NULL DEFAULT 0,
                delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
                tax_total DECIMAL(10,2) NOT NULL DEFAULT 0,
                total DECIMAL(10,2) NOT NULL DEFAULT 0,
                payment_method ENUM('cash','card','wallet') NOT NULL DEFAULT 'cash',
                payment_status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
                notes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT fk_order_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
                CONSTRAINT fk_order_driver FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL,
                CONSTRAINT fk_order_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE SET NULL,
                INDEX idx_orders_user_created (user_id, created_at),
                INDEX idx_orders_restaurant_status (restaurant_id, status),
                INDEX idx_orders_driver_status (driver_id, status),
                INDEX idx_orders_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS order_items (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                order_id INT UNSIGNED NOT NULL,
                product_id INT UNSIGNED NULL,
                product_name VARCHAR(180) NOT NULL,
                unit_price DECIMAL(10,2) NOT NULL,
                quantity INT UNSIGNED NOT NULL DEFAULT 1,
                extras_total DECIMAL(10,2) NOT NULL DEFAULT 0,
                extras_json JSON NULL,
                total DECIMAL(10,2) NOT NULL,
                CONSTRAINT fk_order_item_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                CONSTRAINT fk_order_item_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
                INDEX idx_order_items_order (order_id),
                INDEX idx_order_items_product (product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS deliveries (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                order_id INT UNSIGNED NOT NULL UNIQUE,
                driver_id INT UNSIGNED NULL,
                status ENUM('assigned','arrived_pickup','picked_up','delivered','failed') NOT NULL DEFAULT 'assigned',
                pickup_address VARCHAR(255) NOT NULL,
                dropoff_address VARCHAR(255) NULL,
                distance_km DECIMAL(8,2) NOT NULL DEFAULT 0,
                assigned_at DATETIME NULL,
                picked_up_at DATETIME NULL,
                delivered_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_delivery_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                CONSTRAINT fk_delivery_driver FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL,
                INDEX idx_deliveries_driver_status (driver_id, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS payments (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                order_id INT UNSIGNED NOT NULL,
                provider VARCHAR(80) NOT NULL DEFAULT 'cash',
                method ENUM('cash','card','wallet') NOT NULL DEFAULT 'cash',
                amount DECIMAL(10,2) NOT NULL,
                currency VARCHAR(10) NOT NULL DEFAULT 'DZD',
                status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
                transaction_reference VARCHAR(120) NULL,
                paid_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_payment_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                INDEX idx_payments_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS reviews (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NULL,
                restaurant_id INT UNSIGNED NULL,
                product_id INT UNSIGNED NULL,
                order_id INT UNSIGNED NULL,
                rating TINYINT UNSIGNED NOT NULL,
                comment TEXT NULL,
                status ENUM('approved','pending','hidden') NOT NULL DEFAULT 'approved',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_review_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT fk_review_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
                CONSTRAINT fk_review_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                CONSTRAINT fk_review_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
                INDEX idx_reviews_restaurant (restaurant_id, status),
                INDEX idx_reviews_product (product_id, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS notifications (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NULL,
                role_target VARCHAR(60) NULL,
                title VARCHAR(180) NOT NULL,
                body TEXT NOT NULL,
                type VARCHAR(80) NOT NULL DEFAULT 'info',
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                data JSON NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_notifications_user_read (user_id, is_read),
                INDEX idx_notifications_role_read (role_target, is_read)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS settings (
                `key` VARCHAR(120) PRIMARY KEY,
                `value` TEXT NOT NULL,
                autoload TINYINT(1) NOT NULL DEFAULT 1,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS audit_logs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NULL,
                action VARCHAR(120) NOT NULL,
                entity_type VARCHAR(120) NULL,
                entity_id VARCHAR(80) NULL,
                ip_address VARCHAR(80) NULL,
                user_agent VARCHAR(255) NULL,
                metadata JSON NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_audit_action_created (action, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];

        foreach ($tables as $sql) {
            $this->pdo->exec($sql);
        }
    }

    public function seedIfNeeded(): void
    {
        $seeded = $this->fetch("SELECT `value` FROM settings WHERE `key` = 'seeded_demo'");
        if ($seeded && $seeded['value'] === '1') {
            return;
        }

        $this->pdo->beginTransaction();
        try {
            Seeder::run($this);
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}

final class Seeder
{
    public static function run(Database $db): void
    {
        mt_srand(27);
        $hash = password_hash('password', PASSWORD_DEFAULT);
        $db->insert(
            "INSERT INTO users (role, name, email, phone, password_hash, avatar_url, preferred_language)
             VALUES ('admin', 'Admin DzairEats', 'admin@dzaireats.test', '+213 555 000 001', ?, 'https://i.pravatar.cc/160?img=12', 'en')",
            [$hash]
        );

        $categories = [
            ['Pizza', 'Pizza', 'بيتزا', 'pizza', 'bi-fire', 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=900&q=80'],
            ['Burger', 'Burger', 'برغر', 'burger', 'bi-egg-fried', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=900&q=80'],
            ['Tacos', 'Tacos', 'تاكوس', 'tacos', 'bi-lightning-charge', 'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?auto=format&fit=crop&w=900&q=80'],
            ['Coffee', 'Cafe', 'قهوة', 'coffee', 'bi-cup-hot', 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=900&q=80'],
            ['Bakery', 'Boulangerie', 'مخبزة', 'bakery', 'bi-basket', 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=900&q=80'],
            ['Shawarma', 'Shawarma', 'شاورما', 'shawarma', 'bi-cone-striped', 'https://images.unsplash.com/photo-1599487488170-d11ec9c172f0?auto=format&fit=crop&w=900&q=80'],
            ['Traditional', 'Traditionnel', 'تقليدي', 'traditional', 'bi-stars', 'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?auto=format&fit=crop&w=900&q=80'],
            ['Desserts', 'Desserts', 'حلويات', 'desserts', 'bi-cake2', 'https://images.unsplash.com/photo-1488477181946-6428a0291777?auto=format&fit=crop&w=900&q=80'],
        ];
        $categoryIds = [];
        foreach ($categories as $i => $cat) {
            $categoryIds[$cat[3]] = $db->insert(
                'INSERT INTO categories (name_en, name_fr, name_ar, slug, icon, image_url, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$cat[0], $cat[1], $cat[2], $cat[3], $cat[4], $cat[5], $i + 1]
            );
        }

        $cities = [
            ['Algiers', 36.7538, 3.0588],
            ['Oran', 35.6971, -0.6308],
            ['Constantine', 36.3650, 6.6147],
            ['Annaba', 36.9000, 7.7667],
            ['Blida', 36.4700, 2.8300],
            ['Setif', 36.1911, 5.4137],
            ['Tlemcen', 34.8783, -1.3150],
        ];
        $restaurants = [
            ['Casbah Pizza Club', 'pizza', 'Italian-Algerian pizza', 'Stone-baked pizzas with Algerian herbs, merguez toppings, and quick city delivery.'],
            ['Bab El Oued Burgers', 'burger', 'Smash burgers', 'Premium beef, house sauces, loaded fries, and generous neighborhood portions.'],
            ['Tacos Hydra', 'tacos', 'French tacos', 'Crispy gratinated tacos filled with chicken, cordon bleu, fries, and signature sauces.'],
            ['El Bahia Coffee', 'coffee', 'Specialty coffee', 'Single origin espresso, fresh juices, brunch plates, and quiet work tables.'],
            ['La Mitidja Bakery', 'bakery', 'Artisan bakery', 'Daily croissants, baguettes, msemen, and celebration cakes made before sunrise.'],
            ['Sahara Shawarma', 'shawarma', 'Levantine shawarma', 'Slow-roasted chicken and beef shawarma with garlic cream and pickles.'],
            ['Dar El Couscous', 'traditional', 'Algerian traditional', 'Friday couscous, rechta, chorba frik, and family dishes from across Algeria.'],
            ['Millefeuille Palace', 'desserts', 'Patisserie', 'French and Algerian pastries, coffee boxes, makrout, and honey sweets.'],
            ['Pizza Didouche', 'pizza', 'Pizza and pasta', 'Fast oven pizzas, pasta bowls, salads, and late-night student favorites.'],
            ['Burger Wahran', 'burger', 'Gourmet burgers', 'Charcoal burgers, cheddar, caramelized onions, and coastal Oran energy.'],
            ['Tacos Emir', 'tacos', 'Loaded tacos', 'XL tacos, cheese sauce, spicy harissa, and crispy chicken strips.'],
            ['Constantine Roasters', 'coffee', 'Roastery cafe', 'Fresh roasted beans, Turkish coffee, cakes, and slow mornings.'],
            ['Boulangerie El Amir', 'bakery', 'Bakery and viennoiserie', 'Golden pastries, traditional breads, and breakfast bundles.'],
            ['Shawarma Kouba', 'shawarma', 'Street shawarma', 'Hot wraps, saj bread, plates, and generous garlic sauce.'],
            ['Table Kabyle', 'traditional', 'Kabyle food', 'Aghrum, seksu, olive oil plates, grilled meats, and seasonal vegetables.'],
            ['Sweet Algiers', 'desserts', 'Desserts and ice cream', 'Cheesecake jars, crepes, waffles, gelato, and celebration boxes.'],
            ['Pizza El Hamma', 'pizza', 'Family pizza', 'Family size pizzas, fresh mozzarella, and delivery bundles for match night.'],
            ['Burger Setif', 'burger', 'Fast casual burgers', 'Clean fast casual burgers, grilled chicken, and local sauce flights.'],
            ['Tacos Tlemcen', 'tacos', 'Tacos and sandwiches', 'French tacos, paninis, fries, and student-friendly menus.'],
            ['Dar Dzair Tradition', 'traditional', 'Home cooking', 'Authentic rechta, dolma, tajine zitoune, and weekend couscous boxes.'],
        ];
        $foodImages = [
            'pizza' => 'https://images.unsplash.com/photo-1604382354936-07c5d9983bd3?auto=format&fit=crop&w=900&q=80',
            'burger' => 'https://images.unsplash.com/photo-1550547660-d9450f859349?auto=format&fit=crop&w=900&q=80',
            'tacos' => 'https://images.unsplash.com/photo-1565299507177-b0ac66763828?auto=format&fit=crop&w=900&q=80',
            'coffee' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=900&q=80',
            'bakery' => 'https://images.unsplash.com/photo-1517433367423-c7e5b0f35086?auto=format&fit=crop&w=900&q=80',
            'shawarma' => 'https://images.unsplash.com/photo-1530469912745-a215c6b256ea?auto=format&fit=crop&w=900&q=80',
            'traditional' => 'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?auto=format&fit=crop&w=900&q=80',
            'desserts' => 'https://images.unsplash.com/photo-1551024601-bec78aea704b?auto=format&fit=crop&w=900&q=80',
        ];

        $restaurantIds = [];
        $productIdsByRestaurant = [];
        foreach ($restaurants as $i => $restaurant) {
            $city = $cities[$i % count($cities)];
            $ownerId = $db->insert(
                'INSERT INTO users (role, name, email, phone, password_hash, avatar_url, preferred_language) VALUES (?, ?, ?, ?, ?, ?, ?)',
                ['restaurant_owner', $restaurant[0] . ' Owner', 'owner' . ($i + 1) . '@dzaireats.test', '+213 555 10' . str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT), $hash, 'https://i.pravatar.cc/160?img=' . (($i % 60) + 1), $i % 3 === 0 ? 'fr' : 'en']
            );
            $staffId = $db->insert(
                'INSERT INTO users (role, name, email, phone, password_hash, avatar_url, preferred_language) VALUES (?, ?, ?, ?, ?, ?, ?)',
                ['restaurant_staff', $restaurant[0] . ' Manager', 'staff' . ($i + 1) . '@dzaireats.test', '+213 555 20' . str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT), $hash, 'https://i.pravatar.cc/160?img=' . ((($i + 15) % 60) + 1), 'fr']
            );
            $slug = Utility::slug($restaurant[0]);
            $rid = $db->insert(
                'INSERT INTO restaurants (owner_id, category_id, name, slug, description, address, city, latitude, longitude, phone, email, cover_image, logo, cuisine_type, delivery_fee, min_order, avg_delivery_time, opening_hours, status, is_featured)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $ownerId,
                    $categoryIds[$restaurant[1]],
                    $restaurant[0],
                    $slug,
                    $restaurant[3],
                    (15 + $i) . ' Rue Didouche Mourad',
                    $city[0],
                    $city[1] + (mt_rand(-120, 120) / 10000),
                    $city[2] + (mt_rand(-120, 120) / 10000),
                    '+213 560 ' . str_pad((string)mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT),
                    'hello+' . $slug . '@dzaireats.test',
                    $foodImages[$restaurant[1]],
                    'https://ui-avatars.com/api/?background=111827&color=fff&bold=true&name=' . rawurlencode($restaurant[0]),
                    $restaurant[2],
                    120 + (($i % 5) * 30),
                    700 + (($i % 4) * 150),
                    25 + (($i % 6) * 5),
                    ($i % 4 === 0) ? '08:00 - 00:00' : '09:00 - 23:00',
                    'open',
                    $i < 8 ? 1 : 0,
                ]
            );
            $restaurantIds[] = $rid;
            $db->insert('INSERT INTO restaurant_staff (restaurant_id, user_id, staff_role, permissions) VALUES (?, ?, ?, ?)', [$rid, $staffId, 'manager', json_encode(['orders', 'menu', 'reviews'])]);

            $products = self::productsForCuisine($restaurant[1]);
            foreach ($products as $p => $product) {
                $base = (float)$product[2] + (($i % 3) * 40);
                $discount = $p % 5 === 0 ? $base - 80 : null;
                $pid = $db->insert(
                    'INSERT INTO products (restaurant_id, category_id, name, description, ingredients, image_url, price, discount_price, size_options, is_available, is_featured, prep_time_minutes)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)',
                    [
                        $rid,
                        $categoryIds[$restaurant[1]],
                        $product[0],
                        $product[1],
                        $product[3],
                        $product[4] ?: $foodImages[$restaurant[1]],
                        $base,
                        $discount,
                        json_encode([
                            ['name' => 'Regular', 'price' => 0],
                            ['name' => 'Large', 'price' => 180],
                            ['name' => 'Family', 'price' => 420],
                        ], JSON_UNESCAPED_UNICODE),
                        $p < 3 ? 1 : 0,
                        10 + (($p + $i) % 12),
                    ]
                );
                $productIdsByRestaurant[$rid][] = $pid;
                $db->insert('INSERT INTO product_images (product_id, image_url, sort_order) VALUES (?, ?, ?)', [$pid, $product[4] ?: $foodImages[$restaurant[1]], 1]);
                foreach (self::extrasFor($restaurant[1], $p) as $extra) {
                    $db->insert('INSERT INTO product_extras (product_id, name, price, is_required, max_quantity) VALUES (?, ?, ?, 0, 2)', [$pid, $extra[0], $extra[1]]);
                }
            }
        }

        $customerIds = [];
        $names = ['Yacine', 'Amina', 'Lina', 'Mehdi', 'Sofia', 'Khaled', 'Nour', 'Rayan', 'Samir', 'Meriem'];
        for ($i = 1; $i <= 50; $i++) {
            $name = $names[$i % count($names)] . ' Benali ' . $i;
            $customerIds[] = $db->insert(
                'INSERT INTO users (role, name, email, phone, password_hash, avatar_url, preferred_language) VALUES (?, ?, ?, ?, ?, ?, ?)',
                ['customer', $name, 'customer' . $i . '@dzaireats.test', '+213 660 ' . str_pad((string)(100000 + $i), 6, '0', STR_PAD_LEFT), $hash, 'https://i.pravatar.cc/160?img=' . (($i % 70) + 1), ['en', 'fr', 'ar'][$i % 3]]
            );
        }

        $driverIds = [];
        for ($i = 1; $i <= 20; $i++) {
            $city = $cities[$i % count($cities)];
            $userId = $db->insert(
                'INSERT INTO users (role, name, email, phone, password_hash, avatar_url, preferred_language) VALUES (?, ?, ?, ?, ?, ?, ?)',
                ['driver', 'Driver ' . $i . ' Dzair', 'driver' . $i . '@dzaireats.test', '+213 770 ' . str_pad((string)(200000 + $i), 6, '0', STR_PAD_LEFT), $hash, 'https://i.pravatar.cc/160?img=' . (($i + 25) % 70), 'fr']
            );
            $driverIds[] = $db->insert(
                'INSERT INTO drivers (user_id, vehicle_type, license_number, current_lat, current_lng, status, earnings_balance, rating_avg, total_deliveries) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$userId, ['bike', 'scooter', 'car'][$i % 3], 'DZ-' . strtoupper(bin2hex(random_bytes(3))), $city[1] + (mt_rand(-150, 150) / 10000), $city[2] + (mt_rand(-150, 150) / 10000), $i % 4 === 0 ? 'busy' : 'online', mt_rand(4000, 45000), 4.4 + (($i % 6) / 10), mt_rand(8, 190)]
            );
        }

        $db->insert("INSERT INTO coupons (code, type, value, min_order, max_discount, starts_at, ends_at, usage_limit, is_active) VALUES ('WELCOME20', 'percentage', 20, 900, 600, NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 1000, 1)");
        $db->insert("INSERT INTO coupons (code, type, value, min_order, max_discount, starts_at, ends_at, usage_limit, is_active) VALUES ('DZ500', 'fixed', 500, 1800, NULL, NOW(), DATE_ADD(NOW(), INTERVAL 60 DAY), 500, 1)");
        $db->insert("INSERT INTO coupons (code, type, value, min_order, max_discount, starts_at, ends_at, usage_limit, is_active) VALUES ('COUSCOUS', 'percentage', 15, 1200, 450, NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY), 300, 1)");

        foreach ($restaurantIds as $idx => $rid) {
            for ($r = 0; $r < 5; $r++) {
                $uid = $customerIds[($idx + $r) % count($customerIds)];
                $rating = 4 + (($idx + $r) % 2);
                $db->insert('INSERT INTO reviews (user_id, restaurant_id, rating, comment, status) VALUES (?, ?, ?, ?, ?)', [$uid, $rid, $rating, self::reviewText($rating), 'approved']);
            }
        }

        for ($i = 0; $i < 42; $i++) {
            $rid = $restaurantIds[$i % count($restaurantIds)];
            $uid = $customerIds[$i % count($customerIds)];
            $driverId = $driverIds[$i % count($driverIds)];
            $productId = $productIdsByRestaurant[$rid][$i % 10];
            $product = $db->fetch('SELECT name, COALESCE(discount_price, price) AS sell_price FROM products WHERE id = ?', [$productId]);
            if (!$product) {
                continue;
            }
            $subtotal = (float)$product['sell_price'] * (($i % 3) + 1);
            $deliveryFee = 120 + (($i % 4) * 30);
            $tax = round($subtotal * 0.09, 2);
            $total = $subtotal + $deliveryFee + $tax;
            $status = ['pending', 'accepted', 'preparing', 'ready', 'assigned_driver', 'picked_up', 'delivered'][$i % 7];
            $orderId = $db->insert(
                'INSERT INTO orders (order_number, user_id, restaurant_id, driver_id, delivery_type, status, customer_name, customer_email, customer_phone, delivery_address, subtotal, delivery_fee, tax_total, total, payment_method, payment_status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY))',
                ['DZ' . date('ymd') . str_pad((string)($i + 1), 5, '0', STR_PAD_LEFT), $uid, $rid, $driverId, 'delivery', $status, 'Demo Customer ' . ($i + 1), 'customer' . (($i % 50) + 1) . '@dzaireats.test', '+213 660 10' . str_pad((string)$i, 2, '0', STR_PAD_LEFT), 'Hydra, Algiers', $subtotal, $deliveryFee, $tax, $total, $i % 3 === 0 ? 'card' : 'cash', $i % 3 === 0 ? 'paid' : 'pending', $i % 30]
            );
            $db->insert('INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, extras_total, extras_json, total) VALUES (?, ?, ?, ?, ?, 0, ?, ?)', [$orderId, $productId, $product['name'], $product['sell_price'], ($i % 3) + 1, json_encode([]), $subtotal]);
            $db->insert('INSERT INTO payments (order_id, provider, method, amount, status, transaction_reference, paid_at) VALUES (?, ?, ?, ?, ?, ?, ?)', [$orderId, $i % 3 === 0 ? 'demo-card' : 'cash', $i % 3 === 0 ? 'card' : 'cash', $total, $i % 3 === 0 ? 'paid' : 'pending', 'PAY-' . strtoupper(bin2hex(random_bytes(4))), $i % 3 === 0 ? date('Y-m-d H:i:s') : null]);
            if ($status !== 'pending') {
                $db->insert('INSERT INTO deliveries (order_id, driver_id, status, pickup_address, dropoff_address, distance_km, assigned_at, picked_up_at, delivered_at) VALUES (?, ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? HOUR), ?, ?)', [
                    $orderId,
                    $driverId,
                    in_array($status, ['picked_up', 'delivered'], true) ? $status : 'assigned',
                    'Restaurant pickup',
                    'Customer address',
                    1.2 + (($i % 8) / 2),
                    $i + 1,
                    in_array($status, ['picked_up', 'delivered'], true) ? date('Y-m-d H:i:s', time() - 1800) : null,
                    $status === 'delivered' ? date('Y-m-d H:i:s') : null,
                ]);
            }
        }

        $db->query("UPDATE restaurants r SET rating_avg = COALESCE((SELECT ROUND(AVG(rating), 2) FROM reviews WHERE restaurant_id = r.id AND status = 'approved'), 0), rating_count = COALESCE((SELECT COUNT(*) FROM reviews WHERE restaurant_id = r.id AND status = 'approved'), 0)");
        $db->query("UPDATE products p SET rating_avg = 4.2 + ((p.id % 8) / 10), rating_count = 6 + (p.id % 60)");

        $settings = [
            'seeded_demo' => '1',
            'platform_name' => Config::APP_NAME,
            'tax_percent' => '9',
            'service_fee_percent' => '4',
            'support_phone' => '+213 555 404 404',
            'default_city' => 'Algiers',
            'currency' => 'DZD',
            'production_mode' => '1',
        ];
        foreach ($settings as $key => $value) {
            $db->insert('REPLACE INTO settings (`key`, `value`, autoload) VALUES (?, ?, 1)', [$key, $value]);
        }
        $db->insert('INSERT INTO notifications (role_target, title, body, type) VALUES (?, ?, ?, ?)', ['admin', 'Demo marketplace ready', 'The database was installed with Algerian restaurants, products, customers, drivers, orders, coupons, and reviews.', 'system']);
        $db->insert('INSERT INTO audit_logs (action, entity_type, entity_id, ip_address, user_agent, metadata) VALUES (?, ?, ?, ?, ?, ?)', ['seed_demo', 'system', 'installer', $_SERVER['REMOTE_ADDR'] ?? 'cli', substr($_SERVER['HTTP_USER_AGENT'] ?? 'cli', 0, 250), json_encode(['restaurants' => 20, 'products' => 200, 'customers' => 50, 'drivers' => 20])]);
    }

    private static function productsForCuisine(string $cuisine): array
    {
        $img = [
            'pizza' => 'https://images.unsplash.com/photo-1594007654729-407eedc4be65?auto=format&fit=crop&w=900&q=80',
            'burger' => 'https://images.unsplash.com/photo-1594212699903-ec8a3eca50f5?auto=format&fit=crop&w=900&q=80',
            'tacos' => 'https://images.unsplash.com/photo-1613514785940-daed07799d9b?auto=format&fit=crop&w=900&q=80',
            'coffee' => 'https://images.unsplash.com/photo-1521302080334-4bebac2763a6?auto=format&fit=crop&w=900&q=80',
            'bakery' => 'https://images.unsplash.com/photo-1486427944299-d1955d23e34d?auto=format&fit=crop&w=900&q=80',
            'shawarma' => 'https://images.unsplash.com/photo-1606755962773-d324e0a13086?auto=format&fit=crop&w=900&q=80',
            'traditional' => 'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?auto=format&fit=crop&w=900&q=80',
            'desserts' => 'https://images.unsplash.com/photo-1505253210343-bd13e91aa697?auto=format&fit=crop&w=900&q=80',
        ];
        $base = [
            'pizza' => [['Merguez Pizza', 'Tomato, mozzarella, spicy merguez, peppers.', 980, 'mozzarella, tomato, merguez, olives'], ['Reine Pizza', 'Chicken, mushrooms, cream, mozzarella.', 920, 'chicken, mushrooms, cream'], ['Four Cheese Pizza', 'Creamy blend of local and Italian cheeses.', 1100, 'mozzarella, cheddar, blue cheese'], ['Tuna Harissa Pizza', 'Tuna, onions, harissa drizzle.', 870, 'tuna, onion, harissa'], ['Vegetable Pizza', 'Seasonal grilled vegetables and olives.', 760, 'zucchini, peppers, olives'], ['Family Dzair Pizza', 'Large sharing pizza with mixed toppings.', 1800, 'beef, chicken, olives'], ['Seafood Pizza', 'Shrimp, calamari, tomato, herbs.', 1450, 'shrimp, calamari'], ['Chicken BBQ Pizza', 'BBQ chicken and red onions.', 1050, 'chicken, bbq sauce'], ['Margherita', 'Classic tomato basil mozzarella.', 690, 'tomato, mozzarella, basil'], ['Calzone Maison', 'Folded pizza with cream and turkey.', 940, 'turkey, cream, cheese']],
            'burger' => [['Smash Burger', 'Double smashed beef, cheddar, pickles.', 980, 'beef, cheddar, pickles'], ['Chicken Crispy', 'Crispy chicken breast and garlic sauce.', 820, 'chicken, lettuce, garlic'], ['Merguez Burger', 'Spicy merguez patty with harissa mayo.', 890, 'merguez, harissa'], ['Royal Cheese', 'Beef, turkey bacon, caramelized onions.', 1120, 'beef, cheese, onions'], ['Veggie Burger', 'Chickpea patty and yogurt sauce.', 740, 'chickpea, herbs'], ['Big Dzair Box', 'Burger, fries, drink, sauce flight.', 1390, 'beef, fries'], ['Mushroom Melt', 'Beef, mushrooms, melted cheese.', 1040, 'beef, mushrooms'], ['Fish Burger', 'Crispy fish and lemon mayo.', 900, 'fish, lemon'], ['Mini Sliders', 'Three small burgers for sharing.', 1180, 'beef, buns'], ['Loaded Fries', 'Fries, cheese sauce, beef crumble.', 680, 'potato, cheese']],
            'tacos' => [['Tacos Poulet', 'Chicken, fries, cheese sauce, house sauce.', 780, 'chicken, fries, cheese'], ['Tacos Cordon Bleu', 'Cordon bleu, fries, creamy cheese.', 920, 'cordon bleu, fries'], ['Tacos Mixte', 'Chicken, minced beef, fries, sauce.', 980, 'chicken, beef'], ['Tacos Merguez', 'Merguez, fries, harissa, cheese.', 860, 'merguez, harissa'], ['Tacos Tenders', 'Crispy tenders, cheddar, fries.', 950, 'chicken tenders, cheddar'], ['Tacos XL', 'Two meats, double cheese, extra fries.', 1290, 'mixed meats, fries'], ['Tacos Vegetarian', 'Grilled vegetables, fries, cheese.', 690, 'vegetables, cheese'], ['Tacos Escalope', 'Turkey escalope and mushroom sauce.', 890, 'turkey, mushrooms'], ['Menu Tacos', 'Tacos, fries, drink.', 1150, 'tacos, fries'], ['Tacos Gratin', 'Oven-gratinated tacos with mozzarella.', 1050, 'mozzarella, chicken']],
            'coffee' => [['Espresso', 'Balanced local roast espresso.', 220, 'coffee'], ['Cappuccino', 'Espresso with silky milk.', 360, 'coffee, milk'], ['Iced Latte', 'Cold milk, espresso, vanilla.', 430, 'coffee, milk, vanilla'], ['Turkish Coffee', 'Rich traditional coffee cup.', 300, 'coffee, cardamom'], ['Brunch Plate', 'Eggs, bread, cheese, olives.', 980, 'eggs, bread'], ['Avocado Toast', 'Sourdough, avocado, eggs.', 850, 'avocado, bread'], ['Fresh Orange Juice', 'Pressed Algerian oranges.', 320, 'orange'], ['Chocolate Cake', 'Dense chocolate slice.', 520, 'chocolate'], ['Date Smoothie', 'Dates, milk, banana.', 480, 'dates, banana'], ['Coffee Box', 'Four coffees and pastry bites.', 1550, 'coffee, pastry']],
            'bakery' => [['Croissant Beurre', 'Flaky butter croissant.', 180, 'butter, flour'], ['Pain Chocolat', 'Chocolate viennoiserie.', 220, 'chocolate, flour'], ['Msemen Honey', 'Layered msemen with honey.', 280, 'semolina, honey'], ['Baguette Tradition', 'Long fermented baguette.', 100, 'flour, yeast'], ['Mini Pizza', 'Bakery pizza with olives.', 260, 'tomato, olives'], ['Cheese Borek', 'Crispy pastry with cheese.', 240, 'cheese, pastry'], ['Family Bread Basket', 'Mixed breads for dinner.', 620, 'bread'], ['Strawberry Tart', 'Tart shell, cream, strawberries.', 450, 'strawberry, cream'], ['Birthday Cake Slice', 'Vanilla cream cake slice.', 520, 'vanilla, cream'], ['Breakfast Bundle', 'Bread, croissants, juice.', 1250, 'bread, pastry']],
            'shawarma' => [['Chicken Shawarma', 'Chicken wrap with garlic cream.', 650, 'chicken, garlic'], ['Beef Shawarma', 'Beef wrap with tahini and pickles.', 760, 'beef, tahini'], ['Shawarma Plate', 'Meat, fries, salad, sauces.', 1180, 'meat, fries'], ['Saj Chicken', 'Thin saj bread with chicken.', 720, 'chicken, saj'], ['Falafel Wrap', 'Falafel, tahini, pickles.', 580, 'chickpea, tahini'], ['Mixed Grill Box', 'Skewers, shawarma, fries.', 1490, 'grilled meats'], ['Garlic Fries', 'Fries with garlic sauce.', 380, 'potato, garlic'], ['Hummus Bowl', 'Creamy hummus with olive oil.', 420, 'chickpea'], ['Shawarma Family', 'Four wraps and sides.', 2450, 'shawarma, sides'], ['Spicy Shawarma', 'Chicken, harissa, garlic.', 690, 'chicken, harissa']],
            'traditional' => [['Couscous Royal', 'Couscous with lamb, chicken, vegetables.', 1450, 'semolina, lamb, vegetables'], ['Chorba Frik', 'Traditional soup with frik and herbs.', 420, 'frik, tomato'], ['Rechta Poulet', 'Thin noodles with chicken sauce.', 1250, 'noodles, chicken'], ['Tajine Zitoun', 'Chicken, olives, carrots.', 1180, 'chicken, olives'], ['Dolma', 'Stuffed vegetables in white sauce.', 1100, 'vegetables, meat'], ['Mhadjeb', 'Stuffed semolina flatbread.', 320, 'semolina, tomato'], ['Chakhchoukha', 'Torn flatbread with red sauce.', 1320, 'flatbread, sauce'], ['Kabyle Plate', 'Olive oil, aghrum, vegetables.', 980, 'olive oil, bread'], ['Loubia', 'White bean stew and bread.', 700, 'beans, tomato'], ['Friday Family Couscous', 'Large couscous tray for four.', 3900, 'semolina, meat']],
            'desserts' => [['Millefeuille', 'Crisp pastry, vanilla cream.', 320, 'cream, pastry'], ['Makrout Box', 'Date semolina sweets with honey.', 780, 'dates, honey'], ['Cheesecake Jar', 'Cream cheese, biscuit, berries.', 520, 'cheese, berries'], ['Crepe Nutella', 'Warm crepe with chocolate.', 580, 'chocolate, crepe'], ['Waffle Fruits', 'Belgian waffle with fruit.', 690, 'waffle, fruit'], ['Gelato Cup', 'Two scoops artisan gelato.', 420, 'milk, sugar'], ['Baklava Mix', 'Layered nuts and syrup.', 850, 'nuts, syrup'], ['Tiramisu', 'Coffee mascarpone dessert.', 620, 'coffee, mascarpone'], ['Honey Sweet Box', 'Assorted Algerian sweets.', 1600, 'honey, nuts'], ['Chocolate Fondant', 'Warm chocolate center cake.', 650, 'chocolate']],
        ];

        return array_map(static fn(array $p): array => [$p[0], $p[1], $p[2], $p[3], $img[$cuisine] ?? ''], $base[$cuisine]);
    }

    private static function extrasFor(string $cuisine, int $offset): array
    {
        $common = [
            'pizza' => [['Extra cheese', 160], ['Harissa drizzle', 60], ['Olives', 80]],
            'burger' => [['Cheddar slice', 120], ['Extra patty', 340], ['Sauce cup', 60]],
            'tacos' => [['Cheese sauce', 120], ['Extra meat', 260], ['Spicy sauce', 50]],
            'coffee' => [['Oat milk', 100], ['Vanilla syrup', 70], ['Extra shot', 120]],
            'bakery' => [['Honey cup', 70], ['Jam cup', 80], ['Chocolate dip', 100]],
            'shawarma' => [['Garlic sauce', 70], ['Extra pickles', 50], ['Extra meat', 260]],
            'traditional' => [['Extra bread', 80], ['Olive oil cup', 120], ['Chili sauce', 50]],
            'desserts' => [['Extra chocolate', 100], ['Fruit topping', 120], ['Ice cream scoop', 180]],
        ];
        return array_slice($common[$cuisine] ?? [], $offset % 2, 2);
    }

    private static function reviewText(int $rating): string
    {
        return $rating >= 5 ? 'Excellent packaging, fast delivery, and generous portions.' : 'Very good food, clear tracking, and friendly service.';
    }
}

final class Auth
{
    private static ?Database $db = null;
    private static ?array $user = null;

    public static function boot(Database $db): void
    {
        self::$db = $db;
        self::user();
    }

    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }
        if (!self::$db) {
            return null;
        }
        if (!empty($_SESSION['user_id'])) {
            self::$user = self::$db->fetch('SELECT * FROM users WHERE id = ? AND status = ?', [$_SESSION['user_id'], 'active']);
            return self::$user;
        }
        if (!empty($_COOKIE['dzaireats_remember'])) {
            [$id, $token] = array_pad(explode(':', (string)$_COOKIE['dzaireats_remember'], 2), 2, '');
            if (ctype_digit($id) && $token !== '') {
                $user = self::$db->fetch('SELECT * FROM users WHERE id = ? AND status = ?', [(int)$id, 'active']);
                if ($user && $user['remember_token'] && hash_equals((string)$user['remember_token'], hash('sha256', $token))) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int)$user['id'];
                    self::$user = $user;
                    return self::$user;
                }
            }
        }
        return null;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int)$user['id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function hasRole(array $roles): bool
    {
        $user = self::user();
        return $user && in_array($user['role'], $roles, true);
    }

    public static function requireRole(array $roles): void
    {
        if (!self::hasRole($roles)) {
            Utility::flash('warning', 'Please sign in with an authorized account.');
            Utility::redirect('?page=login');
        }
    }

    public static function login(string $email, string $password, bool $remember): bool
    {
        if (!self::$db) {
            return false;
        }
        $user = self::$db->fetch('SELECT * FROM users WHERE email = ? AND status = ?', [strtolower(trim($email)), 'active']);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['lang'] = $user['preferred_language'] ?: Utility::lang();
        self::$user = $user;
        self::$db->query('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$user['id']]);
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            self::$db->query('UPDATE users SET remember_token = ? WHERE id = ?', [hash('sha256', $token), $user['id']]);
            setcookie('dzaireats_remember', $user['id'] . ':' . $token, [
                'expires' => time() + 60 * 60 * 24 * 30,
                'path' => '/',
                'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        Logger::audit(self::$db, 'login', 'users', (string)$user['id']);
        return true;
    }

    public static function register(array $data): int
    {
        if (!self::$db) {
            throw new RuntimeException('Database unavailable.');
        }
        $name = trim((string)($data['name'] ?? ''));
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $phone = trim((string)($data['phone'] ?? ''));
        $password = (string)($data['password'] ?? '');
        if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($password) < 8) {
            throw new InvalidArgumentException('Please provide a valid name, email, and a password with at least 8 characters.');
        }
        if (self::$db->fetch('SELECT id FROM users WHERE email = ?', [$email])) {
            throw new InvalidArgumentException('This email is already registered.');
        }
        $id = self::$db->insert(
            'INSERT INTO users (role, name, email, phone, password_hash, preferred_language) VALUES (?, ?, ?, ?, ?, ?)',
            ['customer', $name, $email, $phone, password_hash($password, PASSWORD_DEFAULT), Utility::lang()]
        );
        Logger::audit(self::$db, 'register', 'users', (string)$id);
        return $id;
    }

    public static function logout(): void
    {
        if (self::$db && self::id()) {
            self::$db->query('UPDATE users SET remember_token = NULL WHERE id = ?', [self::id()]);
            Logger::audit(self::$db, 'logout', 'users', (string)self::id());
        }
        self::$user = null;
        unset($_SESSION['user_id']);
        setcookie('dzaireats_remember', '', time() - 3600, '/');
    }
}

final class User
{
    public static function all(Database $db, ?string $role = null, int $limit = 50): array
    {
        if ($role) {
            return $db->all('SELECT * FROM users WHERE role = ? ORDER BY created_at DESC LIMIT ' . (int)$limit, [$role]);
        }
        return $db->all('SELECT * FROM users ORDER BY created_at DESC LIMIT ' . (int)$limit);
    }
}

final class Category
{
    public static function all(Database $db): array
    {
        return $db->all('SELECT * FROM categories WHERE active = 1 ORDER BY sort_order, name_en');
    }

    public static function label(array $category): string
    {
        $field = 'name_' . Utility::lang();
        return (string)($category[$field] ?? $category['name_en']);
    }
}

final class Restaurant
{
    public static function list(Database $db, array $filters = [], int $limit = 24, int $offset = 0): array
    {
        $where = ["r.status = 'open'"];
        $params = [];
        if (!empty($filters['category'])) {
            $where[] = 'c.slug = ?';
            $params[] = $filters['category'];
        }
        if (!empty($filters['city'])) {
            $where[] = 'r.city = ?';
            $params[] = $filters['city'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(r.name LIKE ? OR r.description LIKE ? OR r.cuisine_type LIKE ? OR r.city LIKE ?)';
            $term = '%' . $filters['q'] . '%';
            array_push($params, $term, $term, $term, $term);
        }
        $sql = 'SELECT r.*, c.name_en AS category_name FROM restaurants r LEFT JOIN categories c ON c.id = r.category_id WHERE ' . implode(' AND ', $where) . ' ORDER BY r.is_featured DESC, r.rating_avg DESC, r.avg_delivery_time ASC LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
        return $db->all($sql, $params);
    }

    public static function find(Database $db, int $id): ?array
    {
        return $db->fetch('SELECT r.*, c.slug AS category_slug, c.name_en AS category_name FROM restaurants r LEFT JOIN categories c ON c.id = r.category_id WHERE r.id = ?', [$id]);
    }

    public static function ownedByCurrentUser(Database $db): ?array
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }
        if ($user['role'] === 'restaurant_owner') {
            return $db->fetch('SELECT * FROM restaurants WHERE owner_id = ? LIMIT 1', [$user['id']]);
        }
        if ($user['role'] === 'restaurant_staff') {
            return $db->fetch('SELECT r.* FROM restaurants r INNER JOIN restaurant_staff s ON s.restaurant_id = r.id WHERE s.user_id = ? LIMIT 1', [$user['id']]);
        }
        return null;
    }

    public static function cities(Database $db): array
    {
        return $db->all('SELECT DISTINCT city FROM restaurants ORDER BY city');
    }
}

final class Product
{
    public static function find(Database $db, int $id): ?array
    {
        return $db->fetch('SELECT p.*, r.name AS restaurant_name, r.delivery_fee, r.status AS restaurant_status FROM products p INNER JOIN restaurants r ON r.id = p.restaurant_id WHERE p.id = ?', [$id]);
    }

    public static function byRestaurant(Database $db, int $restaurantId, array $filters = []): array
    {
        $where = ['p.restaurant_id = ?'];
        $params = [$restaurantId];
        if (!empty($filters['q'])) {
            $where[] = '(p.name LIKE ? OR p.description LIKE ? OR p.ingredients LIKE ?)';
            $term = '%' . $filters['q'] . '%';
            array_push($params, $term, $term, $term);
        }
        if (!empty($filters['available'])) {
            $where[] = 'p.is_available = 1';
        }
        $sql = 'SELECT p.*, c.name_en AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE ' . implode(' AND ', $where) . ' ORDER BY p.is_featured DESC, p.rating_avg DESC, p.name';
        return $db->all($sql, $params);
    }

    public static function extras(Database $db, int $productId): array
    {
        return $db->all('SELECT * FROM product_extras WHERE product_id = ? ORDER BY price, name', [$productId]);
    }

    public static function related(Database $db, int $productId, int $restaurantId): array
    {
        return $db->all('SELECT * FROM products WHERE restaurant_id = ? AND id <> ? AND is_available = 1 ORDER BY is_featured DESC, rating_avg DESC LIMIT 4', [$restaurantId, $productId]);
    }
}

final class Cart
{
    public static function raw(): array
    {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = ['restaurant_id' => null, 'items' => [], 'coupon_code' => null];
        }
        return $_SESSION['cart'];
    }

    public static function count(): int
    {
        $qty = 0;
        foreach (self::raw()['items'] as $item) {
            $qty += (int)$item['quantity'];
        }
        return $qty;
    }

    public static function add(Database $db, int $productId, int $quantity, array $extraIds = []): void
    {
        $product = Product::find($db, $productId);
        if (!$product || !$product['is_available']) {
            throw new InvalidArgumentException('This product is unavailable.');
        }
        $cart = self::raw();
        if ($cart['restaurant_id'] && (int)$cart['restaurant_id'] !== (int)$product['restaurant_id']) {
            $cart = ['restaurant_id' => (int)$product['restaurant_id'], 'items' => [], 'coupon_code' => null];
        }
        $key = (string)$productId . ':' . implode('-', array_map('intval', $extraIds));
        $cart['restaurant_id'] = (int)$product['restaurant_id'];
        $cart['items'][$key] = [
            'product_id' => $productId,
            'quantity' => min(20, max(1, $quantity + (int)($cart['items'][$key]['quantity'] ?? 0))),
            'extras' => array_values(array_unique(array_map('intval', $extraIds))),
        ];
        $_SESSION['cart'] = $cart;
    }

    public static function update(array $quantities): void
    {
        $cart = self::raw();
        foreach ($cart['items'] as $key => $item) {
            $qty = max(0, min(20, Utility::int($quantities[$key] ?? $item['quantity'])));
            if ($qty === 0) {
                unset($cart['items'][$key]);
            } else {
                $cart['items'][$key]['quantity'] = $qty;
            }
        }
        if (!$cart['items']) {
            $cart = ['restaurant_id' => null, 'items' => [], 'coupon_code' => null];
        }
        $_SESSION['cart'] = $cart;
    }

    public static function applyCoupon(?string $code): void
    {
        $cart = self::raw();
        $cart['coupon_code'] = $code ? strtoupper(trim($code)) : null;
        $_SESSION['cart'] = $cart;
    }

    public static function clear(): void
    {
        unset($_SESSION['cart']);
    }

    public static function items(Database $db): array
    {
        $items = [];
        foreach (self::raw()['items'] as $key => $item) {
            $product = Product::find($db, (int)$item['product_id']);
            if (!$product) {
                continue;
            }
            $extras = [];
            $extrasTotal = 0.0;
            if (!empty($item['extras'])) {
                $placeholders = implode(',', array_fill(0, count($item['extras']), '?'));
                $extras = $db->all('SELECT * FROM product_extras WHERE id IN (' . $placeholders . ') AND product_id = ?', array_merge($item['extras'], [$product['id']]));
                foreach ($extras as $extra) {
                    $extrasTotal += (float)$extra['price'];
                }
            }
            $unit = (float)($product['discount_price'] ?: $product['price']);
            $quantity = (int)$item['quantity'];
            $items[] = [
                'key' => $key,
                'product' => $product,
                'quantity' => $quantity,
                'extras' => $extras,
                'unit_price' => $unit,
                'extras_total' => $extrasTotal,
                'line_total' => ($unit + $extrasTotal) * $quantity,
            ];
        }
        return $items;
    }

    public static function totals(Database $db, string $deliveryType = 'delivery'): array
    {
        $cart = self::raw();
        $items = self::items($db);
        $subtotal = array_reduce($items, static fn(float $sum, array $item): float => $sum + (float)$item['line_total'], 0.0);
        $restaurant = $cart['restaurant_id'] ? Restaurant::find($db, (int)$cart['restaurant_id']) : null;
        $deliveryFee = ($deliveryType === 'delivery' && $restaurant) ? (float)$restaurant['delivery_fee'] : 0.0;
        $coupon = $cart['coupon_code'] ? Coupon::findValid($db, $cart['coupon_code'], $subtotal, $cart['restaurant_id'] ? (int)$cart['restaurant_id'] : null) : null;
        $discount = $coupon ? Coupon::discountAmount($coupon, $subtotal) : 0.0;
        $tax = round(max(0, $subtotal - $discount) * 0.09, 2);
        return [
            'items' => $items,
            'restaurant' => $restaurant,
            'subtotal' => $subtotal,
            'coupon' => $coupon,
            'discount' => $discount,
            'delivery_fee' => $deliveryFee,
            'tax' => $tax,
            'total' => max(0, $subtotal - $discount + $deliveryFee + $tax),
        ];
    }
}

final class Coupon
{
    public static function findValid(Database $db, string $code, float $subtotal, ?int $restaurantId): ?array
    {
        $coupon = $db->fetch(
            "SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW()) AND (usage_limit IS NULL OR used_count < usage_limit)",
            [strtoupper(trim($code))]
        );
        if (!$coupon) {
            return null;
        }
        if ((float)$coupon['min_order'] > $subtotal) {
            return null;
        }
        if ($coupon['restaurant_id'] && $restaurantId && (int)$coupon['restaurant_id'] !== $restaurantId) {
            return null;
        }
        return $coupon;
    }

    public static function discountAmount(array $coupon, float $subtotal): float
    {
        $discount = $coupon['type'] === 'percentage' ? ($subtotal * ((float)$coupon['value'] / 100)) : (float)$coupon['value'];
        if ($coupon['max_discount'] !== null) {
            $discount = min($discount, (float)$coupon['max_discount']);
        }
        return min($subtotal, round($discount, 2));
    }
}

final class Order
{
    public static function statuses(): array
    {
        return ['pending', 'accepted', 'preparing', 'ready', 'assigned_driver', 'picked_up', 'delivered', 'cancelled', 'rejected'];
    }

    public static function place(Database $db, array $data): int
    {
        if (!Auth::check()) {
            throw new RuntimeException('Please log in before checkout.');
        }
        $deliveryType = in_array($data['delivery_type'] ?? 'delivery', ['delivery', 'pickup', 'dinein'], true) ? $data['delivery_type'] : 'delivery';
        $totals = Cart::totals($db, $deliveryType);
        if (!$totals['items'] || !$totals['restaurant']) {
            throw new RuntimeException('Your cart is empty.');
        }
        $restaurant = $totals['restaurant'];
        $driver = $deliveryType === 'delivery' ? Delivery::nearestDriver($db, $restaurant) : null;
        $status = $driver ? 'assigned_driver' : 'pending';
        $db->pdo()->beginTransaction();
        try {
            $couponId = $totals['coupon']['id'] ?? null;
            $orderNumber = 'DZ' . date('ymdHis') . random_int(100, 999);
            $orderId = $db->insert(
                'INSERT INTO orders (order_number, user_id, restaurant_id, driver_id, coupon_id, delivery_type, status, customer_name, customer_email, customer_phone, delivery_address, table_number, subtotal, discount_total, delivery_fee, tax_total, total, payment_method, payment_status, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $orderNumber,
                    Auth::id(),
                    $restaurant['id'],
                    $driver['id'] ?? null,
                    $couponId,
                    $deliveryType,
                    $status,
                    trim((string)$data['customer_name']),
                    trim((string)$data['customer_email']),
                    trim((string)$data['customer_phone']),
                    $deliveryType === 'delivery' ? trim((string)$data['delivery_address']) : null,
                    $deliveryType === 'dinein' ? trim((string)($data['table_number'] ?? '')) : null,
                    $totals['subtotal'],
                    $totals['discount'],
                    $totals['delivery_fee'],
                    $totals['tax'],
                    $totals['total'],
                    in_array($data['payment_method'] ?? 'cash', ['cash', 'card', 'wallet'], true) ? $data['payment_method'] : 'cash',
                    ($data['payment_method'] ?? 'cash') === 'cash' ? 'pending' : 'paid',
                    trim((string)($data['notes'] ?? '')),
                ]
            );
            foreach ($totals['items'] as $item) {
                $db->insert(
                    'INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, extras_total, extras_json, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $orderId,
                        $item['product']['id'],
                        $item['product']['name'],
                        $item['unit_price'],
                        $item['quantity'],
                        $item['extras_total'],
                        json_encode($item['extras'], JSON_UNESCAPED_UNICODE),
                        $item['line_total'],
                    ]
                );
            }
            Payment::create($db, $orderId, (string)$data['payment_method'], (float)$totals['total']);
            if ($driver) {
                $db->insert('INSERT INTO deliveries (order_id, driver_id, status, pickup_address, dropoff_address, distance_km, assigned_at) VALUES (?, ?, ?, ?, ?, ?, NOW())', [$orderId, $driver['id'], 'assigned', $restaurant['address'], $data['delivery_address'], Delivery::distanceEstimate($restaurant, $driver)]);
                $db->query("UPDATE drivers SET status = 'busy' WHERE id = ?", [$driver['id']]);
                Notification::create($db, (int)$driver['user_id'], 'New delivery assigned', 'A delivery from ' . $restaurant['name'] . ' is ready in your driver dashboard.', 'delivery', ['order_id' => $orderId]);
            }
            if ($couponId) {
                $db->query('UPDATE coupons SET used_count = used_count + 1 WHERE id = ?', [$couponId]);
            }
            Notification::create($db, Auth::id(), 'Order received', 'Your order ' . $orderNumber . ' is now ' . str_replace('_', ' ', $status) . '.', 'order', ['order_id' => $orderId]);
            Notification::create($db, (int)$restaurant['owner_id'], 'New order', 'Order ' . $orderNumber . ' needs restaurant attention.', 'order', ['order_id' => $orderId]);
            Logger::audit($db, 'place_order', 'orders', (string)$orderId, ['total' => $totals['total']]);
            $db->pdo()->commit();
            Cart::clear();
            return $orderId;
        } catch (Throwable $e) {
            $db->pdo()->rollBack();
            throw $e;
        }
    }

    public static function find(Database $db, int $id): ?array
    {
        return $db->fetch('SELECT o.*, r.name AS restaurant_name, r.logo AS restaurant_logo, r.address AS restaurant_address, d.user_id AS driver_user_id, du.name AS driver_name FROM orders o INNER JOIN restaurants r ON r.id = o.restaurant_id LEFT JOIN drivers d ON d.id = o.driver_id LEFT JOIN users du ON du.id = d.user_id WHERE o.id = ?', [$id]);
    }

    public static function items(Database $db, int $orderId): array
    {
        return $db->all('SELECT * FROM order_items WHERE order_id = ?', [$orderId]);
    }

    public static function forCurrentUser(Database $db): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }
        if ($user['role'] === 'admin') {
            return $db->all('SELECT o.*, r.name AS restaurant_name FROM orders o INNER JOIN restaurants r ON r.id = o.restaurant_id ORDER BY o.created_at DESC LIMIT 80');
        }
        if (in_array($user['role'], ['restaurant_owner', 'restaurant_staff'], true)) {
            $restaurant = Restaurant::ownedByCurrentUser($db);
            return $restaurant ? $db->all('SELECT o.*, r.name AS restaurant_name FROM orders o INNER JOIN restaurants r ON r.id = o.restaurant_id WHERE o.restaurant_id = ? ORDER BY o.created_at DESC LIMIT 80', [$restaurant['id']]) : [];
        }
        if ($user['role'] === 'driver') {
            return $db->all('SELECT o.*, r.name AS restaurant_name FROM orders o INNER JOIN restaurants r ON r.id = o.restaurant_id INNER JOIN drivers d ON d.id = o.driver_id WHERE d.user_id = ? ORDER BY o.created_at DESC LIMIT 80', [$user['id']]);
        }
        return $db->all('SELECT o.*, r.name AS restaurant_name FROM orders o INNER JOIN restaurants r ON r.id = o.restaurant_id WHERE o.user_id = ? ORDER BY o.created_at DESC LIMIT 80', [$user['id']]);
    }

    public static function updateStatus(Database $db, int $orderId, string $status): void
    {
        if (!in_array($status, self::statuses(), true)) {
            throw new InvalidArgumentException('Invalid order status.');
        }
        $order = self::find($db, $orderId);
        if (!$order) {
            throw new RuntimeException('Order not found.');
        }
        $db->query('UPDATE orders SET status = ? WHERE id = ?', [$status, $orderId]);
        if (in_array($status, ['picked_up', 'delivered'], true)) {
            $field = $status === 'picked_up' ? 'picked_up_at' : 'delivered_at';
            $deliveryStatus = $status === 'picked_up' ? 'picked_up' : 'delivered';
            $db->query("UPDATE deliveries SET status = ?, {$field} = NOW() WHERE order_id = ?", [$deliveryStatus, $orderId]);
        }
        if ($status === 'delivered' && $order['driver_id']) {
            $db->query("UPDATE drivers SET status = 'online', total_deliveries = total_deliveries + 1, earnings_balance = earnings_balance + ? WHERE id = ?", [max(120, ((float)$order['delivery_fee'] * 0.75)), $order['driver_id']]);
        }
        Notification::create($db, (int)$order['user_id'], 'Order update', 'Order ' . $order['order_number'] . ' is now ' . str_replace('_', ' ', $status) . '.', 'order', ['order_id' => $orderId]);
        Logger::audit($db, 'update_order_status', 'orders', (string)$orderId, ['status' => $status]);
    }
}

final class Delivery
{
    public static function nearestDriver(Database $db, array $restaurant): ?array
    {
        return $db->fetch(
            "SELECT d.*, u.name, u.phone, u.email, u.id AS user_id,
                    POW(COALESCE(d.current_lat, 0) - COALESCE(?, 0), 2) + POW(COALESCE(d.current_lng, 0) - COALESCE(?, 0), 2) AS distance_score
             FROM drivers d INNER JOIN users u ON u.id = d.user_id
             WHERE d.status = 'online'
             ORDER BY distance_score ASC, d.rating_avg DESC
             LIMIT 1",
            [$restaurant['latitude'], $restaurant['longitude']]
        );
    }

    public static function distanceEstimate(array $restaurant, array $driver): float
    {
        $lat = abs((float)$restaurant['latitude'] - (float)$driver['current_lat']);
        $lng = abs((float)$restaurant['longitude'] - (float)$driver['current_lng']);
        return round(max(1.2, sqrt(($lat * $lat) + ($lng * $lng)) * 111), 2);
    }

    public static function currentForDriver(Database $db): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }
        return $db->all("SELECT del.*, o.order_number, o.status AS order_status, o.total, o.delivery_address, r.name AS restaurant_name, r.address AS restaurant_address
            FROM deliveries del
            INNER JOIN orders o ON o.id = del.order_id
            INNER JOIN restaurants r ON r.id = o.restaurant_id
            INNER JOIN drivers d ON d.id = del.driver_id
            WHERE d.user_id = ? ORDER BY del.created_at DESC LIMIT 40", [$user['id']]);
    }
}

final class Payment
{
    public static function create(Database $db, int $orderId, string $method, float $amount): void
    {
        $paid = $method === 'cash' ? 'pending' : 'paid';
        $db->insert(
            'INSERT INTO payments (order_id, provider, method, amount, status, transaction_reference, paid_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$orderId, $method === 'cash' ? 'cash' : 'demo-gateway', $method, $amount, $paid, 'TX-' . strtoupper(bin2hex(random_bytes(5))), $paid === 'paid' ? date('Y-m-d H:i:s') : null]
        );
    }
}

final class Review
{
    public static function create(Database $db, array $data): void
    {
        if (!Auth::check()) {
            throw new RuntimeException('Please log in to review.');
        }
        $rating = max(1, min(5, Utility::int($data['rating'] ?? 5, 5)));
        $restaurantId = Utility::int($data['restaurant_id'] ?? 0);
        $productId = Utility::int($data['product_id'] ?? 0);
        if (!$restaurantId && !$productId) {
            throw new InvalidArgumentException('Select an item to review.');
        }
        $db->insert('INSERT INTO reviews (user_id, restaurant_id, product_id, order_id, rating, comment, status) VALUES (?, ?, ?, ?, ?, ?, ?)', [Auth::id(), $restaurantId ?: null, $productId ?: null, Utility::int($data['order_id'] ?? 0) ?: null, $rating, trim((string)($data['comment'] ?? '')), 'approved']);
        self::recalculate($db, $restaurantId ?: null, $productId ?: null);
    }

    public static function recalculate(Database $db, ?int $restaurantId, ?int $productId): void
    {
        if ($restaurantId) {
            $db->query("UPDATE restaurants SET rating_avg = COALESCE((SELECT ROUND(AVG(rating), 2) FROM reviews WHERE restaurant_id = ? AND status = 'approved'), 0), rating_count = COALESCE((SELECT COUNT(*) FROM reviews WHERE restaurant_id = ? AND status = 'approved'), 0) WHERE id = ?", [$restaurantId, $restaurantId, $restaurantId]);
        }
        if ($productId) {
            $db->query("UPDATE products SET rating_avg = COALESCE((SELECT ROUND(AVG(rating), 2) FROM reviews WHERE product_id = ? AND status = 'approved'), 0), rating_count = COALESCE((SELECT COUNT(*) FROM reviews WHERE product_id = ? AND status = 'approved'), 0) WHERE id = ?", [$productId, $productId, $productId]);
        }
    }
}

final class Notification
{
    public static function create(Database $db, ?int $userId, string $title, string $body, string $type = 'info', array $data = []): void
    {
        $db->insert('INSERT INTO notifications (user_id, title, body, type, data) VALUES (?, ?, ?, ?, ?)', [$userId, $title, $body, $type, json_encode($data, JSON_UNESCAPED_UNICODE)]);
    }

    public static function unread(Database $db): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }
        return $db->all('SELECT * FROM notifications WHERE (user_id = ? OR role_target = ?) AND is_read = 0 ORDER BY created_at DESC LIMIT 8', [$user['id'], $user['role']]);
    }

    public static function markRead(Database $db): void
    {
        $user = Auth::user();
        if ($user) {
            $db->query('UPDATE notifications SET is_read = 1 WHERE user_id = ? OR role_target = ?', [$user['id'], $user['role']]);
        }
    }
}

final class Analytics
{
    public static function summary(Database $db, ?int $restaurantId = null): array
    {
        $where = $restaurantId ? 'WHERE restaurant_id = ' . (int)$restaurantId : '';
        $orders = $db->fetch("SELECT COUNT(*) AS orders_count, COALESCE(SUM(total), 0) AS revenue, COALESCE(AVG(total), 0) AS avg_order FROM orders {$where}");
        $activeCustomers = $db->fetch("SELECT COUNT(DISTINCT user_id) AS c FROM orders {$where}");
        $restaurants = $db->fetch("SELECT COUNT(*) AS c FROM restaurants WHERE status = 'open'");
        $drivers = $db->fetch("SELECT COUNT(*) AS c FROM drivers WHERE status = 'online'");
        return [
            'orders' => (int)($orders['orders_count'] ?? 0),
            'revenue' => (float)($orders['revenue'] ?? 0),
            'avg_order' => (float)($orders['avg_order'] ?? 0),
            'customers' => (int)($activeCustomers['c'] ?? 0),
            'restaurants' => (int)($restaurants['c'] ?? 0),
            'drivers' => (int)($drivers['c'] ?? 0),
        ];
    }

    public static function revenueSeries(Database $db, ?int $restaurantId = null): array
    {
        $params = [];
        $where = '';
        if ($restaurantId) {
            $where = 'AND restaurant_id = ?';
            $params[] = $restaurantId;
        }
        return $db->all("SELECT DATE(created_at) AS day, COUNT(*) AS orders_count, COALESCE(SUM(total), 0) AS revenue FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) {$where} GROUP BY DATE(created_at) ORDER BY day", $params);
    }

    public static function topRestaurants(Database $db): array
    {
        return $db->all("SELECT r.name, r.city, COUNT(o.id) AS orders_count, COALESCE(SUM(o.total), 0) AS revenue FROM restaurants r LEFT JOIN orders o ON o.restaurant_id = r.id GROUP BY r.id ORDER BY revenue DESC LIMIT 8");
    }

    public static function topProducts(Database $db, ?int $restaurantId = null): array
    {
        $params = [];
        $where = '';
        if ($restaurantId) {
            $where = 'WHERE p.restaurant_id = ?';
            $params[] = $restaurantId;
        }
        return $db->all("SELECT p.name, r.name AS restaurant_name, SUM(oi.quantity) AS sold, COALESCE(SUM(oi.total), 0) AS revenue FROM products p INNER JOIN restaurants r ON r.id = p.restaurant_id LEFT JOIN order_items oi ON oi.product_id = p.id {$where} GROUP BY p.id ORDER BY sold DESC, revenue DESC LIMIT 8", $params);
    }
}

final class Admin
{
    public static function settings(Database $db): array
    {
        return $db->all('SELECT * FROM settings ORDER BY `key`');
    }
}

final class Logger
{
    public static function audit(Database $db, string $action, ?string $entityType = null, ?string $entityId = null, array $meta = []): void
    {
        $db->insert(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address, user_agent, metadata) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [Auth::id(), $action, $entityType, $entityId, $_SERVER['REMOTE_ADDR'] ?? 'cli', substr($_SERVER['HTTP_USER_AGENT'] ?? 'cli', 0, 250), json_encode($meta, JSON_UNESCAPED_UNICODE)]
        );
    }
}

final class ErrorHandler
{
    public static function render(Throwable $e): void
    {
        http_response_code(500);
        $message = Config::debug() ? $e->getMessage() : 'Something went wrong. Please try again.';
        echo '<!doctype html><meta charset="utf-8"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><div class="container py-5"><div class="alert alert-danger shadow-sm"><h1 class="h4">Application error</h1><p>' . e($message) . '</p></div></div>';
        exit;
    }
}

final class Router
{
    public static function dispatch(Database $db, string $page): array
    {
        return match ($page) {
            'restaurants' => ['title' => t('restaurants'), 'content' => View::restaurants($db)],
            'restaurant' => ['title' => t('restaurant'), 'content' => View::restaurant($db, Utility::int($_GET['id'] ?? 0))],
            'product' => ['title' => 'Product', 'content' => View::product($db, Utility::int($_GET['id'] ?? 0))],
            'cart' => ['title' => t('cart'), 'content' => View::cart($db)],
            'checkout' => ['title' => t('checkout'), 'content' => View::checkout($db)],
            'orders' => ['title' => t('orders'), 'content' => View::orders($db)],
            'order' => ['title' => t('orders'), 'content' => View::orderDetail($db, Utility::int($_GET['id'] ?? 0))],
            'dashboard' => ['title' => t('dashboard'), 'content' => View::dashboard($db)],
            'login' => ['title' => t('login'), 'content' => View::auth('login')],
            'register' => ['title' => t('register'), 'content' => View::auth('register')],
            'forgot' => ['title' => t('forgot_password'), 'content' => View::auth('forgot')],
            'profile' => ['title' => t('profile'), 'content' => View::profile($db)],
            'health' => ['title' => 'System health', 'content' => View::health($db)],
            default => ['title' => Config::APP_NAME, 'content' => View::home($db)],
        };
    }
}

final class View
{
    public static function render(Database $db, string $title, string $content): void
    {
        $lang = Utility::lang();
        $rtl = Utility::rtl();
        $bootstrap = $rtl ? 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css' : 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css';
        $user = Auth::user();
        $notifications = Notification::unread($db);
        $cartCount = Cart::count();
        $flashes = Utility::flashes();
        $navUser = $user ? '<a class="nav-link" href="?page=dashboard"><i class="bi bi-grid-1x2"></i> ' . e(t('dashboard')) . '</a>' : '<a class="nav-link" href="?page=login"><i class="bi bi-person"></i> ' . e(t('login')) . '</a>';
        $logout = $user ? '<form method="post" class="d-inline">' . Utility::csrfField() . '<input type="hidden" name="action" value="logout"><button class="dropdown-item" type="submit">' . e(t('logout')) . '</button></form>' : '<a class="dropdown-item" href="?page=register">' . e(t('register')) . '</a>';
        $flashHtml = '';
        foreach ($flashes as $flash) {
            $flashHtml .= '<div class="alert alert-' . e($flash['type']) . ' alert-dismissible fade show rounded-4 shadow-soft" role="alert">' . e($flash['message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
        $notifItems = $notifications ? '' : '<li><span class="dropdown-item-text text-muted small">No unread notifications</span></li>';
        foreach ($notifications as $note) {
            $notifItems .= '<li><a class="dropdown-item" href="?page=orders"><strong>' . e($note['title']) . '</strong><br><span class="small text-muted">' . e($note['body']) . '</span></a></li>';
        }
        $dirClass = $rtl ? 'rtl' : 'ltr';
        $homeLabel = e(t('home'));
        $restaurantsLabel = e(t('restaurants'));
        $ordersLabel = e(t('orders'));
        $notificationCount = count($notifications);
        $accountLabel = $user ? e($user['name']) : e(t('register'));
        $profileLabel = e(t('profile'));
        echo <<<HTML
<!doctype html>
<html lang="{$lang}" dir="{$dirClass}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$title} - DzirEats</title>
    <link href="{$bootstrap}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --ink:#121212; --muted:#69717d; --surface:#ffffff; --wash:#f5f7f4; --green:#20a66a; --lime:#d6f85a; --amber:#ffb703; --rose:#ff5a5f; --line:#e7ebdf; }
        body { font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Arial, sans-serif; background: linear-gradient(180deg,#fbfcf8 0%,#f4f7ef 100%); color: var(--ink); letter-spacing:0; }
        a { color: inherit; text-decoration: none; }
        .navbar { backdrop-filter: blur(18px); background: rgba(255,255,255,.86); border-bottom:1px solid rgba(18,18,18,.06); }
        .brand-mark { width:38px; height:38px; border-radius:12px; background:#121212; color:#d6f85a; display:grid; place-items:center; }
        .hero { min-height: calc(100vh - 86px); display:flex; align-items:center; position:relative; overflow:hidden; background: linear-gradient(90deg, rgba(18,18,18,.80), rgba(18,18,18,.30)), url('https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=2200&q=80') center/cover; color:#fff; }
        .hero:after { content:""; position:absolute; inset:auto 0 0; height:120px; background:linear-gradient(180deg, transparent, #f4f7ef); }
        .hero-content { position:relative; z-index:1; padding:clamp(3rem,8vw,7rem) 0 9rem; }
        .hero h1 { font-size:clamp(2.7rem,8vw,6.7rem); line-height:.95; max-width:920px; letter-spacing:0; }
        .hero-copy { max-width:680px; color:rgba(255,255,255,.86); font-size:1.12rem; }
        .search-shell { background:#fff; color:#121212; border-radius:22px; padding:.55rem; box-shadow:0 22px 60px rgba(0,0,0,.25); max-width:780px; }
        .shadow-soft { box-shadow:0 16px 44px rgba(22,34,20,.08); }
        .soft-card { background:var(--surface); border:1px solid var(--line); border-radius:8px; box-shadow:0 12px 35px rgba(22,34,20,.07); }
        .hover-lift { transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease; }
        .hover-lift:hover { transform:translateY(-3px); box-shadow:0 18px 52px rgba(22,34,20,.13); border-color:#d7dfce; }
        .btn-brand { --bs-btn-bg:var(--green); --bs-btn-border-color:var(--green); --bs-btn-hover-bg:#168955; --bs-btn-hover-border-color:#168955; --bs-btn-color:#fff; --bs-btn-hover-color:#fff; border-radius:999px; font-weight:700; }
        .btn-dark-pill { border-radius:999px; font-weight:700; }
        .btn-icon { width:42px; height:42px; border-radius:999px; display:inline-grid; place-items:center; }
        .section-pad { padding:clamp(2.5rem,6vw,5.5rem) 0; }
        .restaurant-cover { aspect-ratio: 16/9; width:100%; object-fit:cover; border-radius:8px; }
        .product-img { aspect-ratio: 4/3; width:100%; object-fit:cover; border-radius:8px 8px 0 0; }
        .category-tile { min-height:130px; background-size:cover; background-position:center; color:#fff; border-radius:8px; overflow:hidden; position:relative; }
        .category-tile:before { content:""; position:absolute; inset:0; background:linear-gradient(180deg, rgba(0,0,0,.10), rgba(0,0,0,.62)); }
        .category-tile > * { position:relative; z-index:1; }
        .stat-number { font-size:clamp(1.8rem,4vw,3rem); font-weight:800; }
        .badge-status { border-radius:999px; padding:.45rem .7rem; text-transform:capitalize; }
        .timeline { display:grid; grid-template-columns:repeat(9, minmax(80px,1fr)); gap:.45rem; overflow:auto; }
        .timeline-step { border-radius:8px; background:#ecf1e6; padding:.7rem; font-size:.78rem; min-height:72px; border:1px solid #dce5d4; }
        .timeline-step.active { background:#121212; color:#fff; border-color:#121212; }
        .admin-grid { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:1rem; }
        .cart-drawer { position:fixed; top:0; bottom:0; right:0; width:min(440px,100vw); background:#fff; z-index:1055; transform:translateX(105%); transition:transform .22s ease; box-shadow:-24px 0 60px rgba(0,0,0,.18); overflow:auto; }
        [dir="rtl"] .cart-drawer { right:auto; left:0; transform:translateX(-105%); }
        .cart-drawer.open { transform:translateX(0); }
        .drawer-backdrop { position:fixed; inset:0; background:rgba(0,0,0,.28); z-index:1050; display:none; }
        .drawer-backdrop.open { display:block; }
        .mini-chart { height:170px; display:flex; align-items:end; gap:.5rem; border-bottom:1px solid var(--line); padding-top:1rem; }
        .mini-chart span { flex:1; min-width:14px; background:linear-gradient(180deg,var(--green),#9bd96d); border-radius:6px 6px 0 0; }
        .form-control, .form-select { border-radius:8px; border-color:#dfe7d8; padding:.78rem .9rem; }
        .nav-pills .nav-link { border-radius:999px; }
        .table { --bs-table-bg:transparent; }
        footer { background:#111; color:#d7d7d7; }
        @media (max-width: 992px) { .admin-grid { grid-template-columns:repeat(2, minmax(0,1fr)); } .hero { min-height:auto; } .hero-content { padding-bottom:7rem; } }
        @media (max-width: 576px) { .admin-grid { grid-template-columns:1fr; } .search-shell { border-radius:18px; } .timeline { grid-template-columns:repeat(9, 120px); } }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-black" href="?page=home">
            <span class="brand-mark"><i class="bi bi-lightning-charge-fill"></i></span><span>DzairEats</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="?page=home">{$homeLabel}</a></li>
                <li class="nav-item"><a class="nav-link" href="?page=restaurants">{$restaurantsLabel}</a></li>
                <li class="nav-item"><a class="nav-link" href="?page=orders">{$ordersLabel}</a></li>
                <li class="nav-item">{$navUser}</li>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <div class="dropdown">
                    <button class="btn btn-light btn-icon position-relative" data-bs-toggle="dropdown" aria-label="Notifications">
                        <i class="bi bi-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{$notificationCount}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-soft rounded-4 p-2" style="min-width:310px">{$notifItems}<li><hr class="dropdown-divider"></li><li><a class="dropdown-item" href="?page=profile&mark_notifications=1">Mark all read</a></li></ul>
                </div>
                <button class="btn btn-dark btn-icon position-relative" type="button" data-cart-open aria-label="Open cart">
                    <i class="bi bi-bag"></i><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">{$cartCount}</span>
                </button>
                <div class="dropdown">
                    <button class="btn btn-outline-dark btn-icon" data-bs-toggle="dropdown" aria-label="Language"><i class="bi bi-translate"></i></button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-soft rounded-4 p-2">
                        <li><a class="dropdown-item" href="?lang=en">English</a></li>
                        <li><a class="dropdown-item" href="?lang=fr">Francais</a></li>
                        <li><a class="dropdown-item" href="?lang=ar">العربية</a></li>
                    </ul>
                </div>
                <div class="dropdown">
                    <button class="btn btn-brand px-3" data-bs-toggle="dropdown">{$accountLabel}</button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-soft rounded-4 p-2">
                        <li><a class="dropdown-item" href="?page=profile">{$profileLabel}</a></li>
                        <li><a class="dropdown-item" href="?page=health">System health</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>{$logout}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>
<main>
    <div class="container position-relative" style="z-index:2">{$flashHtml}</div>
    {$content}
</main>
HTML;
        echo self::cartDrawer($db);
        echo <<<HTML
<footer class="section-pad">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="d-flex align-items-center gap-2 mb-3"><span class="brand-mark"><i class="bi bi-lightning-charge-fill"></i></span><strong class="fs-4 text-white">DzairEats</strong></div>
                <p class="text-white-50">A complete multi-restaurant marketplace for Algeria: delivery, pickup, dine-in, restaurant operations, driver dispatch, and SaaS administration in one PHP file.</p>
            </div>
            <div class="col-md-2"><h6 class="text-white">Marketplace</h6><a class="d-block text-white-50" href="?page=restaurants">Restaurants</a><a class="d-block text-white-50" href="?page=dashboard">Dashboard</a><a class="d-block text-white-50" href="?page=health">Health</a></div>
            <div class="col-md-2"><h6 class="text-white">Demo logins</h6><span class="d-block text-white-50 small">admin@dzaireats.test</span><span class="d-block text-white-50 small">owner1@dzaireats.test</span><span class="d-block text-white-50 small">driver1@dzaireats.test</span></div>
            <div class="col-md-3"><h6 class="text-white">Password</h6><p class="text-white-50 small mb-0">All seeded demo accounts use <strong>password</strong>. Register a customer account for a fresh checkout flow.</p></div>
        </div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('[data-product-search]').forEach(input => {
    input.addEventListener('input', () => {
        const term = input.value.toLowerCase();
        document.querySelectorAll('[data-product-card]').forEach(card => {
            card.style.display = card.dataset.search.includes(term) ? '' : 'none';
        });
    });
});
const drawer = document.querySelector('[data-cart-drawer]');
const backdrop = document.querySelector('[data-cart-backdrop]');
function openCart(){ drawer?.classList.add('open'); backdrop?.classList.add('open'); }
function closeCart(){ drawer?.classList.remove('open'); backdrop?.classList.remove('open'); }
document.querySelectorAll('[data-cart-open]').forEach(btn => btn.addEventListener('click', openCart));
document.querySelectorAll('[data-cart-close]').forEach(btn => btn.addEventListener('click', closeCart));
backdrop?.addEventListener('click', closeCart);
document.querySelectorAll('[data-delivery-type]').forEach(el => el.addEventListener('change', () => {
    document.querySelectorAll('[data-address-block]').forEach(block => block.classList.toggle('d-none', el.value !== 'delivery' || !el.checked));
    document.querySelectorAll('[data-table-block]').forEach(block => block.classList.toggle('d-none', el.value !== 'dinein' || !el.checked));
}));
</script>
</body>
</html>
HTML;
    }

    public static function home(Database $db): string
    {
        $featured = Restaurant::list($db, [], 8);
        $popular = $db->all('SELECT r.*, COUNT(o.id) AS orders_count FROM restaurants r LEFT JOIN orders o ON o.restaurant_id = r.id WHERE r.status = ? GROUP BY r.id ORDER BY orders_count DESC, r.rating_avg DESC LIMIT 8', ['open']);
        $top = $db->all("SELECT * FROM restaurants WHERE status = 'open' ORDER BY rating_avg DESC, rating_count DESC LIMIT 4");
        $categories = Category::all($db);
        $summary = Analytics::summary($db);
        $restaurantsHtml = self::restaurantGrid($featured);
        $popularHtml = self::restaurantGrid($popular);
        $topHtml = self::restaurantGrid($top);
        $categoriesHtml = '';
        foreach ($categories as $cat) {
            $label = Category::label($cat);
            $categoriesHtml .= '<div class="col-6 col-md-3"><a class="category-tile d-flex align-items-end p-3 hover-lift" style="background-image:url(' . e($cat['image_url']) . ')" href="?page=restaurants&category=' . e($cat['slug']) . '"><div><i class="bi ' . e($cat['icon']) . ' fs-3"></i><h3 class="h6 mb-0 mt-2">' . e($label) . '</h3></div></a></div>';
        }
        $stats = [
            ['Restaurants', $summary['restaurants'] . '+', 'Open partners'],
            ['Products', '200+', 'Fresh menu items'],
            ['Drivers', $summary['drivers'] . '+', 'Online couriers'],
            ['Orders', $summary['orders'] . '+', 'Demo transactions'],
        ];
        $statsHtml = '';
        foreach ($stats as $stat) {
            $statsHtml .= '<div class="col-6 col-lg-3"><div class="soft-card p-4 h-100"><div class="stat-number">' . e($stat[1]) . '</div><div class="fw-bold">' . e($stat[0]) . '</div><div class="text-muted small">' . e($stat[2]) . '</div></div></div>';
        }
        $orderNowLabel = e(t('order_now'));
        $categoriesLabel = e(t('categories'));
        $featuredLabel = e(t('featured_restaurants'));
        $promotionsLabel = e(t('promotions'));
        $popularLabel = e(t('popular_restaurants'));
        $topRatedLabel = e(t('top_rated'));
        return <<<HTML
<section class="hero">
    <div class="container hero-content">
        <span class="badge text-bg-light rounded-pill px-3 py-2 mb-3"><i class="bi bi-stars me-1"></i> Multi-restaurant delivery across Algeria</span>
        <h1 class="fw-black mb-4">Food delivery that feels fast, local, and premium.</h1>
        <p class="hero-copy mb-4">Browse Algerian restaurants, compare delivery fees, order in a few clicks, and track every status from kitchen to doorstep.</p>
        <form class="search-shell d-flex gap-2" action="" method="get">
            <input type="hidden" name="page" value="restaurants">
            <div class="input-group input-group-lg border-0">
                <span class="input-group-text bg-white border-0"><i class="bi bi-search"></i></span>
                <input class="form-control border-0" name="q" placeholder="Pizza, tacos, couscous, coffee..." aria-label="Search restaurants">
            </div>
            <button class="btn btn-brand px-4" type="submit">{$orderNowLabel}</button>
        </form>
    </div>
</section>
<section class="section-pad">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4"><div><p class="text-success fw-bold mb-1">Explore</p><h2 class="h1 fw-bold mb-0">{$categoriesLabel}</h2></div><a class="btn btn-outline-dark btn-dark-pill" href="?page=restaurants">All restaurants</a></div>
        <div class="row g-3">{$categoriesHtml}</div>
    </div>
</section>
<section class="section-pad pt-0">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4"><div><p class="text-success fw-bold mb-1">Curated</p><h2 class="h1 fw-bold mb-0">{$featuredLabel}</h2></div><a class="btn btn-brand" href="?page=restaurants">Browse all</a></div>
        {$restaurantsHtml}
    </div>
</section>
<section class="section-pad bg-white">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-5"><p class="text-success fw-bold mb-1">Promotions</p><h2 class="h1 fw-bold">Save on your first Algerian feast.</h2><p class="text-muted">Use <strong>WELCOME20</strong> for 20% off, <strong>DZ500</strong> for a fixed discount, or <strong>COUSCOUS</strong> for traditional dishes.</p><a class="btn btn-dark btn-dark-pill px-4" href="?page=restaurants"><i class="bi bi-ticket-perforated me-1"></i> {$promotionsLabel}</a></div>
            <div class="col-lg-7"><div class="row g-3">{$statsHtml}</div></div>
        </div>
    </div>
</section>
<section class="section-pad">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-6"><h2 class="h1 fw-bold mb-4">{$popularLabel}</h2>{$popularHtml}</div>
            <div class="col-lg-6"><h2 class="h1 fw-bold mb-4">{$topRatedLabel}</h2>{$topHtml}</div>
        </div>
    </div>
</section>
<section class="section-pad bg-white">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-6"><h2 class="h1 fw-bold">Trusted by hungry teams, families, and late-night coders.</h2><p class="text-muted">Clean menus, visible fees, reliable driver assignment, and restaurant tools in the same experience.</p></div>
            <div class="col-lg-6"><div class="soft-card p-4"><div class="d-flex gap-3"><img class="rounded-circle" width="58" height="58" src="https://i.pravatar.cc/120?img=33" alt=""><div><div class="text-warning mb-1">★★★★★</div><p class="mb-1">"DzairEats made ordering couscous for the whole office painless. The timeline is clear and the food arrived hot."</p><strong>Amina, Algiers</strong></div></div></div></div>
        </div>
    </div>
</section>
HTML;
    }

    public static function restaurants(Database $db): string
    {
        $filters = ['q' => trim((string)($_GET['q'] ?? '')), 'category' => trim((string)($_GET['category'] ?? '')), 'city' => trim((string)($_GET['city'] ?? ''))];
        $restaurants = Restaurant::list($db, $filters, 48);
        $categories = Category::all($db);
        $cities = Restaurant::cities($db);
        $categoryOptions = '<option value="">All categories</option>';
        foreach ($categories as $cat) {
            $selected = $filters['category'] === $cat['slug'] ? 'selected' : '';
            $categoryOptions .= '<option value="' . e($cat['slug']) . '" ' . $selected . '>' . e(Category::label($cat)) . '</option>';
        }
        $cityOptions = '<option value="">All cities</option>';
        foreach ($cities as $city) {
            $selected = $filters['city'] === $city['city'] ? 'selected' : '';
            $cityOptions .= '<option value="' . e($city['city']) . '" ' . $selected . '>' . e($city['city']) . '</option>';
        }
        $grid = self::restaurantGrid($restaurants);
        $searchQuery = e($filters['q']);
        return <<<HTML
<section class="section-pad">
    <div class="container">
        <div class="row g-4 align-items-end mb-4">
            <div class="col-lg-6"><p class="text-success fw-bold mb-1">Marketplace</p><h1 class="display-5 fw-bold mb-0">Restaurants near you</h1><p class="text-muted mb-0">Search, filter, compare delivery fees, and open a menu in one tap.</p></div>
            <div class="col-lg-6">
                <form class="soft-card p-3" method="get">
                    <input type="hidden" name="page" value="restaurants">
                    <div class="row g-2">
                        <div class="col-md-5"><input class="form-control" name="q" value="{$searchQuery}" placeholder="Search food or restaurant"></div>
                        <div class="col-md-3"><select class="form-select" name="category">{$categoryOptions}</select></div>
                        <div class="col-md-3"><select class="form-select" name="city">{$cityOptions}</select></div>
                        <div class="col-md-1"><button class="btn btn-brand w-100" aria-label="Search"><i class="bi bi-search"></i></button></div>
                    </div>
                </form>
            </div>
        </div>
        {$grid}
    </div>
</section>
HTML;
    }

    public static function restaurant(Database $db, int $id): string
    {
        $restaurant = Restaurant::find($db, $id);
        if (!$restaurant) {
            return self::emptyState('Restaurant not found', 'This restaurant may be closed or unavailable.');
        }
        $products = Product::byRestaurant($db, $id, ['available' => true]);
        $reviews = $db->all('SELECT rv.*, u.name AS user_name FROM reviews rv LEFT JOIN users u ON u.id = rv.user_id WHERE rv.restaurant_id = ? AND rv.status = ? ORDER BY rv.created_at DESC LIMIT 8', [$id, 'approved']);
        $productHtml = '';
        foreach ($products as $product) {
            $productHtml .= self::productCard($product);
        }
        $reviewHtml = '';
        foreach ($reviews as $review) {
            $reviewHtml .= '<div class="soft-card p-3"><div class="d-flex justify-content-between"><strong>' . e($review['user_name'] ?: 'Customer') . '</strong><span class="text-warning">' . self::stars((float)$review['rating']) . '</span></div><p class="text-muted mb-0 small">' . e($review['comment']) . '</p></div>';
        }
        $cover = e($restaurant['cover_image']);
        $nameAlt = e($restaurant['name']);
        $logo = e($restaurant['logo']);
        $cuisine = e($restaurant['cuisine_type']);
        $name = e($restaurant['name']);
        $description = e($restaurant['description']);
        $rating = e($restaurant['rating_avg']);
        $fee = e(Utility::money($restaurant['delivery_fee']));
        $time = e($restaurant['avg_delivery_time']);
        $address = e($restaurant['address']);
        $city = e($restaurant['city']);
        $hours = e($restaurant['opening_hours']);
        $minimum = e(Utility::money($restaurant['min_order']));
        $productCount = count($products);
        $reviewsLabel = e(t('reviews'));
        $csrf = Utility::csrfField();
        return <<<HTML
<section class="section-pad pb-4">
    <div class="container">
        <img class="restaurant-cover shadow-soft mb-3" src="{$cover}" alt="{$nameAlt}">
        <div class="row g-4 align-items-end">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-3">
                    <img src="{$logo}" class="rounded-4 shadow-soft" width="88" height="88" alt="">
                    <div><p class="text-success fw-bold mb-1">{$cuisine}</p><h1 class="display-5 fw-bold mb-0">{$name}</h1><p class="text-muted mb-0">{$description}</p></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="soft-card p-3">
                    <div class="row g-3 text-center">
                        <div class="col-4"><strong class="d-block"><i class="bi bi-star-fill text-warning"></i> {$rating}</strong><span class="small text-muted">Rating</span></div>
                        <div class="col-4"><strong class="d-block">{$fee}</strong><span class="small text-muted">Fee</span></div>
                        <div class="col-4"><strong class="d-block">{$time}m</strong><span class="small text-muted">ETA</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="section-pad pt-3">
    <div class="container">
        <div class="row g-4">
            <aside class="col-lg-3">
                <div class="soft-card p-3 sticky-lg-top" style="top:92px">
                    <h2 class="h5 fw-bold">Details</h2>
                    <p class="small text-muted mb-2"><i class="bi bi-geo-alt me-1"></i>{$address}, {$city}</p>
                    <p class="small text-muted mb-2"><i class="bi bi-clock me-1"></i>{$hours}</p>
                    <p class="small text-muted"><i class="bi bi-bag-check me-1"></i>Minimum {$minimum}</p>
                    <input class="form-control" data-product-search placeholder="Search this menu">
                </div>
            </aside>
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h3 fw-bold mb-0">Menu</h2><span class="badge text-bg-success rounded-pill">{$productCount} products</span></div>
                <div class="row g-3">{$productHtml}</div>
                <hr class="my-5">
                <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h3 fw-bold mb-0">{$reviewsLabel}</h2><button class="btn btn-outline-dark btn-dark-pill" data-bs-toggle="modal" data-bs-target="#reviewModal">Write review</button></div>
                <div class="row g-3">{$reviewHtml}</div>
            </div>
        </div>
    </div>
</section>
<div class="modal fade" id="reviewModal" tabindex="-1"><div class="modal-dialog"><form method="post" class="modal-content rounded-4">{$csrf}<input type="hidden" name="action" value="review"><input type="hidden" name="restaurant_id" value="{$id}"><div class="modal-header"><h3 class="modal-title h5">Review {$name}</h3><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Rating</label><select class="form-select mb-3" name="rating"><option>5</option><option>4</option><option>3</option><option>2</option><option>1</option></select><label class="form-label">Comment</label><textarea class="form-control" name="comment" rows="4" required></textarea></div><div class="modal-footer"><button class="btn btn-brand">Submit review</button></div></form></div></div>
HTML;
    }

    public static function product(Database $db, int $id): string
    {
        $product = Product::find($db, $id);
        if (!$product) {
            return self::emptyState('Product not found', 'This item is not available.');
        }
        $extras = Product::extras($db, $id);
        $related = Product::related($db, $id, (int)$product['restaurant_id']);
        $extraHtml = '';
        foreach ($extras as $extra) {
            $extraHtml .= '<label class="d-flex justify-content-between align-items-center border rounded-3 p-3 mb-2"><span><input class="form-check-input me-2" type="checkbox" name="extras[]" value="' . e($extra['id']) . '"> ' . e($extra['name']) . '</span><strong>' . e(Utility::money($extra['price'])) . '</strong></label>';
        }
        $price = $product['discount_price'] ? '<span class="fs-3 fw-bold text-success">' . e(Utility::money($product['discount_price'])) . '</span> <span class="text-muted text-decoration-line-through">' . e(Utility::money($product['price'])) . '</span>' : '<span class="fs-3 fw-bold">' . e(Utility::money($product['price'])) . '</span>';
        $relatedHtml = '';
        foreach ($related as $item) {
            $relatedHtml .= self::productCard($item);
        }
        $image = e($product['image_url']);
        $nameAlt = e($product['name']);
        $restaurantName = e($product['restaurant_name']);
        $name = e($product['name']);
        $description = e($product['description']);
        $ingredients = e($product['ingredients']);
        $csrf = Utility::csrfField();
        $addLabel = e(t('add_to_cart'));
        return <<<HTML
<section class="section-pad">
    <div class="container">
        <div class="row g-5 align-items-start">
            <div class="col-lg-6"><img src="{$image}" class="restaurant-cover shadow-soft" alt="{$nameAlt}"></div>
            <div class="col-lg-6">
                <p class="text-success fw-bold mb-1">{$restaurantName}</p>
                <h1 class="display-5 fw-bold">{$name}</h1>
                <p class="text-muted fs-5">{$description}</p>
                <p><strong>Ingredients:</strong> {$ingredients}</p>
                <div class="mb-4">{$price}</div>
                <form method="post" class="soft-card p-4">
                    {$csrf}
                    <input type="hidden" name="action" value="add_cart">
                    <input type="hidden" name="product_id" value="{$id}">
                    <input type="hidden" name="redirect" value="?page=product&id={$id}&cart=1">
                    <label class="form-label fw-bold">Quantity</label>
                    <input class="form-control mb-3" type="number" name="quantity" min="1" max="20" value="1">
                    <h2 class="h6 fw-bold">Extras</h2>
                    {$extraHtml}
                    <button class="btn btn-brand btn-lg w-100 mt-3"><i class="bi bi-bag-plus me-1"></i> {$addLabel}</button>
                </form>
            </div>
        </div>
        <hr class="my-5">
        <h2 class="h3 fw-bold mb-3">Related products</h2>
        <div class="row g-3">{$relatedHtml}</div>
    </div>
</section>
HTML;
    }

    public static function cart(Database $db): string
    {
        return '<section class="section-pad"><div class="container"><h1 class="display-6 fw-bold mb-4">' . e(t('cart')) . '</h1>' . self::cartContents($db, true) . '</div></section>';
    }

    public static function checkout(Database $db): string
    {
        if (!Auth::check()) {
            Utility::flash('warning', 'Please log in or register before checkout.');
            Utility::redirect('?page=login');
        }
        $user = Auth::user();
        $totals = Cart::totals($db);
        if (!$totals['items']) {
            return self::emptyState('Your cart is empty', 'Add a meal before checkout.');
        }
        $summary = self::orderSummary($totals);
        $checkoutLabel = e(t('checkout'));
        $csrf = Utility::csrfField();
        $nameLabel = e(t('name'));
        $phoneLabel = e(t('phone'));
        $emailLabel = e(t('email'));
        $customerName = e($user['name']);
        $customerPhone = e($user['phone']);
        $customerEmail = e($user['email']);
        $deliveryLabel = e(t('delivery'));
        $pickupLabel = e(t('pickup'));
        $dineinLabel = e(t('dinein'));
        $addressLabel = e(t('address'));
        $paymentLabel = e(t('payment'));
        $placeOrderLabel = e(t('place_order'));
        return <<<HTML
<section class="section-pad">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-7">
                <h1 class="display-6 fw-bold mb-4">{$checkoutLabel}</h1>
                <form method="post" class="soft-card p-4">
                    {$csrf}
                    <input type="hidden" name="action" value="place_order">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">{$nameLabel}</label><input class="form-control" name="customer_name" value="{$customerName}" required></div>
                        <div class="col-md-6"><label class="form-label">{$phoneLabel}</label><input class="form-control" name="customer_phone" value="{$customerPhone}" required></div>
                        <div class="col-12"><label class="form-label">{$emailLabel}</label><input class="form-control" type="email" name="customer_email" value="{$customerEmail}" required></div>
                    </div>
                    <hr>
                    <label class="form-label fw-bold">Delivery option</label>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4"><label class="soft-card p-3 w-100"><input class="form-check-input me-2" data-delivery-type type="radio" name="delivery_type" value="delivery" checked> {$deliveryLabel}</label></div>
                        <div class="col-md-4"><label class="soft-card p-3 w-100"><input class="form-check-input me-2" data-delivery-type type="radio" name="delivery_type" value="pickup"> {$pickupLabel}</label></div>
                        <div class="col-md-4"><label class="soft-card p-3 w-100"><input class="form-check-input me-2" data-delivery-type type="radio" name="delivery_type" value="dinein"> {$dineinLabel}</label></div>
                    </div>
                    <div data-address-block><label class="form-label">{$addressLabel}</label><input class="form-control mb-3" name="delivery_address" value="Hydra, Algiers"></div>
                    <div data-table-block class="d-none"><label class="form-label">Table number</label><input class="form-control mb-3" name="table_number"></div>
                    <label class="form-label fw-bold">{$paymentLabel}</label>
                    <select class="form-select mb-3" name="payment_method"><option value="cash">Cash on delivery</option><option value="card">Demo card payment</option><option value="wallet">Wallet</option></select>
                    <label class="form-label">Notes</label><textarea class="form-control mb-4" name="notes" rows="3" placeholder="No onions, call on arrival..."></textarea>
                    <button class="btn btn-brand btn-lg w-100"><i class="bi bi-check2-circle me-1"></i> {$placeOrderLabel}</button>
                </form>
            </div>
            <div class="col-lg-5"><div class="sticky-lg-top" style="top:96px">{$summary}</div></div>
        </div>
    </div>
</section>
HTML;
    }

    public static function orders(Database $db): string
    {
        Auth::requireRole(['customer', 'restaurant_owner', 'restaurant_staff', 'driver', 'admin']);
        $orders = Order::forCurrentUser($db);
        $rows = '';
        foreach ($orders as $order) {
            $rows .= '<tr><td><a class="fw-bold" href="?page=order&id=' . e($order['id']) . '">' . e($order['order_number']) . '</a></td><td>' . e($order['restaurant_name']) . '</td><td>' . self::statusBadge($order['status']) . '</td><td>' . e(Utility::money($order['total'])) . '</td><td>' . e($order['created_at']) . '</td></tr>';
        }
        $ordersLabel = e(t('orders'));
        return <<<HTML
<section class="section-pad">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4"><h1 class="display-6 fw-bold mb-0">{$ordersLabel}</h1><a class="btn btn-brand" href="?page=restaurants">Order again</a></div>
        <div class="soft-card p-3 table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Order</th><th>Restaurant</th><th>Status</th><th>Total</th><th>Date</th></tr></thead><tbody>{$rows}</tbody></table></div>
    </div>
</section>
HTML;
    }

    public static function orderDetail(Database $db, int $id): string
    {
        Auth::requireRole(['customer', 'restaurant_owner', 'restaurant_staff', 'driver', 'admin']);
        $order = Order::find($db, $id);
        if (!$order) {
            return self::emptyState('Order not found', 'This order is unavailable.');
        }
        $items = Order::items($db, $id);
        $itemRows = '';
        foreach ($items as $item) {
            $itemRows .= '<tr><td>' . e($item['product_name']) . '</td><td>' . e($item['quantity']) . '</td><td>' . e(Utility::money($item['unit_price'])) . '</td><td>' . e(Utility::money($item['total'])) . '</td></tr>';
        }
        $statusSteps = '';
        $currentIndex = array_search($order['status'], Order::statuses(), true);
        foreach (Order::statuses() as $i => $status) {
            $statusSteps .= '<div class="timeline-step ' . ($i <= $currentIndex ? 'active' : '') . '"><i class="bi bi-check-circle d-block mb-1"></i>' . e(str_replace('_', ' ', $status)) . '</div>';
        }
        $controls = '';
        if (Auth::hasRole(['restaurant_owner', 'restaurant_staff', 'driver', 'admin'])) {
            $options = '';
            foreach (Order::statuses() as $status) {
                $options .= '<option value="' . e($status) . '" ' . ($status === $order['status'] ? 'selected' : '') . '>' . e(str_replace('_', ' ', $status)) . '</option>';
            }
            $controls = '<form method="post" class="soft-card p-3 d-flex gap-2 align-items-center">' . Utility::csrfField() . '<input type="hidden" name="action" value="order_status"><input type="hidden" name="order_id" value="' . e($id) . '"><select class="form-select" name="status">' . $options . '</select><button class="btn btn-brand">' . e(t('update')) . '</button></form>';
        }
        $restaurantName = e($order['restaurant_name']);
        $orderNumber = e($order['order_number']);
        $statusBadge = self::statusBadge($order['status']);
        $deliveryType = e($order['delivery_type']);
        $driverName = e($order['driver_name'] ?: 'Not assigned');
        $orderTotal = e(Utility::money($order['total']));
        return <<<HTML
<section class="section-pad">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-8">
                <p class="text-success fw-bold mb-1">{$restaurantName}</p>
                <h1 class="display-6 fw-bold">Order {$orderNumber}</h1>
                <div class="timeline my-4">{$statusSteps}</div>
                <div class="soft-card p-3 table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Item</th><th>Qty</th><th>Unit</th><th>Total</th></tr></thead><tbody>{$itemRows}</tbody></table></div>
            </div>
            <div class="col-lg-4">
                <div class="soft-card p-4 mb-3">
                    <h2 class="h5 fw-bold">Summary</h2>
                    <p class="mb-1">Status: {$statusBadge}</p>
                    <p class="mb-1">Type: {$deliveryType}</p>
                    <p class="mb-1">Driver: {$driverName}</p>
                    <hr><div class="d-flex justify-content-between"><span>Total</span><strong>{$orderTotal}</strong></div>
                </div>
                {$controls}
            </div>
        </div>
    </div>
</section>
HTML;
    }

    public static function dashboard(Database $db): string
    {
        $user = Auth::user();
        if (!$user) {
            Utility::redirect('?page=login');
        }
        return match ($user['role']) {
            'admin' => self::adminDashboard($db),
            'restaurant_owner', 'restaurant_staff' => self::restaurantDashboard($db),
            'driver' => self::driverDashboard($db),
            default => self::customerDashboard($db),
        };
    }

    public static function adminDashboard(Database $db): string
    {
        Auth::requireRole(['admin']);
        $summary = Analytics::summary($db);
        $series = Analytics::revenueSeries($db);
        $topRestaurants = Analytics::topRestaurants($db);
        $users = User::all($db, null, 12);
        $audits = $db->all('SELECT a.*, u.name AS user_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC LIMIT 10');
        $settings = Admin::settings($db);
        $cards = self::metricCards($summary);
        $chart = self::chart($series);
        $topRows = '';
        foreach ($topRestaurants as $row) {
            $topRows .= '<tr><td>' . e($row['name']) . '</td><td>' . e($row['city']) . '</td><td>' . e($row['orders_count']) . '</td><td>' . e(Utility::money($row['revenue'])) . '</td></tr>';
        }
        $userRows = '';
        foreach ($users as $row) {
            $userRows .= '<tr><td>' . e($row['name']) . '</td><td>' . e($row['role']) . '</td><td>' . e($row['email']) . '</td><td>' . self::statusBadge($row['status']) . '</td></tr>';
        }
        $settingRows = '';
        foreach ($settings as $setting) {
            $settingRows .= '<tr><td>' . e($setting['key']) . '</td><td>' . e($setting['value']) . '</td></tr>';
        }
        $auditRows = '';
        foreach ($audits as $audit) {
            $auditRows .= '<tr><td>' . e($audit['action']) . '</td><td>' . e($audit['entity_type']) . '</td><td>' . e($audit['user_name'] ?: 'system') . '</td><td>' . e($audit['created_at']) . '</td></tr>';
        }
        $csrf = Utility::csrfField();
        return <<<HTML
<section class="section-pad">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4"><div><p class="text-success fw-bold mb-1">SaaS control center</p><h1 class="display-6 fw-bold mb-0">Admin dashboard</h1></div><a class="btn btn-outline-dark btn-dark-pill" href="?page=health">System health</a></div>
        {$cards}
        <div class="row g-4 mt-1">
            <div class="col-lg-7"><div class="soft-card p-4 h-100"><h2 class="h5 fw-bold">Revenue analytics</h2>{$chart}</div></div>
            <div class="col-lg-5"><div class="soft-card p-4 h-100 table-responsive"><h2 class="h5 fw-bold">Top restaurants</h2><table class="table mb-0"><thead><tr><th>Name</th><th>City</th><th>Orders</th><th>Revenue</th></tr></thead><tbody>{$topRows}</tbody></table></div></div>
            <div class="col-lg-7"><div class="soft-card p-4 table-responsive"><h2 class="h5 fw-bold">User management</h2><table class="table mb-0"><thead><tr><th>Name</th><th>Role</th><th>Email</th><th>Status</th></tr></thead><tbody>{$userRows}</tbody></table></div></div>
            <div class="col-lg-5"><div class="soft-card p-4"><h2 class="h5 fw-bold">Coupon management</h2><form method="post" class="row g-2">{$csrf}<input type="hidden" name="action" value="coupon_save"><div class="col-6"><input class="form-control" name="code" placeholder="CODE" required></div><div class="col-6"><select class="form-select" name="type"><option value="percentage">Percentage</option><option value="fixed">Fixed</option></select></div><div class="col-6"><input class="form-control" name="value" type="number" step="0.01" placeholder="Value" required></div><div class="col-6"><input class="form-control" name="min_order" type="number" step="0.01" placeholder="Min order"></div><div class="col-12"><button class="btn btn-brand w-100">Save coupon</button></div></form></div></div>
            <div class="col-lg-6"><div class="soft-card p-4 table-responsive"><h2 class="h5 fw-bold">System settings</h2><table class="table mb-0"><tbody>{$settingRows}</tbody></table></div></div>
            <div class="col-lg-6"><div class="soft-card p-4 table-responsive"><h2 class="h5 fw-bold">Audit logs</h2><table class="table mb-0"><tbody>{$auditRows}</tbody></table></div></div>
        </div>
    </div>
</section>
HTML;
    }

    public static function restaurantDashboard(Database $db): string
    {
        Auth::requireRole(['restaurant_owner', 'restaurant_staff']);
        $restaurant = Restaurant::ownedByCurrentUser($db);
        if (!$restaurant) {
            return self::emptyState('No restaurant linked', 'Ask an administrator to link this account to a restaurant.');
        }
        $summary = Analytics::summary($db, (int)$restaurant['id']);
        $orders = $db->all('SELECT * FROM orders WHERE restaurant_id = ? ORDER BY created_at DESC LIMIT 12', [$restaurant['id']]);
        $products = Product::byRestaurant($db, (int)$restaurant['id']);
        $reviews = $db->all('SELECT rv.*, u.name AS user_name FROM reviews rv LEFT JOIN users u ON u.id = rv.user_id WHERE rv.restaurant_id = ? ORDER BY rv.created_at DESC LIMIT 8', [$restaurant['id']]);
        $orderRows = '';
        foreach ($orders as $order) {
            $orderRows .= '<tr><td><a href="?page=order&id=' . e($order['id']) . '">' . e($order['order_number']) . '</a></td><td>' . self::statusBadge($order['status']) . '</td><td>' . e(Utility::money($order['total'])) . '</td><td>' . e($order['created_at']) . '</td></tr>';
        }
        $productRows = '';
        foreach (array_slice($products, 0, 12) as $product) {
            $productRows .= '<tr><td>' . e($product['name']) . '</td><td>' . e(Utility::money($product['price'])) . '</td><td>' . ($product['is_available'] ? '<span class="badge text-bg-success">Available</span>' : '<span class="badge text-bg-secondary">Hidden</span>') . '</td><td><a class="btn btn-sm btn-outline-dark" href="?page=product&id=' . e($product['id']) . '">View</a></td></tr>';
        }
        $reviewList = '';
        foreach ($reviews as $review) {
            $reviewList .= '<div class="border-bottom py-2"><div class="d-flex justify-content-between"><strong>' . e($review['user_name'] ?: 'Customer') . '</strong><span class="text-warning">' . self::stars((float)$review['rating']) . '</span></div><p class="small text-muted mb-0">' . e($review['comment']) . '</p></div>';
        }
        $restaurantName = e($restaurant['name']);
        $restaurantId = e($restaurant['id']);
        $cards = self::metricCards($summary);
        $csrf = Utility::csrfField();
        $openingHours = e($restaurant['opening_hours']);
        $deliveryFee = e($restaurant['delivery_fee']);
        return <<<HTML
<section class="section-pad">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4"><div><p class="text-success fw-bold mb-1">Restaurant operations</p><h1 class="display-6 fw-bold mb-0">{$restaurantName}</h1></div><a class="btn btn-brand" href="?page=restaurant&id={$restaurantId}">Open storefront</a></div>
        {$cards}
        <div class="row g-4 mt-1">
            <div class="col-lg-7"><div class="soft-card p-4 table-responsive"><h2 class="h5 fw-bold">Order management</h2><table class="table mb-0"><thead><tr><th>Order</th><th>Status</th><th>Total</th><th>Date</th></tr></thead><tbody>{$orderRows}</tbody></table></div></div>
            <div class="col-lg-5"><div class="soft-card p-4"><h2 class="h5 fw-bold">Restaurant settings</h2><form method="post" class="row g-2">{$csrf}<input type="hidden" name="action" value="restaurant_settings"><input type="hidden" name="restaurant_id" value="{$restaurantId}"><div class="col-12"><input class="form-control" name="opening_hours" value="{$openingHours}"></div><div class="col-6"><input class="form-control" name="delivery_fee" type="number" step="0.01" value="{$deliveryFee}"></div><div class="col-6"><select class="form-select" name="status"><option value="open">Open</option><option value="closed">Closed</option><option value="paused">Paused</option></select></div><div class="col-12"><button class="btn btn-brand w-100">Update restaurant</button></div></form></div></div>
            <div class="col-lg-7"><div class="soft-card p-4 table-responsive"><h2 class="h5 fw-bold">Menu management</h2><table class="table mb-0"><thead><tr><th>Product</th><th>Price</th><th>Status</th><th></th></tr></thead><tbody>{$productRows}</tbody></table></div></div>
            <div class="col-lg-5"><div class="soft-card p-4"><h2 class="h5 fw-bold">Recent reviews</h2>{$reviewList}</div></div>
        </div>
    </div>
</section>
HTML;
    }

    public static function driverDashboard(Database $db): string
    {
        Auth::requireRole(['driver']);
        $user = Auth::user();
        $driver = $db->fetch('SELECT * FROM drivers WHERE user_id = ?', [$user['id']]);
        $deliveries = Delivery::currentForDriver($db);
        $rows = '';
        foreach ($deliveries as $delivery) {
            $rows .= '<tr><td><a href="?page=order&id=' . e($delivery['order_id']) . '">' . e($delivery['order_number']) . '</a></td><td>' . e($delivery['restaurant_name']) . '</td><td>' . self::statusBadge($delivery['status']) . '</td><td>' . e(Utility::money($delivery['total'])) . '</td></tr>';
        }
        $csrf = Utility::csrfField();
        $earnings = e(Utility::money($driver['earnings_balance'] ?? 0));
        $driverStatus = e($driver['status'] ?? 'offline');
        $deliveryCount = e($driver['total_deliveries'] ?? 0);
        return <<<HTML
<section class="section-pad">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4"><div class="soft-card p-4"><h1 class="h3 fw-bold">Driver dashboard</h1><p class="text-muted">Manage availability, assigned deliveries, and earnings.</p><form method="post">{$csrf}<input type="hidden" name="action" value="driver_status"><select class="form-select mb-3" name="status"><option value="online">Online</option><option value="offline">Offline</option><option value="busy">Busy</option></select><button class="btn btn-brand w-100">Update status</button></form><hr><div class="d-flex justify-content-between"><span>Earnings</span><strong>{$earnings}</strong></div><div class="d-flex justify-content-between"><span>Status</span><strong>{$driverStatus}</strong></div><div class="d-flex justify-content-between"><span>Deliveries</span><strong>{$deliveryCount}</strong></div></div></div>
            <div class="col-lg-8"><div class="soft-card p-4 table-responsive"><h2 class="h5 fw-bold">Delivery history</h2><table class="table mb-0"><thead><tr><th>Order</th><th>Pickup</th><th>Status</th><th>Total</th></tr></thead><tbody>{$rows}</tbody></table></div></div>
        </div>
    </div>
</section>
HTML;
    }

    public static function customerDashboard(Database $db): string
    {
        $orders = Order::forCurrentUser($db);
        $restaurants = Restaurant::list($db, [], 4);
        $orderCards = '';
        foreach (array_slice($orders, 0, 4) as $order) {
            $orderCards .= '<div class="soft-card p-3"><div class="d-flex justify-content-between"><strong>' . e($order['order_number']) . '</strong>' . self::statusBadge($order['status']) . '</div><p class="small text-muted mb-1">' . e($order['restaurant_name']) . '</p><a class="btn btn-sm btn-outline-dark" href="?page=order&id=' . e($order['id']) . '">Track</a></div>';
        }
        return '<section class="section-pad"><div class="container"><h1 class="display-6 fw-bold mb-4">Customer dashboard</h1><div class="row g-4"><div class="col-lg-5"><h2 class="h5 fw-bold">Recent orders</h2><div class="d-grid gap-3">' . $orderCards . '</div></div><div class="col-lg-7"><h2 class="h5 fw-bold">Recommended restaurants</h2>' . self::restaurantGrid($restaurants) . '</div></div></div></section>';
    }

    public static function auth(string $mode): string
    {
        $title = $mode === 'register' ? t('register') : ($mode === 'forgot' ? t('forgot_password') : t('login'));
        $fields = '';
        if ($mode === 'register') {
            $fields .= '<label class="form-label">' . e(t('name')) . '</label><input class="form-control mb-3" name="name" required>';
            $fields .= '<label class="form-label">' . e(t('phone')) . '</label><input class="form-control mb-3" name="phone">';
        }
        if ($mode !== 'forgot') {
            $fields .= '<label class="form-label">' . e(t('email')) . '</label><input class="form-control mb-3" type="email" name="email" required>';
            $fields .= '<label class="form-label">' . e(t('password')) . '</label><input class="form-control mb-3" type="password" name="password" required>';
        } else {
            $fields .= '<label class="form-label">' . e(t('email')) . '</label><input class="form-control mb-3" type="email" name="email" required>';
        }
        $remember = $mode === 'login' ? '<label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="remember" value="1"> ' . e(t('remember_me')) . '</label>' : '';
        $links = $mode === 'login' ? '<a href="?page=register">Create account</a><span class="mx-2">·</span><a href="?page=forgot">' . e(t('forgot_password')) . '</a>' : '<a href="?page=login">Already have an account?</a>';
        $heading = e($title);
        $csrf = Utility::csrfField();
        $action = e($mode);
        $button = e($title);
        return <<<HTML
<section class="section-pad">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <div class="soft-card p-4 p-md-5">
                    <h1 class="h2 fw-bold mb-1">{$heading}</h1>
                    <p class="text-muted mb-4">Use demo accounts or create a customer profile.</p>
                    <form method="post">
                        {$csrf}
                        <input type="hidden" name="action" value="{$action}">
                        {$fields}
                        {$remember}
                        <button class="btn btn-brand btn-lg w-100 mb-3">{$button}</button>
                    </form>
                    <div class="small text-center">{$links}</div>
                    <hr><p class="small text-muted mb-0">Demo password: <strong>password</strong>. Try admin@dzaireats.test, owner1@dzaireats.test, driver1@dzaireats.test, customer1@dzaireats.test.</p>
                </div>
            </div>
        </div>
    </div>
</section>
HTML;
    }

    public static function profile(Database $db): string
    {
        if (isset($_GET['mark_notifications'])) {
            Notification::markRead($db);
            Utility::flash('success', 'Notifications marked as read.');
            Utility::redirect('?page=profile');
        }
        Auth::requireRole(['customer', 'restaurant_owner', 'restaurant_staff', 'driver', 'admin']);
        $user = Auth::user();
        $avatar = e($user['avatar_url'] ?: 'https://ui-avatars.com/api/?name=' . rawurlencode($user['name']));
        $name = e($user['name']);
        $role = e($user['role']);
        $phone = e($user['phone']);
        $csrf = Utility::csrfField();
        return <<<HTML
<section class="section-pad">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4"><div class="soft-card p-4 text-center"><img class="rounded-circle shadow-soft mb-3" src="{$avatar}" width="120" height="120" alt=""><h1 class="h3 fw-bold">{$name}</h1><p class="text-muted">{$role}</p></div></div>
            <div class="col-lg-8"><div class="soft-card p-4"><h2 class="h5 fw-bold">Profile settings</h2><form method="post" class="row g-3">{$csrf}<input type="hidden" name="action" value="profile"><div class="col-md-6"><label class="form-label">Name</label><input class="form-control" name="name" value="{$name}"></div><div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="{$phone}"></div><div class="col-md-6"><label class="form-label">Language</label><select class="form-select" name="preferred_language"><option value="en">English</option><option value="fr">Francais</option><option value="ar">العربية</option></select></div><div class="col-md-6"><label class="form-label">New password</label><input class="form-control" type="password" name="password" placeholder="Leave blank"></div><div class="col-12"><button class="btn btn-brand">Save profile</button></div></form></div></div>
        </div>
    </div>
</section>
HTML;
    }

    public static function health(Database $db): string
    {
        Auth::requireRole(['admin', 'restaurant_owner', 'restaurant_staff', 'driver', 'customer']);
        $tables = ['users', 'restaurants', 'restaurant_staff', 'drivers', 'categories', 'products', 'product_images', 'product_extras', 'orders', 'order_items', 'deliveries', 'payments', 'reviews', 'coupons', 'notifications', 'settings', 'audit_logs'];
        $rows = '';
        foreach ($tables as $table) {
            $count = $db->fetch('SELECT COUNT(*) AS c FROM ' . $table);
            $rows .= '<tr><td>' . e($table) . '</td><td><span class="badge text-bg-success">OK</span></td><td>' . e($count['c'] ?? 0) . '</td></tr>';
        }
        $cfg = Config::db();
        $host = e($cfg['host']);
        $databaseName = e($cfg['name']);
        $phpVersion = e(PHP_VERSION);
        $mode = Config::debug() ? 'Debug' : 'Production';
        return <<<HTML
<section class="section-pad">
    <div class="container">
        <h1 class="display-6 fw-bold mb-4">System health checker</h1>
        <div class="row g-4">
            <div class="col-lg-4"><div class="soft-card p-4"><h2 class="h5 fw-bold">Connection</h2><p class="mb-1">Host: {$host}</p><p class="mb-1">Database: {$databaseName}</p><p class="mb-1">PHP: {$phpVersion}</p><p class="mb-0">Mode: {$mode}</p></div></div>
            <div class="col-lg-8"><div class="soft-card p-4 table-responsive"><table class="table mb-0"><thead><tr><th>Table</th><th>Status</th><th>Rows</th></tr></thead><tbody>{$rows}</tbody></table></div></div>
        </div>
    </div>
</section>
HTML;
    }

    public static function installer(?Throwable $error = null, array $old = []): void
    {
        $cfg = Config::db();
        $message = $error ? '<div class="alert alert-danger rounded-4">' . e($error->getMessage()) . '</div>' : '<div class="alert alert-info rounded-4">Enter MySQL credentials. The installer will create the database, tables, indexes, foreign keys, and Algerian demo data automatically.</div>';
        $host = e($old['db_host'] ?? $cfg['host']);
        $port = e($old['db_port'] ?? $cfg['port']);
        $name = e($old['db_name'] ?? $cfg['name']);
        $user = e($old['db_user'] ?? $cfg['user']);
        $csrf = e(Utility::csrfToken());
        echo <<<HTML
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Install DzairEats</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"><style>body{background:#f5f7f4}.card{border-radius:8px;border:1px solid #e3eadb;box-shadow:0 18px 52px rgba(22,34,20,.12)}.btn-brand{background:#20a66a;border-color:#20a66a;color:#fff;border-radius:999px;font-weight:700}</style></head>
<body><main class="container py-5"><div class="row justify-content-center"><div class="col-lg-7"><div class="text-center mb-4"><div class="d-inline-grid place-items-center bg-dark text-white rounded-4 p-3 mb-3"><i class="bi bi-lightning-charge-fill fs-2 text-success"></i></div><h1 class="display-6 fw-bold">Install DzairEats</h1><p class="text-muted">Single-file PHP 8 + MySQL food delivery marketplace.</p></div>{$message}<form method="post" class="card p-4"><input type="hidden" name="_csrf" value="{$csrf}"><input type="hidden" name="action" value="install"><div class="row g-3"><div class="col-md-8"><label class="form-label">DB host</label><input class="form-control" name="db_host" value="{$host}" required></div><div class="col-md-4"><label class="form-label">Port</label><input class="form-control" name="db_port" value="{$port}" required></div><div class="col-12"><label class="form-label">Database name</label><input class="form-control" name="db_name" value="{$name}" required></div><div class="col-md-6"><label class="form-label">DB user</label><input class="form-control" name="db_user" value="{$user}" required></div><div class="col-md-6"><label class="form-label">DB password</label><input class="form-control" type="password" name="db_pass"></div><div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="debug" value="1"> Enable debug mode in this session</label></div><div class="col-12"><button class="btn btn-brand btn-lg w-100">Check connection and install</button></div></div></form><p class="small text-muted mt-3">For permanent credentials without extra files, set environment variables FOOD_DB_HOST, FOOD_DB_PORT, FOOD_DB_NAME, FOOD_DB_USER, and FOOD_DB_PASS.</p></div></div></main></body></html>
HTML;
    }

    private static function restaurantGrid(array $restaurants): string
    {
        if (!$restaurants) {
            return self::emptyState('No restaurants found', 'Try a different search or city.');
        }
        $html = '<div class="row g-3">';
        foreach ($restaurants as $restaurant) {
            $html .= '<div class="col-md-6 col-xl-3"><a class="soft-card hover-lift d-block h-100 overflow-hidden" href="?page=restaurant&id=' . e($restaurant['id']) . '"><img class="product-img" src="' . e($restaurant['cover_image']) . '" alt="' . e($restaurant['name']) . '"><div class="p-3"><div class="d-flex justify-content-between gap-2 mb-2"><h3 class="h6 fw-bold mb-0">' . e($restaurant['name']) . '</h3><span class="badge text-bg-light"><i class="bi bi-star-fill text-warning"></i> ' . e($restaurant['rating_avg']) . '</span></div><p class="text-muted small mb-2">' . e($restaurant['cuisine_type']) . ' · ' . e($restaurant['city']) . '</p><div class="d-flex justify-content-between small"><span><i class="bi bi-clock"></i> ' . e($restaurant['avg_delivery_time']) . ' min</span><span>' . e(Utility::money($restaurant['delivery_fee'])) . '</span></div></div></a></div>';
        }
        return $html . '</div>';
    }

    private static function productCard(array $product): string
    {
        $price = $product['discount_price'] ? '<span class="fw-bold text-success">' . e(Utility::money($product['discount_price'])) . '</span> <span class="small text-muted text-decoration-line-through">' . e(Utility::money($product['price'])) . '</span>' : '<span class="fw-bold">' . e(Utility::money($product['price'])) . '</span>';
        $search = strtolower($product['name'] . ' ' . $product['description'] . ' ' . ($product['ingredients'] ?? ''));
        return '<div class="col-md-6 col-xl-4" data-product-card data-search="' . e($search) . '"><div class="soft-card hover-lift h-100 overflow-hidden"><a href="?page=product&id=' . e($product['id']) . '"><img class="product-img" src="' . e($product['image_url']) . '" alt="' . e($product['name']) . '"></a><div class="p-3"><div class="d-flex justify-content-between gap-2"><h3 class="h6 fw-bold mb-1">' . e($product['name']) . '</h3><span class="text-warning small">' . self::stars((float)$product['rating_avg']) . '</span></div><p class="small text-muted mb-2">' . e(mb_strimwidth($product['description'], 0, 92, '...')) . '</p><div class="d-flex justify-content-between align-items-center"><div>' . $price . '</div><form method="post">' . Utility::csrfField() . '<input type="hidden" name="action" value="add_cart"><input type="hidden" name="product_id" value="' . e($product['id']) . '"><input type="hidden" name="quantity" value="1"><input type="hidden" name="redirect" value="' . e($_SERVER['REQUEST_URI'] ?? '?page=home') . '"><button class="btn btn-sm btn-brand" aria-label="Add to cart"><i class="bi bi-plus-lg"></i></button></form></div></div></div></div>';
    }

    private static function cartDrawer(Database $db): string
    {
        return '<div class="drawer-backdrop" data-cart-backdrop></div><aside class="cart-drawer" data-cart-drawer><div class="p-4 d-flex justify-content-between align-items-center border-bottom"><h2 class="h4 fw-bold mb-0">' . e(t('cart')) . '</h2><button class="btn btn-light btn-icon" data-cart-close aria-label="Close cart"><i class="bi bi-x-lg"></i></button></div><div class="p-4">' . self::cartContents($db, false) . '</div></aside>';
    }

    private static function cartContents(Database $db, bool $full): string
    {
        $totals = Cart::totals($db);
        if (!$totals['items']) {
            return self::emptyState('Your cart is empty', 'Start with a restaurant menu and add your first dish.');
        }
        $itemsHtml = '';
        foreach ($totals['items'] as $item) {
            $extras = '';
            foreach ($item['extras'] as $extra) {
                $extras .= '<span class="badge text-bg-light me-1">' . e($extra['name']) . '</span>';
            }
            $itemsHtml .= '<div class="d-flex gap-3 border-bottom py-3"><img src="' . e($item['product']['image_url']) . '" class="rounded-3" width="72" height="72" style="object-fit:cover" alt=""><div class="flex-grow-1"><div class="d-flex justify-content-between"><strong>' . e($item['product']['name']) . '</strong><strong>' . e(Utility::money($item['line_total'])) . '</strong></div><div class="small text-muted">' . $extras . '</div><input class="form-control form-control-sm mt-2" type="number" min="0" max="20" name="quantities[' . e($item['key']) . ']" value="' . e($item['quantity']) . '"></div></div>';
        }
        $summary = self::orderSummary($totals);
        $checkout = '<a class="btn btn-brand btn-lg w-100 mt-3" href="?page=checkout">' . e(t('checkout')) . '</a>';
        return '<form method="post">' . Utility::csrfField() . '<input type="hidden" name="action" value="cart_update">' . $itemsHtml . '<button class="btn btn-outline-dark w-100 mt-3">' . e(t('update')) . '</button></form><form method="post" class="mt-3 d-flex gap-2">' . Utility::csrfField() . '<input type="hidden" name="action" value="apply_coupon"><input class="form-control" name="coupon_code" placeholder="WELCOME20" value="' . e(Cart::raw()['coupon_code'] ?? '') . '"><button class="btn btn-dark">' . e(t('coupon')) . '</button></form><div class="mt-3">' . $summary . $checkout . '</div>';
    }

    private static function orderSummary(array $totals): string
    {
        $coupon = $totals['coupon'] ? '<div class="d-flex justify-content-between text-success"><span>' . e(t('coupon')) . ' ' . e($totals['coupon']['code']) . '</span><strong>-' . e(Utility::money($totals['discount'])) . '</strong></div>' : '';
        return '<div class="soft-card p-4"><h2 class="h5 fw-bold">Order summary</h2><div class="d-flex justify-content-between"><span>' . e(t('subtotal')) . '</span><strong>' . e(Utility::money($totals['subtotal'])) . '</strong></div>' . $coupon . '<div class="d-flex justify-content-between"><span>' . e(t('delivery_fee')) . '</span><strong>' . e(Utility::money($totals['delivery_fee'])) . '</strong></div><div class="d-flex justify-content-between"><span>' . e(t('tax')) . '</span><strong>' . e(Utility::money($totals['tax'])) . '</strong></div><hr><div class="d-flex justify-content-between fs-5"><span>' . e(t('total')) . '</span><strong>' . e(Utility::money($totals['total'])) . '</strong></div></div>';
    }

    private static function metricCards(array $summary): string
    {
        $metrics = [
            ['Revenue', Utility::money($summary['revenue']), 'bi-cash-coin'],
            ['Orders', (string)$summary['orders'], 'bi-receipt'],
            ['Active customers', (string)$summary['customers'], 'bi-people'],
            ['Online drivers', (string)$summary['drivers'], 'bi-bicycle'],
        ];
        $html = '<div class="admin-grid">';
        foreach ($metrics as $metric) {
            $html .= '<div class="soft-card p-4"><div class="d-flex justify-content-between align-items-start"><div><p class="text-muted small mb-1">' . e($metric[0]) . '</p><div class="h3 fw-bold mb-0">' . e($metric[1]) . '</div></div><span class="btn btn-light btn-icon"><i class="bi ' . e($metric[2]) . '"></i></span></div></div>';
        }
        return $html . '</div>';
    }

    private static function chart(array $series): string
    {
        $max = 1.0;
        foreach ($series as $row) {
            $max = max($max, (float)$row['revenue']);
        }
        $bars = '';
        foreach ($series as $row) {
            $height = max(8, ((float)$row['revenue'] / $max) * 160);
            $bars .= '<span style="height:' . e((string)$height) . 'px" title="' . e($row['day'] . ' - ' . Utility::money($row['revenue'])) . '"></span>';
        }
        return '<div class="mini-chart" aria-label="Revenue chart">' . $bars . '</div><div class="d-flex justify-content-between small text-muted mt-2"><span>Daily</span><span>Weekly</span><span>Monthly</span><span>Yearly ready</span></div>';
    }

    private static function statusBadge(string $status): string
    {
        $colors = [
            'pending' => 'warning',
            'accepted' => 'info',
            'preparing' => 'primary',
            'ready' => 'success',
            'assigned_driver' => 'dark',
            'picked_up' => 'secondary',
            'delivered' => 'success',
            'cancelled' => 'danger',
            'rejected' => 'danger',
            'active' => 'success',
            'suspended' => 'danger',
            'approved' => 'success',
            'assigned' => 'dark',
            'online' => 'success',
            'offline' => 'secondary',
            'busy' => 'warning',
        ];
        $color = $colors[$status] ?? 'secondary';
        return '<span class="badge badge-status text-bg-' . e($color) . '">' . e(str_replace('_', ' ', $status)) . '</span>';
    }

    private static function stars(float $rating): string
    {
        $full = (int)round($rating);
        return str_repeat('★', max(0, min(5, $full))) . str_repeat('☆', max(0, 5 - $full));
    }

    private static function emptyState(string $title, string $body): string
    {
        return '<div class="soft-card p-5 text-center"><div class="btn btn-light btn-icon mb-3"><i class="bi bi-search"></i></div><h2 class="h4 fw-bold">' . e($title) . '</h2><p class="text-muted mb-0">' . e($body) . '</p></div>';
    }
}

function handlePost(Database $db): void
{
    Utility::verifyCsrf();
    $action = (string)($_POST['action'] ?? '');
    try {
        switch ($action) {
            case 'login':
                if (!Auth::login((string)$_POST['email'], (string)$_POST['password'], !empty($_POST['remember']))) {
                    throw new InvalidArgumentException('Invalid email or password.');
                }
                Utility::flash('success', 'Welcome back.');
                Utility::redirect('?page=dashboard');
            case 'register':
                Auth::register($_POST);
                Auth::login((string)$_POST['email'], (string)$_POST['password'], false);
                Utility::flash('success', 'Account created.');
                Utility::redirect('?page=dashboard');
            case 'forgot':
                $email = strtolower(trim((string)($_POST['email'] ?? '')));
                $user = $db->fetch('SELECT id FROM users WHERE email = ?', [$email]);
                if ($user) {
                    Notification::create($db, (int)$user['id'], 'Password reset requested', 'A reset request was created. In production this would email a signed reset link.', 'security');
                }
                Utility::flash('info', 'If that email exists, password reset instructions were created.');
                Utility::redirect('?page=login');
            case 'logout':
                Auth::logout();
                Utility::flash('success', 'Signed out.');
                Utility::redirect('?page=home');
            case 'add_cart':
                Cart::add($db, Utility::int($_POST['product_id'] ?? 0), Utility::int($_POST['quantity'] ?? 1, 1), $_POST['extras'] ?? []);
                Utility::flash('success', 'Added to cart.');
                Utility::redirect((string)($_POST['redirect'] ?? '?page=cart'));
            case 'cart_update':
                Cart::update($_POST['quantities'] ?? []);
                Utility::flash('success', 'Cart updated.');
                Utility::redirect('?page=cart');
            case 'apply_coupon':
                Cart::applyCoupon((string)($_POST['coupon_code'] ?? ''));
                Utility::flash('success', 'Coupon checked.');
                Utility::redirect('?page=cart');
            case 'place_order':
                $orderId = Order::place($db, $_POST);
                Utility::flash('success', 'Order placed successfully.');
                Utility::redirect('?page=order&id=' . $orderId);
            case 'order_status':
                Auth::requireRole(['restaurant_owner', 'restaurant_staff', 'driver', 'admin']);
                Order::updateStatus($db, Utility::int($_POST['order_id'] ?? 0), (string)$_POST['status']);
                Utility::flash('success', 'Order status updated.');
                Utility::redirect('?page=order&id=' . Utility::int($_POST['order_id'] ?? 0));
            case 'review':
                Review::create($db, $_POST);
                Utility::flash('success', 'Review published.');
                $rid = Utility::int($_POST['restaurant_id'] ?? 0);
                Utility::redirect($rid ? '?page=restaurant&id=' . $rid : '?page=orders');
            case 'profile':
                Auth::requireRole(['customer', 'restaurant_owner', 'restaurant_staff', 'driver', 'admin']);
                $params = [trim((string)$_POST['name']), trim((string)$_POST['phone']), in_array($_POST['preferred_language'] ?? 'en', ['en', 'fr', 'ar'], true) ? $_POST['preferred_language'] : 'en', Auth::id()];
                $db->query('UPDATE users SET name = ?, phone = ?, preferred_language = ? WHERE id = ?', $params);
                if (!empty($_POST['password'])) {
                    $db->query('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash((string)$_POST['password'], PASSWORD_DEFAULT), Auth::id()]);
                }
                $_SESSION['lang'] = $params[2];
                Utility::flash('success', 'Profile updated.');
                Utility::redirect('?page=profile');
            case 'driver_status':
                Auth::requireRole(['driver']);
                $status = in_array($_POST['status'] ?? '', ['online', 'offline', 'busy'], true) ? $_POST['status'] : 'offline';
                $db->query('UPDATE drivers SET status = ? WHERE user_id = ?', [$status, Auth::id()]);
                Utility::flash('success', 'Driver status updated.');
                Utility::redirect('?page=dashboard');
            case 'restaurant_settings':
                Auth::requireRole(['restaurant_owner', 'restaurant_staff', 'admin']);
                $restaurant = Restaurant::ownedByCurrentUser($db);
                if (!$restaurant && !Auth::hasRole(['admin'])) {
                    throw new RuntimeException('Restaurant not found.');
                }
                $rid = Utility::int($_POST['restaurant_id'] ?? ($restaurant['id'] ?? 0));
                $status = in_array($_POST['status'] ?? 'open', ['open', 'closed', 'paused'], true) ? $_POST['status'] : 'open';
                $db->query('UPDATE restaurants SET opening_hours = ?, delivery_fee = ?, status = ? WHERE id = ?', [trim((string)$_POST['opening_hours']), (float)$_POST['delivery_fee'], $status, $rid]);
                Utility::flash('success', 'Restaurant settings updated.');
                Utility::redirect('?page=dashboard');
            case 'coupon_save':
                Auth::requireRole(['admin']);
                $code = strtoupper(trim((string)$_POST['code']));
                $type = in_array($_POST['type'] ?? '', ['percentage', 'fixed'], true) ? $_POST['type'] : 'fixed';
                $db->insert('INSERT INTO coupons (code, type, value, min_order, starts_at, ends_at, usage_limit, is_active) VALUES (?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY), 500, 1) ON DUPLICATE KEY UPDATE type = VALUES(type), value = VALUES(value), min_order = VALUES(min_order), is_active = 1', [$code, $type, (float)$_POST['value'], (float)($_POST['min_order'] ?? 0)]);
                Utility::flash('success', 'Coupon saved.');
                Utility::redirect('?page=dashboard');
            default:
                throw new InvalidArgumentException('Unknown action.');
        }
    } catch (Throwable $e) {
        Utility::flash('danger', $e->getMessage());
        Utility::redirect($_SERVER['HTTP_REFERER'] ?? '?page=home');
    }
}

set_exception_handler([ErrorHandler::class, 'render']);
Utility::lang();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'install') {
    try {
        Utility::verifyCsrf();
        $_SESSION['debug_mode'] = !empty($_POST['debug']);
        Config::rememberDb($_POST);
        $db = new Database(Config::db());
        $db->migrate();
        $db->seedIfNeeded();
        Utility::flash('success', 'Installation complete. Demo data is ready.');
        Utility::redirect('?page=home');
    } catch (Throwable $e) {
        View::installer($e, $_POST);
        exit;
    }
}

try {
    $db = new Database(Config::db());
    $db->migrate();
    $db->seedIfNeeded();
    Auth::boot($db);
} catch (Throwable $e) {
    View::installer($e);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handlePost($db);
}

$page = (string)($_GET['page'] ?? 'home');
$route = Router::dispatch($db, $page);
View::render($db, $route['title'], $route['content']);
