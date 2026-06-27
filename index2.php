<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║   DarFood — Marketplace de Livraison de Nourriture              ║
 * ║   Version 1.0.0 · Youcef Studio © 2024                         ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * Stack: PHP 8.0+ · MySQL · Bootstrap 5 · Vanilla JS
 * Architecture: Single-File MVC · PDO · Session Auth
 */

// ====================================================================
// SECTION 1 — CONFIGURATION
// ====================================================================
define('APP_NAME',    'DarFood');
define('APP_TAGLINE', 'Commandez. Savourez. Profitez.');
define('APP_VERSION', '1.0.0');
define('DEBUG_MODE',  true);

// ─── Database ───────────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'darfood');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// ─── App ────────────────────────────────────────────────────────────
define('ITEMS_PER_PAGE', 12);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

session_name('darfood_sess');
if (session_status() === PHP_SESSION_NONE) session_start();

// ====================================================================
// SECTION 2 — HELPERS
// ====================================================================

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_ok(): bool {
    $t = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['csrf'] ?? '', $t);
}

function redirect(string $url): never {
    header("Location: $url");
    exit;
}

function json_out(array $d, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}

function flash(string $key, string $msg = ''): string {
    if ($msg !== '') { $_SESSION['flash'][$key] = $msg; return ''; }
    $v = $_SESSION['flash'][$key] ?? '';
    unset($_SESSION['flash'][$key]);
    return $v;
}

function money(float $n): string {
    return number_format($n, 0, '.', ' ') . ' DA';
}

function ago(string $dt): string {
    $s = time() - strtotime($dt);
    if ($s < 60) return 'À l\'instant';
    if ($s < 3600) return floor($s/60) . ' min';
    if ($s < 86400) return floor($s/3600) . 'h';
    return date('d/m/Y', strtotime($dt));
}

function stars(float $r): string {
    $h = '';
    for ($i = 1; $i <= 5; $i++) {
        $h .= $i <= $r
            ? '<i class="fa-solid fa-star text-warning"></i>'
            : ($i - .5 <= $r
                ? '<i class="fa-solid fa-star-half-stroke text-warning"></i>'
                : '<i class="fa-regular fa-star" style="color:#ddd"></i>');
    }
    return $h;
}

function clean(string $s): string { return trim(strip_tags($s)); }

function ava(string $name): string {
    return 'https://ui-avatars.com/api/?name='.urlencode($name).'&background=FF5722&color=fff&size=80&bold=true';
}

function cover(int $id): string { return "https://picsum.photos/seed/rest{$id}/800/400"; }
function pimg(int $id): string  { return "https://picsum.photos/seed/prod{$id}/400/300"; }

// ====================================================================
// SECTION 3 — DATABASE
// ====================================================================
class DB
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo) return self::$pdo;

        // Ensure DB exists
        try {
            $tmp = new PDO(
                "mysql:host=".DB_HOST.";port=".DB_PORT.";charset=".DB_CHARSET,
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $tmp->exec("CREATE DATABASE IF NOT EXISTS `".DB_NAME."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (PDOException $e) { /* ignore */ }

        try {
            self::$pdo = new PDO(
                "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=".DB_CHARSET,
                DB_USER, DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                ]
            );
        } catch (PDOException $e) {
            $msg = DEBUG_MODE ? $e->getMessage() : "Erreur de connexion à la base de données.";
            die('<div style="font-family:system-ui;padding:30px;background:#fff0f0;border-left:5px solid red;max-width:700px;margin:40px auto">
                <h2>❌ Connexion DB échouée</h2><p>'.$msg.'</p>
                <p>Vérifiez DB_HOST, DB_USER, DB_PASS dans le fichier.</p></div>');
        }
        return self::$pdo;
    }

    public static function q(string $sql, array $p = []): PDOStatement
    {
        $s = self::pdo()->prepare($sql);
        $s->execute($p);
        return $s;
    }

    public static function row(string $sql, array $p = []): ?array
    { return self::q($sql, $p)->fetch() ?: null; }

    public static function all(string $sql, array $p = []): array
    { return self::q($sql, $p)->fetchAll(); }

    public static function val(string $sql, array $p = []): mixed
    { $v = self::q($sql, $p)->fetchColumn(); return $v === false ? null : $v; }

    public static function ins(string $sql, array $p = []): int
    { self::q($sql, $p); return (int)self::pdo()->lastInsertId(); }

    public static function run(string $sql, array $p = []): int
    { return self::q($sql, $p)->rowCount(); }

    public static function begin()  : void { self::pdo()->beginTransaction(); }
    public static function commit() : void { self::pdo()->commit(); }
    public static function back()   : void { self::pdo()->rollBack(); }
}

// ====================================================================
// SECTION 4 — AUTH
// ====================================================================
class Auth
{
    public static function check(): bool
    {
        if (!empty($_SESSION['uid'])) return true;
        if (!empty($_COOKIE['rem'])) {
            $u = DB::row("SELECT * FROM users WHERE remember_token=? AND status='active'", [$_COOKIE['rem']]);
            if ($u) {
                self::_set($u);
                return true;
            }
        }
        return false;
    }

    private static function _set(array $u): void
    {
        $_SESSION['uid']   = $u['id'];
        $_SESSION['urole'] = $u['role'];
        $_SESSION['uname'] = $u['name'];
    }

    public static function login(string $email, string $pass, bool $rem = false): bool
    {
        $u = DB::row("SELECT * FROM users WHERE email=? AND status='active'", [$email]);
        if (!$u || !password_verify($pass, $u['password'])) return false;
        self::_set($u);
        if ($rem) {
            $tok = bin2hex(random_bytes(32));
            setcookie('rem', $tok, time() + 86400*30, '/');
            DB::run("UPDATE users SET remember_token=? WHERE id=?", [$tok, $u['id']]);
        }
        DB::run("UPDATE users SET last_login=NOW() WHERE id=?", [$u['id']]);
        self::log($u['id'], 'login');
        return true;
    }

    public static function logout(): void
    {
        if (!empty($_SESSION['uid']))
            DB::run("UPDATE users SET remember_token=NULL WHERE id=?", [$_SESSION['uid']]);
        setcookie('rem', '', time()-3600, '/');
        session_destroy();
        session_start();
    }

    public static function register(array $d): int|false
    {
        if (DB::val("SELECT id FROM users WHERE email=?", [$d['email']])) return false;
        return DB::ins(
            "INSERT INTO users (name,email,phone,password,role,status,created_at) VALUES (?,?,?,?,?,?,NOW())",
            [$d['name'], $d['email'], $d['phone']??'', password_hash($d['password'],PASSWORD_DEFAULT), $d['role']??'customer','active']
        );
    }

    public static function id()    : int    { return (int)($_SESSION['uid']   ?? 0); }
    public static function role()  : string { return $_SESSION['urole'] ?? 'guest'; }
    public static function name()  : string { return $_SESSION['uname'] ?? ''; }
    public static function is($r)  : bool   { return self::role() === $r; }
    public static function isAdmin(): bool  { return self::is('admin');  }
    public static function isOwner(): bool  { return self::is('owner');  }
    public static function isDriver():bool  { return self::is('driver'); }

    public static function user(): ?array
    { return self::check() ? DB::row("SELECT * FROM users WHERE id=?", [self::id()]) : null; }

    public static function guard(string $role = ''): void
    {
        if (!self::check()) {
            flash('error','Connexion requise.');
            redirect('?page=login&ret='.urlencode($_SERVER['REQUEST_URI']));
        }
        if ($role && self::role() !== $role) redirect('?page=home');
    }

    public static function log(int $uid, string $act, string $detail = ''): void
    {
        try {
            DB::ins("INSERT INTO audit_logs (user_id,action,details,ip,created_at) VALUES (?,?,?,?,NOW())",
                    [$uid, $act, $detail, $_SERVER['REMOTE_ADDR']??'']);
        } catch (Exception $e) {}
    }
}

// ====================================================================
// SECTION 5 — MODELS
// ====================================================================

/* ── Restaurant ─────────────────────────────────────────────────── */
class RModel
{
    public static function get(int $id): ?array
    { return DB::row("SELECT r.*,u.name owner_name FROM restaurants r LEFT JOIN users u ON r.owner_id=u.id WHERE r.id=?", [$id]); }

    public static function byOwner(int $oid): ?array
    { return DB::row("SELECT * FROM restaurants WHERE owner_id=?", [$oid]); }

    public static function list(array $f=[], int $lim=12, int $off=0): array
    {
        [$where,$p] = self::_where($f);
        $ob = match($f['sort']??'') { 'rating'=>'r.rating DESC','newest'=>'r.created_at DESC','fee'=>'r.delivery_fee ASC', default=>'r.featured DESC,r.rating DESC' };
        $p[]=$lim; $p[]=$off;
        return DB::all("SELECT r.* FROM restaurants r $where ORDER BY $ob LIMIT ? OFFSET ?", $p);
    }

    public static function count(array $f=[]): int
    { [$where,$p]=self::_where($f); return (int)DB::val("SELECT COUNT(*) FROM restaurants r $where",$p); }

    private static function _where(array $f): array
    {
        $w=["r.status='active'"]; $p=[];
        if(!empty($f['search'])){ $w[]="(r.name LIKE ? OR r.description LIKE ?)"; $p[]="%{$f['search']}%"; $p[]="%{$f['search']}%"; }
        if(!empty($f['category'])){ $w[]="r.category=?"; $p[]=$f['category']; }
        return ['WHERE '.implode(' AND ',$w), $p];
    }

    public static function featured(int $n=6): array
    { return DB::all("SELECT * FROM restaurants WHERE status='active' AND featured=1 ORDER BY rating DESC LIMIT ?",[$n]); }

    public static function topRated(int $n=6): array
    { return DB::all("SELECT * FROM restaurants WHERE status='active' ORDER BY rating DESC LIMIT ?",[$n]); }

    public static function newest(int $n=6): array
    { return DB::all("SELECT * FROM restaurants WHERE status='active' ORDER BY created_at DESC LIMIT ?",[$n]); }

    public static function menu(int $rid): array
    {
        $cats = DB::all("SELECT DISTINCT menu_category FROM products WHERE restaurant_id=? AND is_available=1 ORDER BY menu_category",[$rid]);
        $out=[];
        foreach($cats as $c) {
            $k=$c['menu_category'];
            $out[$k]=DB::all("SELECT * FROM products WHERE restaurant_id=? AND menu_category=? AND is_available=1 ORDER BY is_featured DESC,name",[$rid,$k]);
        }
        return $out;
    }

    public static function update(int $id, array $d): void
    {
        $sets=[]; $p=[];
        foreach($d as $k=>$v){ $sets[]="$k=?"; $p[]=$v; }
        $p[]=$id;
        DB::run("UPDATE restaurants SET ".implode(',',$sets)." WHERE id=?",$p);
    }

    public static function recalcRating(int $id): void
    {
        $avg=DB::val("SELECT AVG(rating) FROM reviews WHERE restaurant_id=?",[$id]);
        $cnt=DB::val("SELECT COUNT(*) FROM reviews WHERE restaurant_id=?",[$id]);
        DB::run("UPDATE restaurants SET rating=?,rating_count=? WHERE id=?",[round($avg??0,1),$cnt,$id]);
    }
}

/* ── Product ────────────────────────────────────────────────────── */
class PModel
{
    public static function get(int $id): ?array
    { return DB::row("SELECT p.*,r.name restaurant_name,r.delivery_fee FROM products p LEFT JOIN restaurants r ON p.restaurant_id=r.id WHERE p.id=?",[$id]); }

    public static function byRestaurant(int $rid): array
    { return DB::all("SELECT * FROM products WHERE restaurant_id=? ORDER BY menu_category,name",[$rid]); }

    public static function create(array $d): int
    {
        return DB::ins(
            "INSERT INTO products (restaurant_id,name,name_ar,description,price,discount_price,image,menu_category,is_available,is_featured,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())",
            [$d['restaurant_id'],$d['name'],$d['name_ar']??'',$d['description']??'',$d['price'],$d['discount_price']??null,$d['image']??'',$d['menu_category']??'Principal',$d['is_available']??1,$d['is_featured']??0]
        );
    }

    public static function update(int $id, array $d): void
    {
        $sets=[]; $p=[];
        foreach($d as $k=>$v){ $sets[]="$k=?"; $p[]=$v; }
        $p[]=$id;
        DB::run("UPDATE products SET ".implode(',',$sets)." WHERE id=?",$p);
    }

    public static function delete(int $id): void { DB::run("DELETE FROM products WHERE id=?",[$id]); }

    public static function search(string $q, int $n=12): array
    { return DB::all("SELECT p.*,r.name restaurant_name FROM products p JOIN restaurants r ON p.restaurant_id=r.id WHERE p.is_available=1 AND r.status='active' AND (p.name LIKE ? OR p.description LIKE ?) ORDER BY p.is_featured DESC LIMIT ?",["%$q%","%$q%",$n]); }
}

/* ── Cart ───────────────────────────────────────────────────────── */
class Cart
{
    private static function &data(): array
    {
        if (!isset($_SESSION['cart'])) $_SESSION['cart']=['rid'=>0,'items'=>[]];
        return $_SESSION['cart'];
    }

    public static function add(array $item): bool
    {
        $d=&self::data(); $pid=(int)$item['pid']; $rid=(int)$item['rid'];
        if($d['rid']>0 && $d['rid']!==$rid) return false;
        $d['rid']=$rid;
        if(isset($d['items'][$pid])) $d['items'][$pid]['qty']+=(int)($item['qty']??1);
        else $d['items'][$pid]=['pid'=>$pid,'name'=>$item['name'],'price'=>(float)$item['price'],'qty'=>(int)($item['qty']??1),'img'=>$item['img']??''];
        return true;
    }

    public static function update(int $pid, int $qty): void
    {
        $d=&self::data();
        if($qty<=0) unset($d['items'][$pid]);
        elseif(isset($d['items'][$pid])) $d['items'][$pid]['qty']=$qty;
        if(empty($d['items'])) $d['rid']=0;
    }

    public static function clear(): void { $_SESSION['cart']=['rid'=>0,'items'=>[]]; }

    public static function get(): array  { return self::data(); }
    public static function count(): int  { return array_sum(array_column(self::data()['items'],'qty')); }
    public static function total(): float{ $t=0; foreach(self::data()['items'] as $i) $t+=$i['price']*$i['qty']; return $t; }
    public static function rid():   int  { return (int)(self::data()['rid']??0); }
    public static function empty(): bool { return empty(self::data()['items']); }
}

/* ── Order ──────────────────────────────────────────────────────── */
class OModel
{
    public static function create(array $d): int
    {
        return DB::ins(
            "INSERT INTO orders (customer_id,restaurant_id,status,delivery_type,address,phone,subtotal,delivery_fee,discount,total,payment_method,payment_status,coupon_code,notes,created_at) VALUES (?,?,'pending',?,?,?,?,?,?,?,?,'pending',?,?,NOW())",
            [$d['cid'],$d['rid'],$d['dtype'],$d['address'],$d['phone'],$d['subtotal'],$d['dfee'],$d['discount']??0,$d['total'],$d['pmethod'],$d['coupon']??null,$d['notes']??'']
        );
    }

    public static function addItem(array $d): void
    {
        DB::ins(
            "INSERT INTO order_items (order_id,product_id,product_name,quantity,unit_price,options,extras,notes) VALUES (?,?,?,?,?,?,?,?)",
            [$d['oid'],$d['pid'],$d['pname'],$d['qty'],$d['uprice'],json_encode($d['options']??[]),json_encode($d['extras']??[]),$d['notes']??'']
        );
    }

    public static function get(int $id): ?array
    {
        return DB::row(
            "SELECT o.*,r.name rname,r.address raddr,r.phone rphone,u.name cname,u.phone cphone,d.name drname,d.phone drphone
             FROM orders o
             LEFT JOIN restaurants r ON o.restaurant_id=r.id
             LEFT JOIN users u ON o.customer_id=u.id
             LEFT JOIN drivers d ON o.driver_id=d.id
             WHERE o.id=?",[$id]
        );
    }

    public static function items(int $oid): array
    { return DB::all("SELECT oi.*,p.image FROM order_items oi LEFT JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?",[$oid]); }

    public static function byCustomer(int $cid, int $lim=20, int $off=0): array
    { return DB::all("SELECT o.*,r.name rname,r.logo rlogo FROM orders o LEFT JOIN restaurants r ON o.restaurant_id=r.id WHERE o.customer_id=? ORDER BY o.created_at DESC LIMIT ? OFFSET ?",[$cid,$lim,$off]); }

    public static function byRestaurant(int $rid, string $st='', int $lim=50): array
    {
        $w=''; $p=[$rid];
        if($st){ $w=" AND o.status=?"; $p[]=$st; }
        $p[]=$lim;
        return DB::all("SELECT o.*,u.name cname,u.phone cphone FROM orders o LEFT JOIN users u ON o.customer_id=u.id WHERE o.restaurant_id=? $w ORDER BY o.created_at DESC LIMIT ?",$p);
    }

    public static function byDriver(int $did): array
    {
        return DB::all(
            "SELECT o.*,r.name rname,r.address raddr,u.name cname FROM orders o LEFT JOIN restaurants r ON o.restaurant_id=r.id LEFT JOIN users u ON o.customer_id=u.id WHERE o.driver_id=? AND o.status IN ('assigned','picked_up') ORDER BY o.created_at DESC LIMIT 10",
            [$did]
        );
    }

    public static function available(int $lim=20): array
    {
        return DB::all(
            "SELECT o.*,r.name rname,r.address raddr,u.name cname FROM orders o LEFT JOIN restaurants r ON o.restaurant_id=r.id LEFT JOIN users u ON o.customer_id=u.id WHERE o.status='ready' AND o.driver_id IS NULL ORDER BY o.created_at DESC LIMIT ?",
            [$lim]
        );
    }

    public static function setStatus(int $id, string $st): void
    { DB::run("UPDATE orders SET status=?,updated_at=NOW() WHERE id=?",[$st,$id]); }

    public static function assignDriver(int $oid, int $did): void
    {
        DB::run("UPDATE orders SET driver_id=?,status='assigned',updated_at=NOW() WHERE id=?",[$did,$oid]);
        DB::run("UPDATE drivers SET status='busy' WHERE id=?",[$did]);
        DB::ins("INSERT INTO deliveries (order_id,driver_id,status,created_at) VALUES (?,?,'assigned',NOW())",[$oid,$did]);
    }

    public static function stats(int $rid=0): array
    {
        $w = $rid>0 ? "AND restaurant_id=$rid" : '';
        return [
            'today'   => (float)DB::val("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='delivered' AND DATE(created_at)=CURDATE() $w"),
            'week'    => (float)DB::val("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='delivered' AND created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY) $w"),
            'month'   => (float)DB::val("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='delivered' AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW()) $w"),
            'total'   => (int)DB::val("SELECT COUNT(*) FROM orders WHERE 1=1 $w"),
            'pending' => (int)DB::val("SELECT COUNT(*) FROM orders WHERE status IN ('pending','accepted','preparing','ready','assigned') $w"),
        ];
    }
}

/* ── Review ─────────────────────────────────────────────────────── */
class RevModel
{
    public static function create(array $d): int
    { return DB::ins("INSERT INTO reviews (customer_id,restaurant_id,order_id,rating,comment,created_at) VALUES (?,?,?,?,?,NOW())",[$d['cid'],$d['rid'],$d['oid'],$d['rating'],$d['comment']]); }

    public static function byRestaurant(int $rid, int $n=10): array
    { return DB::all("SELECT rv.*,u.name cname FROM reviews rv LEFT JOIN users u ON rv.customer_id=u.id WHERE rv.restaurant_id=? ORDER BY rv.created_at DESC LIMIT ?",[$rid,$n]); }

    public static function hasReviewed(int $cid, int $oid): bool
    { return (bool)DB::val("SELECT COUNT(*) FROM reviews WHERE customer_id=? AND order_id=?",[$cid,$oid]); }
}

/* ── Notification ───────────────────────────────────────────────── */
class Notif
{
    public static function push(int $uid, string $type, string $title, string $msg): void
    { try { DB::ins("INSERT INTO notifications (user_id,type,title,message,is_read,created_at) VALUES (?,?,?,?,0,NOW())",[$uid,$type,$title,$msg]); } catch(Exception $e){} }

    public static function unread(int $uid): array
    { return DB::all("SELECT * FROM notifications WHERE user_id=? AND is_read=0 ORDER BY created_at DESC LIMIT 10",[$uid]); }

    public static function all(int $uid, int $n=20): array
    { return DB::all("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT ?",[$uid,$n]); }

    public static function count(int $uid): int
    { return (int)DB::val("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0",[$uid]); }

    public static function markRead(int $uid): void
    { DB::run("UPDATE notifications SET is_read=1 WHERE user_id=?",[$uid]); }
}

/* ── Coupon ─────────────────────────────────────────────────────── */
class Coupon
{
    public static function validate(string $code, float $sub): array
    {
        $c=DB::row("SELECT * FROM coupons WHERE code=? AND status='active' AND (expires_at IS NULL OR expires_at>NOW()) AND (max_uses=0 OR used_count<max_uses)",[strtoupper($code)]);
        if(!$c) return ['ok'=>false,'msg'=>'Code promo invalide ou expiré.'];
        if($sub<$c['min_order']) return ['ok'=>false,'msg'=>'Commande minimum: '.money($c['min_order'])];
        $disc=$c['type']==='percent' ? $sub*$c['value']/100 : $c['value'];
        $disc=min($disc,$sub);
        return ['ok'=>true,'disc'=>$disc,'id'=>$c['id'],'msg'=>'Réduction de '.money($disc)];
    }

    public static function use(int $id): void { DB::run("UPDATE coupons SET used_count=used_count+1 WHERE id=?",[$id]); }
}

/* ── Admin ──────────────────────────────────────────────────────── */
class AdminModel
{
    public static function stats(): array
    {
        return [
            'customers'     => (int)DB::val("SELECT COUNT(*) FROM users WHERE role='customer'"),
            'restaurants'   => (int)DB::val("SELECT COUNT(*) FROM restaurants WHERE status='active'"),
            'orders'        => (int)DB::val("SELECT COUNT(*) FROM orders"),
            'drivers'       => (int)DB::val("SELECT COUNT(*) FROM drivers"),
            'rev_today'     => (float)DB::val("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='delivered' AND DATE(created_at)=CURDATE()"),
            'rev_month'     => (float)DB::val("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='delivered' AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())"),
            'pending'       => (int)DB::val("SELECT COUNT(*) FROM orders WHERE status IN ('pending','accepted','preparing','ready','assigned')"),
        ];
    }

    public static function revenueChart(): array
    { return DB::all("SELECT DATE(created_at) d,COALESCE(SUM(total),0) rev,COUNT(*) cnt FROM orders WHERE status='delivered' AND created_at>=DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY d"); }

    public static function topRestaurants(): array
    { return DB::all("SELECT r.name,COUNT(o.id) oc,COALESCE(SUM(o.total),0) rev FROM restaurants r LEFT JOIN orders o ON r.id=o.restaurant_id AND o.status='delivered' GROUP BY r.id ORDER BY rev DESC LIMIT 8"); }

    public static function users(int $lim=50, int $off=0): array
    { return DB::all("SELECT * FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?",[$lim,$off]); }

    public static function restaurants(): array
    { return DB::all("SELECT r.*,u.name owner_name FROM restaurants r LEFT JOIN users u ON r.owner_id=u.id ORDER BY r.created_at DESC LIMIT 50"); }

    public static function orders(int $lim=50): array
    { return DB::all("SELECT o.*,r.name rname,u.name cname FROM orders o LEFT JOIN restaurants r ON o.restaurant_id=r.id LEFT JOIN users u ON o.customer_id=u.id ORDER BY o.created_at DESC LIMIT ?",[$lim]); }

    public static function drivers(): array
    { return DB::all("SELECT d.*,u.name,u.email,u.phone FROM drivers d LEFT JOIN users u ON d.user_id=u.id ORDER BY d.created_at DESC"); }

    public static function logs(): array
    { return DB::all("SELECT al.*,u.name uname FROM audit_logs al LEFT JOIN users u ON al.user_id=u.id ORDER BY al.created_at DESC LIMIT 30"); }
}

// ====================================================================
// SECTION 6 — SETUP & SEED
// ====================================================================
class Setup
{
    public static function installed(): bool
    {
        try { return (bool)DB::val("SELECT COUNT(*) FROM users"); }
        catch(Exception $e){ return false; }
    }

    public static function run(): void
    {
        self::tables();
        if(!self::installed()) self::seed();
    }

    private static function tables(): void
    {
        $pdo=DB::pdo();
        $stmts=[
        "CREATE TABLE IF NOT EXISTS `users` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(150) UNIQUE NOT NULL,
            `phone` VARCHAR(20) DEFAULT '',
            `password` VARCHAR(255) NOT NULL,
            `role` ENUM('customer','owner','driver','admin','staff') DEFAULT 'customer',
            `avatar` VARCHAR(255) DEFAULT '',
            `address` TEXT,
            `status` ENUM('active','inactive','banned') DEFAULT 'active',
            `remember_token` VARCHAR(100) DEFAULT NULL,
            `last_login` DATETIME DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `categories` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `name_ar` VARCHAR(100) DEFAULT '',
            `icon` VARCHAR(10) DEFAULT '🍽',
            `color` VARCHAR(7) DEFAULT '#FF5722',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `restaurants` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `owner_id` INT UNSIGNED NOT NULL,
            `name` VARCHAR(150) NOT NULL,
            `slug` VARCHAR(150) UNIQUE NOT NULL,
            `description` TEXT,
            `category` VARCHAR(100) DEFAULT '',
            `address` VARCHAR(255) DEFAULT '',
            `city` VARCHAR(100) DEFAULT 'Alger',
            `phone` VARCHAR(20) DEFAULT '',
            `email` VARCHAR(150) DEFAULT '',
            `logo` VARCHAR(255) DEFAULT '',
            `cover` VARCHAR(255) DEFAULT '',
            `delivery_fee` DECIMAL(10,2) DEFAULT 150.00,
            `min_order` DECIMAL(10,2) DEFAULT 500.00,
            `delivery_time` INT DEFAULT 30,
            `rating` DECIMAL(3,1) DEFAULT 0.0,
            `rating_count` INT DEFAULT 0,
            `status` ENUM('active','inactive','pending') DEFAULT 'active',
            `featured` TINYINT(1) DEFAULT 0,
            `opening_hours` TEXT,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_cat (`category`), INDEX idx_st (`status`), INDEX idx_rt (`rating`),
            FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `restaurant_staff` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `restaurant_id` INT UNSIGNED NOT NULL,
            `user_id` INT UNSIGNED NOT NULL,
            `role` VARCHAR(50) DEFAULT 'staff',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `drivers` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT UNSIGNED NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `phone` VARCHAR(20) DEFAULT '',
            `vehicle_type` VARCHAR(50) DEFAULT 'moto',
            `vehicle_plate` VARCHAR(20) DEFAULT '',
            `status` ENUM('online','offline','busy') DEFAULT 'offline',
            `rating` DECIMAL(3,1) DEFAULT 5.0,
            `total_earnings` DECIMAL(10,2) DEFAULT 0,
            `total_deliveries` INT DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `products` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `restaurant_id` INT UNSIGNED NOT NULL,
            `name` VARCHAR(150) NOT NULL,
            `name_ar` VARCHAR(150) DEFAULT '',
            `description` TEXT,
            `price` DECIMAL(10,2) NOT NULL DEFAULT 0,
            `discount_price` DECIMAL(10,2) DEFAULT NULL,
            `image` VARCHAR(255) DEFAULT '',
            `menu_category` VARCHAR(100) DEFAULT 'Principal',
            `is_available` TINYINT(1) DEFAULT 1,
            `is_featured` TINYINT(1) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_rest (`restaurant_id`),
            FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `product_images` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT UNSIGNED NOT NULL,
            `image_url` VARCHAR(255) NOT NULL,
            FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `product_options` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT UNSIGNED NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `values` TEXT,
            FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `product_extras` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT UNSIGNED NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `price` DECIMAL(10,2) DEFAULT 0,
            FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `orders` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `customer_id` INT UNSIGNED NOT NULL,
            `restaurant_id` INT UNSIGNED NOT NULL,
            `driver_id` INT UNSIGNED DEFAULT NULL,
            `status` ENUM('pending','accepted','preparing','ready','assigned','picked_up','delivered','cancelled','rejected') DEFAULT 'pending',
            `delivery_type` ENUM('delivery','pickup','dinein') DEFAULT 'delivery',
            `address` TEXT,
            `phone` VARCHAR(20) DEFAULT '',
            `subtotal` DECIMAL(10,2) DEFAULT 0,
            `delivery_fee` DECIMAL(10,2) DEFAULT 0,
            `discount` DECIMAL(10,2) DEFAULT 0,
            `total` DECIMAL(10,2) DEFAULT 0,
            `payment_method` ENUM('cash','card') DEFAULT 'cash',
            `payment_status` ENUM('pending','paid','refunded') DEFAULT 'pending',
            `coupon_code` VARCHAR(50) DEFAULT NULL,
            `notes` TEXT,
            `estimated_time` INT DEFAULT 30,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            INDEX idx_cust (`customer_id`), INDEX idx_rest (`restaurant_id`), INDEX idx_st (`status`), INDEX idx_dt (`created_at`),
            FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`),
            FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `order_items` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `order_id` INT UNSIGNED NOT NULL,
            `product_id` INT UNSIGNED DEFAULT NULL,
            `product_name` VARCHAR(150) NOT NULL,
            `quantity` INT DEFAULT 1,
            `unit_price` DECIMAL(10,2) DEFAULT 0,
            `options` TEXT,
            `extras` TEXT,
            `notes` TEXT,
            FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `deliveries` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `order_id` INT UNSIGNED NOT NULL,
            `driver_id` INT UNSIGNED NOT NULL,
            `status` ENUM('assigned','picked_up','delivered') DEFAULT 'assigned',
            `picked_at` DATETIME DEFAULT NULL,
            `delivered_at` DATETIME DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`),
            FOREIGN KEY (`driver_id`) REFERENCES `drivers`(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `payments` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `order_id` INT UNSIGNED NOT NULL,
            `method` ENUM('cash','card') DEFAULT 'cash',
            `amount` DECIMAL(10,2) DEFAULT 0,
            `status` ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
            `reference` VARCHAR(100) DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `reviews` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `customer_id` INT UNSIGNED NOT NULL,
            `restaurant_id` INT UNSIGNED NOT NULL,
            `order_id` INT UNSIGNED DEFAULT NULL,
            `rating` TINYINT UNSIGNED DEFAULT 5,
            `comment` TEXT,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`),
            FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `coupons` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `code` VARCHAR(30) UNIQUE NOT NULL,
            `type` ENUM('percent','fixed') DEFAULT 'percent',
            `value` DECIMAL(10,2) NOT NULL,
            `min_order` DECIMAL(10,2) DEFAULT 0,
            `max_uses` INT DEFAULT 0,
            `used_count` INT DEFAULT 0,
            `status` ENUM('active','inactive') DEFAULT 'active',
            `expires_at` DATETIME DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `notifications` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT UNSIGNED NOT NULL,
            `type` VARCHAR(50) DEFAULT 'info',
            `title` VARCHAR(255) NOT NULL,
            `message` TEXT,
            `is_read` TINYINT(1) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_uid (`user_id`,`is_read`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `settings` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `setting_key` VARCHAR(100) UNIQUE NOT NULL,
            `setting_value` TEXT,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `audit_logs` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT UNSIGNED DEFAULT NULL,
            `action` VARCHAR(100) NOT NULL,
            `details` TEXT,
            `ip` VARCHAR(45) DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_dt (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];

        foreach($stmts as $sql) {
            try { $pdo->exec($sql); } catch(PDOException $e){ /* skip existing */ }
        }
    }

    private static function seed(): void
    {
        // ── Admin ──────────────────────────────────────────────────
        $adminId = DB::ins(
            "INSERT INTO users (name,email,phone,password,role,status) VALUES (?,?,?,?,?,?)",
            ['Administrateur','admin@darfood.dz','0555000000',password_hash('admin123',PASSWORD_DEFAULT),'admin','active']
        );

        // ── Categories ─────────────────────────────────────────────
        $cats=[
            ['Pizza','بيتزا','🍕','#FF5722'],
            ['Burger','برغر','🍔','#E53935'],
            ['Shawarma','شاورما','🥙','#FF9800'],
            ['Tacos','تاكوس','🌮','#4CAF50'],
            ['Café','قهوة','☕','#795548'],
            ['Boulangerie','مخبزة','🥐','#FFC107'],
            ['Traditionnel','تقليدي','🫕','#9C27B0'],
            ['Grill','مشويات','🥩','#E91E63'],
        ];
        foreach($cats as $c)
            DB::ins("INSERT INTO categories (name,name_ar,icon,color) VALUES (?,?,?,?)",$c);

        // ── Coupons ────────────────────────────────────────────────
        DB::ins("INSERT INTO coupons (code,type,value,min_order,max_uses,status) VALUES ('BIENVENUE','percent',10,1000,100,'active')",[]);
        DB::ins("INSERT INTO coupons (code,type,value,min_order,max_uses,status) VALUES ('DARFOOD50','fixed',50,500,50,'active')",[]);
        DB::ins("INSERT INTO coupons (code,type,value,min_order,max_uses,status) VALUES ('LIVRAISON','fixed',150,800,200,'active')",[]);
        DB::ins("INSERT INTO coupons (code,type,value,min_order,max_uses,status) VALUES ('PROMO20','percent',20,1500,30,'active')",[]);

        // ── Settings ───────────────────────────────────────────────
        foreach(['platform_fee'=>'5','default_delivery_fee'=>'150','currency'=>'DA','app_name'=>APP_NAME] as $k=>$v)
            DB::ins("INSERT IGNORE INTO settings (setting_key,setting_value) VALUES (?,?)",[$k,$v]);

        // ── Owners (20) ────────────────────────────────────────────
        $ownerIds=[];
        for($i=1;$i<=20;$i++)
            $ownerIds[$i]=DB::ins(
                "INSERT INTO users (name,email,phone,password,role,status) VALUES (?,?,?,?,'owner','active')",
                ["Propriétaire $i","owner$i@darfood.dz","0550".str_pad($i,6,'0',STR_PAD_LEFT),password_hash('owner123',PASSWORD_DEFAULT)]
            );

        // ── Restaurants (20) ──────────────────────────────────────
        $rdata=[
         //  [ownIdx, name, slug, description, cat, city, phone, rating, featured, dfee, minOrd, dtime]
            [1,'Pizza Roma','pizza-roma','Authentique pizza italienne au feu de bois','Pizza','Alger','021456789',4.7,1,150,600,30],
            [2,'Burger House','burger-house','Burgers artisanaux et frites croustillantes','Burger','Oran','0411234567',4.5,1,100,500,25],
            [3,'Shawarma Palace','shawarma-palace','Authentique shawarma libanais mariné','Shawarma','Constantine','0311789456',4.8,1,80,400,20],
            [4,'El Tacos','el-tacos','Tacos mexicains et wraps fusion','Tacos','Alger','0550123456',4.3,0,120,550,35],
            [5,'Café Alger','cafe-alger','Café gourmet et pâtisseries fines','Café','Alger','0660987654',4.6,1,0,300,20],
            [6,'Boulangerie Zakia','boulangerie-zakia','Pain frais et viennoiseries artisanales','Boulangerie','Oran','0411555666',4.4,0,50,200,30],
            [7,'Dar Couscous','dar-couscous','Couscous traditionnel et plats kabyles','Traditionnel','Constantine','0770321654',4.9,1,200,800,45],
            [8,'Grillades Express','grillades-express','Grillades et viandes fraîches au charbon','Grill','Alger','0560147258',4.5,0,150,700,35],
            [9,'Pizza El Bahdja','pizza-el-bahdja','Pizzas algéro-italiennes au four','Pizza','Alger','0770963852',4.2,0,120,550,30],
            [10,'Royal Burger','royal-burger','Le burger noble façon Oran','Burger','Oran','0411741852',4.6,1,100,500,25],
            [11,'Sandwich Factory','sandwich-factory','Sandwiches et paninis maison','Burger','Oran','0660258369',4.1,0,80,400,20],
            [12,'Poulet Rôti Plus','poulet-roti-plus','Poulet rôti braisé et ses accompagnements','Grill','Alger','0555147369',4.4,0,100,400,30],
            [13,'Pâtisserie El Feth','patisserie-el-feth','Pâtisseries orientales et gâteaux de fête','Boulangerie','Constantine','0770456789',4.7,1,0,200,25],
            [14,'Fresh Juice Bar','fresh-juice-bar','Jus frais pressés et smoothies santé','Café','Alger','0550654321',4.5,0,0,300,15],
            [15,'El Kebab','el-kebab','Kebab grillé et sandwiches chauds','Shawarma','Oran','0411852963',4.3,0,80,400,20],
            [16,'BBQ Plaza','bbq-plaza','Barbecue américain et côtes levées','Grill','Alger','0660159753',4.5,1,150,700,40],
            [17,'Taco Fiesta','taco-fiesta','Tacos et burritos street food','Tacos','Alger','0550753159',4.2,0,100,450,25],
            [18,'Crêperie Madeleine','creperie-madeleine','Crêpes sucrées et salées à la française','Café','Constantine','0770159357',4.6,0,50,300,20],
            [19,'Le Divan Coffee','le-divan-coffee','Café spécialisé et torréfaction artisanale','Café','Alger','0660357159',4.8,1,0,200,15],
            [20,'El Tajine','el-tajine','Tajines et plats traditionnels algériens','Traditionnel','Oran','0411963741',4.7,1,180,900,50],
        ];

        $rIds=[];
        $bgColors=['FF5722','E53935','FF9800','4CAF50','795548','FFC107','9C27B0','E91E63','F44336','2196F3'];
        foreach($rdata as $idx=>$r){
            $n=$idx+1;
            $bg=$bgColors[$n%10];
            $hours='{"mon":"08:00-23:00","tue":"08:00-23:00","wed":"08:00-23:00","thu":"08:00-23:00","fri":"09:00-23:00","sat":"09:00-00:00","sun":"10:00-22:00"}';
            $rIds[$n]=DB::ins(
                "INSERT INTO restaurants (owner_id,name,slug,description,category,address,city,phone,logo,cover,delivery_fee,min_order,delivery_time,rating,rating_count,status,featured,opening_hours,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,DATE_SUB(NOW(),INTERVAL ? DAY))",
                [
                    $ownerIds[$r[0]], $r[1], $r[2], $r[3], $r[4],
                    "Rue ".($n+10).", ".$r[5], $r[5], $r[6],
                    "https://ui-avatars.com/api/?name=".urlencode($r[1])."&size=200&background={$bg}&color=fff&bold=true",
                    "https://picsum.photos/seed/rest{$n}/800/400",
                    $r[9], $r[10], $r[11], $r[7], rand(5,120),
                    'active', $r[8], $hours, rand(1,180)
                ]
            );
        }

        // ── Products (10 per restaurant = 200) ────────────────────
        $menuTemplates=[
            'Pizza'=>[
                ['Pizza Margherita','بيتزا مارغريتا','Tomate, mozzarella, basilic frais',850,null,'Pizzas',1],
                ['Pizza 4 Fromages','بيتزا أربعة جبن','Mozzarella, gorgonzola, parmesan, chèvre',1100,null,'Pizzas',1],
                ['Pizza Poulet','بيتزا دجاج','Poulet grillé, champignons, poivrons',950,850,'Pizzas',0],
                ['Pizza Viande','بيتزا لحم','Viande hachée épicée, oignons, tomate',1000,null,'Pizzas',0],
                ['Pizza Végétarienne','بيتزا نباتية','Légumes grillés, sauce tomate, fromage',900,800,'Pizzas',0],
                ['Calzone','كالزوني','Pizza pliée farcie au poulet et fromage',1050,null,'Spéciaux',0],
                ['Garlic Bread','خبز الثوم','Pain à l\'ail et persil grillé, 6pcs',350,null,'Accompagnements',0],
                ['Tiramisu','تيراميسو','Dessert italien café et mascarpone',450,null,'Desserts',0],
                ['Limonade Maison','عصير الليمون','Limonade fraîche maison',200,null,'Boissons',0],
                ['Soda 33cl','صودا','Coca-Cola, Fanta ou Sprite au choix',150,null,'Boissons',0],
            ],
            'Burger'=>[
                ['Classic Burger','برغر كلاسيك','Steak haché 150g, cheddar, salade, tomate',750,null,'Burgers',1],
                ['Double Smash','برغر مزدوج','Double steak smashé, sauce maison, cheddar',950,null,'Burgers',1],
                ['Chicken Crispy','برغر دجاج','Poulet frit croustillant, salade, mayo',800,700,'Burgers',0],
                ['BBQ Burger','برغر BBQ','Steak, sauce BBQ, bacon halal, oignons frits',1000,null,'Burgers',0],
                ['Veggie Burger','برغر نباتي','Galette de légumes, avocat, sauce verte',700,null,'Burgers',0],
                ['Frites Maison','بطاطا مقلية','Frites fraîches coupées à la main',300,null,'Accompagnements',0],
                ['Onion Rings','حلقات بصل','Rondelles d\'oignon panées et frites',350,null,'Accompagnements',0],
                ['Milkshake','ميلك شيك','Milkshake chocolat, vanille ou fraise',400,null,'Boissons',0],
                ['Nuggets 6pcs','نوجتس','Nuggets de poulet croustillants',400,350,'Accompagnements',0],
                ['Menu Complet','قائمة كاملة','Burger + Frites + Boisson',1100,950,'Menus',1],
            ],
            'Shawarma'=>[
                ['Shawarma Poulet','شاورما دجاج','Poulet mariné, légumes, sauce tarator',450,null,'Shawarmas',1],
                ['Shawarma Viande','شاورما لحم','Agneau et veau marinés aux épices',550,null,'Shawarmas',1],
                ['Shawarma Mix','شاورما مشكل','Mélange poulet et viande, double sauce',600,null,'Shawarmas',0],
                ['Assiette Shawarma','طبق شاورما','Shawarma + riz + salade + sauce',750,null,'Assiettes',0],
                ['Falafel Sandwich','ساندويش فلافل','Falafel maison, tahiné, légumes frais',350,null,'Sandwiches',0],
                ['Hummus','حمص','Houmous maison avec pita grillé',300,null,'Entrées',0],
                ['Fattoush','فتوش','Salade levantine fraîche',350,null,'Salades',0],
                ['Jus Orange','عصير برتقال','Jus d\'orange pressé frais',200,null,'Boissons',0],
                ['Thé Menthe','شاي نعناع','Thé vert à la menthe fraîche',150,null,'Boissons',0],
                ['Plateau Famille','طبق العائلة','4 shawarmas + 4 boissons + 2 entrées',2200,1900,'Plateaux',1],
            ],
            'Tacos'=>[
                ['Tacos Poulet','تاكوس دجاج','Frites, escalope, sauce fromagère',450,null,'Tacos',1],
                ['Tacos Viande','تاكوس لحم','Frites, viande hachée, sauce BBQ',500,null,'Tacos',1],
                ['Tacos Mix','تاكوس مشكل','Poulet et viande, double sauce',550,null,'Tacos',0],
                ['Burrito','بوريتو','Grande tortilla, riz, haricots, viande',600,null,'Autres',0],
                ['Quesadilla','كيساديلا','Tortilla grillée, fromage fondu, poulet',500,null,'Autres',0],
                ['Nachos','ناتشوز','Chips maïs, salsa, crème, fromage',400,null,'Entrées',0],
                ['Frites Épicées','بطاطا حارة','Frites sauce piquante maison',300,null,'Accompagnements',0],
                ['Churros','تشوروس','Churros frits, sucre, sauce chocolat',350,null,'Desserts',0],
                ['Horchata','هورتشاتا','Boisson riz et cannelle',200,null,'Boissons',0],
                ['Menu Tacos','قائمة تاكوس','Tacos + Frites + Boisson',700,600,'Menus',1],
            ],
            'Café'=>[
                ['Expresso','إسبريسو','Café expresso simple ou double',150,null,'Café chaud',1],
                ['Cappuccino','كابوتشينو','Expresso, mousse de lait, cannelle',250,null,'Café chaud',1],
                ['Café Latte','لاتيه','Expresso allongé au lait crémeux',280,null,'Café chaud',0],
                ['Chocolat Chaud','شوكولاتة ساخنة','Chocolat belge fondu au lait entier',300,null,'Chaud',0],
                ['Thé Vert','شاي أخضر','Thé vert premium à la menthe',150,null,'Thé',0],
                ['Croissant','كرواسان','Croissant feuilleté au beurre pur',250,null,'Viennoiseries',0],
                ['Pain au Chocolat','ألم شوكولاتة','Pâte feuilletée, 2 barres chocolat',280,null,'Viennoiseries',0],
                ['Smoothie Fruits','سموذي فواكه','Mangue, fraise, banane mixés',350,null,'Boissons froides',0],
                ['Limonade Menthe','ليمونادة نعناع','Citron pressé, menthe, eau gazeuse',220,null,'Boissons froides',0],
                ['Cheese Cake','تشيز كيك','Cheese cake new-yorkais maison',400,null,'Pâtisseries',0],
            ],
            'Boulangerie'=>[
                ['Baguette Tradition','باغيت','Baguette artisanale farine T65',80,null,'Pain',1],
                ['Pain de Campagne','خبز الريف','Pain au levain naturel',180,null,'Pain',0],
                ['Croissant Beurre','كرواسان','Croissant pur beurre feuilleté',200,null,'Viennoiseries',1],
                ['Pain au Raisin','خبز بالزبيب','Escargot brioché aux raisins secs',180,null,'Viennoiseries',0],
                ['Chausson Pommes','شوسون','Pâte feuilletée, compote de pommes',220,null,'Viennoiseries',0],
                ['Madeleine x6','مادلين','Madeleines moelleuses au citron',250,null,'Gâteaux',0],
                ['Tarte Citron','تارت ليمون','Tarte fine, crème citron, meringue',380,null,'Tartes',0],
                ['Mille-feuille','ميل فوي','Feuilleté, crème pâtissière vanille',350,null,'Gâteaux',0],
                ['Brioche 500g','بريوش','Brioche moelleuse au beurre',450,null,'Brioche',0],
                ['Sandwich Mixte','ساندويش','Baguette, jambon halal, fromage, légumes',300,null,'Sandwiches',0],
            ],
            'Traditionnel'=>[
                ['Couscous Poulet','كسكسي بالدجاج','Couscous aux légumes et poulet fermier',900,null,'Plats Principaux',1],
                ['Couscous Agneau','كسكسي بالخروف','Couscous royal agneau et merguez',1100,null,'Plats Principaux',1],
                ['Chorba Frik','شوربة الفريك','Soupe traditionnelle algérienne au frik',400,null,'Soupes',1],
                ['Bourek x4','بوراك','Rouleaux brick à la viande hachée',500,null,'Entrées',0],
                ['Tajine Zitoune','طاجين الزيتون','Poulet aux olives et citron confit',950,null,'Plats Principaux',0],
                ['Dolma','دولمة','Légumes farcis à la viande et riz',850,null,'Plats Principaux',0],
                ['Mechoui (portion)','مشوي','Agneau rôti aux épices, portion',900,null,'Spéciaux',0],
                ['Kalb El Louz','قلب اللوز','Gâteau algérien aux amandes et semoule',350,null,'Desserts',1],
                ['Makrout x6','مقروط','Gâteau semoule et dattes',300,null,'Desserts',0],
                ['Lben 50cl','لبن','Lait fermenté traditionnel algérien',150,null,'Boissons',0],
            ],
            'Grill'=>[
                ['Côtelettes Agneau','كوتليت خروف','Côtelettes agneau grillées aux herbes',1200,null,'Grillades',1],
                ['Brochettes Veau x4','مشويات لحم','Brochettes veau marinées',900,null,'Grillades',1],
                ['Poulet Entier','دجاجة كاملة','Poulet entier mariné et grillé',1100,null,'Grillades',0],
                ['Merguez x6','مرقاز','Merguez maison épicées',650,null,'Grillades',0],
                ['Mix Grill (2 pers)','مشكل مشوي','Assortiment de viandes grillées pour 2',1800,1600,'Grillades',1],
                ['Salade Méchouia','سلطة المشوية','Salade légumes grillés mixés',350,null,'Salades',0],
                ['Riz Pilaf','أرز بلاف','Riz aux épices et raisins secs',300,null,'Accompagnements',0],
                ['Pain Semo','خبز الشعير','Pain traditionnel à la semoule',100,null,'Pain',0],
                ['Thé Menthe','شاي نعناع','Thé chaud à la menthe fraîche',150,null,'Boissons',0],
                ['Plat du Chef','طبق الشيف','Sélection du chef + 3 accompagnements',1400,1200,'Spéciaux',0],
            ],
        ];

        $catMap=[1=>'Pizza',2=>'Burger',3=>'Shawarma',4=>'Tacos',5=>'Café',6=>'Boulangerie',7=>'Traditionnel',8=>'Grill',9=>'Pizza',10=>'Burger',11=>'Burger',12=>'Grill',13=>'Boulangerie',14=>'Café',15=>'Shawarma',16=>'Grill',17=>'Tacos',18=>'Café',19=>'Café',20=>'Traditionnel'];

        $pseed=1;
        foreach($rIds as $n=>$rid){
            $ck=$catMap[$n]??'Pizza';
            $prods=$menuTemplates[$ck]??$menuTemplates['Pizza'];
            foreach($prods as $p){
                DB::ins(
                    "INSERT INTO products (restaurant_id,name,name_ar,description,price,discount_price,image,menu_category,is_available,is_featured,created_at) VALUES (?,?,?,?,?,?,?,?,1,?,DATE_SUB(NOW(),INTERVAL ? DAY))",
                    [$rid,$p[0],$p[1],$p[2],$p[3],$p[4],"https://picsum.photos/seed/prod{$pseed}/400/300",$p[5],$p[6],rand(1,120)]
                );
                $pseed++;
            }
        }

        // ── Customers (50) ────────────────────────────────────────
        $cnames=['Ahmed Benali','Fatima Khedim','Mohamed Hamdi','Sara Belounis','Youcef Ziani','Amina Taleb','Karim Rahmani','Nadia Bensalem','Omar Ferhat','Lina Chebli','Rachid Boudiaf','Yasmine Aouadi','Khalid Meddour','Sonia Hadjib','Tarek Messaoudi','Houda Oulad','Bilal Mahdi','Rania Bouazza','Amine Kerboub','Sabrina Nekkache','Ryad Ghezali','Kahina Amrani','Sofiane Meziani','Assia Laib','Walid Senhadji','Meriem Salhi','Adel Krimi','Nawel Hadj','Farouk Bouzid','Imane Berkane','Hassan Boureghda','Djamila Aissaoui','Reda Kaci','Chaima Belhadj','Mehdi Saad','Zineb Hamdani','Lotfi Benamar','Nour Bellil','Samir Benkhelidja','Safa Hedna','Mourad Ghali','Lilia Zidane','Hichem Brahim','Amira Terki','Nadir Moula','Farida Achour','Djamel Ouali','Rima Khaldi','Yazid Benhadj','Karima Meliani'];

        $cids=[];
        foreach($cnames as $i=>$cn){
            $cids[]=DB::ins(
                "INSERT INTO users (name,email,phone,password,role,status,created_at) VALUES (?,?,?,?,'customer','active',DATE_SUB(NOW(),INTERVAL ? DAY))",
                [$cn,'customer'.($i+1).'@darfood.dz','0600'.str_pad($i+1,6,'0',STR_PAD_LEFT),password_hash('customer123',PASSWORD_DEFAULT),rand(1,300)]
            );
        }

        // ── Drivers (20) ──────────────────────────────────────────
        $ddata=[
            ['Amar Boussad','0551111001','moto','16-12345-01'],['Hamza Khelil','0551111002','moto','31-23456-02'],
            ['Fares Belkacem','0551111003','moto','16-34567-03'],['Ilyas Djouadi','0551111004','voiture','16-45678-04'],
            ['Malek Laouari','0551111005','moto','31-56789-05'],['Nasim Ghoul','0551111006','moto','25-67890-06'],
            ['Samy Berber','0551111007','moto','16-78901-07'],['Tarik Djebbar','0551111008','voiture','31-89012-08'],
            ['Yazid Mahfouf','0551111009','moto','16-90123-09'],['Anwar Khalil','0551111010','moto','25-01234-10'],
            ['Brahim Ouled','0551111011','voiture','16-12346-11'],['Chakib Feddi','0551111012','moto','31-23457-12'],
            ['Djamel Serrai','0551111013','moto','16-34568-13'],['Ezzedine Amara','0551111014','moto','25-45679-14'],
            ['Fouad Belaid','0551111015','voiture','16-56780-15'],['Ghiles Sahed','0551111016','moto','31-67891-16'],
            ['Hakim Touati','0551111017','moto','16-78902-17'],['Ibrahim Yahi','0551111018','moto','25-89013-18'],
            ['Jamil Zerrouk','0551111019','voiture','16-90124-19'],['Khaled Amrouche','0551111020','moto','31-01235-20'],
        ];

        $statuses=['online','offline','offline'];
        foreach($ddata as $i=>$d){
            $uid=DB::ins(
                "INSERT INTO users (name,email,phone,password,role,status) VALUES (?,?,?,?,'driver','active')",
                [$d[0],'driver'.($i+1).'@darfood.dz',$d[1],password_hash('driver123',PASSWORD_DEFAULT)]
            );
            DB::ins(
                "INSERT INTO drivers (user_id,name,phone,vehicle_type,vehicle_plate,status,rating,total_deliveries,total_earnings,created_at) VALUES (?,?,?,?,?,?,?,?,?,DATE_SUB(NOW(),INTERVAL ? DAY))",
                [$uid,$d[0],$d[1],$d[2],$d[3],$statuses[$i%3],number_format(4.0+rand(0,10)/10,1),rand(10,300),rand(5000,80000),rand(1,180)]
            );
        }

        // ── Sample Reviews ────────────────────────────────────────
        $comments=['Excellent service, livraison rapide !','Très bon repas, je recommande vraiment.','Qualité exceptionnelle, portions généreuses.','Parfait, encore une fois !','Un peu long mais ça valait l\'attente.','Super bon, ma famille a adoré !','Meilleur restaurant de la ville !','Saveurs authentiques et délicieuses.','Toujours satisfait de cette adresse.','Très frais et bien présenté.','Je recommande vivement, service impeccable !','Rapport qualité-prix imbattable.'];

        foreach($rIds as $n=>$rid){
            $nr=rand(5,18);
            for($i=0;$i<$nr;$i++){
                $cid=$cids[array_rand($cids)];
                $rating=rand(3,5);
                $days=rand(1,180);
                DB::ins(
                    "INSERT INTO reviews (customer_id,restaurant_id,rating,comment,created_at) VALUES (?,?,?,?,DATE_SUB(NOW(),INTERVAL ? DAY))",
                    [$cid,$rid,$rating,$comments[array_rand($comments)],$days]
                );
            }
            RModel::recalcRating($rid);
        }

        // ── Sample Orders ─────────────────────────────────────────
        $statuses2=['pending','accepted','preparing','ready','delivered','delivered','delivered','cancelled'];
        foreach(array_slice($cids,0,30) as $cid){
            $nr=rand(1,5);
            for($j=0;$j<$nr;$j++){
                $rnum=array_rand($rIds);
                $rid=$rIds[$rnum];
                $restaurant=DB::row("SELECT * FROM restaurants WHERE id=?",[$rid]);
                $prods=DB::all("SELECT * FROM products WHERE restaurant_id=? LIMIT 5",[$rid]);
                if(empty($prods)) continue;
                $st=$statuses2[array_rand($statuses2)];
                $dfee=(float)$restaurant['delivery_fee'];
                $subtotal=0;
                $items=array_slice($prods,0,rand(1,3));
                foreach($items as $p) $subtotal+=$p['price']*rand(1,2);
                $total=$subtotal+$dfee;
                $days=rand(1,60);
                $oid=DB::ins(
                    "INSERT INTO orders (customer_id,restaurant_id,status,delivery_type,address,phone,subtotal,delivery_fee,discount,total,payment_method,payment_status,created_at,updated_at) VALUES (?,?,?,'delivery','Rue Exemple, Alger','0555000000',?,?,0,?,'cash',?,DATE_SUB(NOW(),INTERVAL ? DAY),DATE_SUB(NOW(),INTERVAL ? DAY))",
                    [$cid,$rid,$st,$subtotal,$dfee,$total,$st==='delivered'?'paid':'pending',$days,$days]
                );
                foreach($items as $p){
                    DB::ins("INSERT INTO order_items (order_id,product_id,product_name,quantity,unit_price,options,extras) VALUES (?,?,?,?,?,'[]','[]')",[$oid,$p['id'],$p['name'],1,$p['price']]);
                }
            }
        }

        DB::ins("INSERT INTO audit_logs (user_id,action,details,ip,created_at) VALUES (?,?,?,?,NOW())",[$adminId,'install','Demo data seeded','127.0.0.1']);
    }
}

// Bootstrap setup
try { Setup::run(); }
catch(Exception $e) { if(DEBUG_MODE) die("Setup error: ".$e->getMessage()); }

// ====================================================================
// SECTION 7 — ACTIONS (POST / AJAX handlers)
// ====================================================================

function handleAction(): void
{
    $act = clean($_POST['action'] ?? $_GET['action'] ?? '');

    $safeMethods=['logout','get_cart','get_order_status','mark_notifs_read'];
    if(!in_array($act,$safeMethods) && !csrf_ok())
        json_out(['ok'=>false,'msg'=>'Token invalide.'],403);

    match($act){
        'login'               => actLogin(),
        'register'            => actRegister(),
        'logout'              => doLogout(),
        'add_to_cart'         => actAddCart(),
        'update_cart'         => actUpdateCart(),
        'apply_coupon'        => actCoupon(),
        'place_order'         => actPlaceOrder(),
        'restaurant_order'    => actRestaurantOrder(),
        'assign_driver'       => actAssignDriver(),
        'driver_accept'       => actDriverAccept(),
        'driver_pickup'       => actDriverPickup(),
        'driver_deliver'      => actDriverDeliver(),
        'add_product'         => actAddProduct(),
        'edit_product'        => actEditProduct(),
        'delete_product'      => actDeleteProduct(),
        'update_restaurant'   => actUpdateRestaurant(),
        'leave_review'        => actLeaveReview(),
        'update_profile'      => actUpdateProfile(),
        'mark_notifs_read'    => doMarkNotifs(),
        'get_cart'            => json_out(['ok'=>true,'count'=>Cart::count(),'total'=>Cart::total()]),
        'admin_user_status'   => actAdminUserStatus(),
        'admin_restaurant'    => actAdminRestaurant(),
        default               => json_out(['ok'=>false,'msg'=>'Action inconnue.'],400),
    };
}

function actLogin(): never
{
    $email=clean($_POST['email']??'');
    $pass=$_POST['password']??'';
    $rem=isset($_POST['remember']);

    if(!$email||!$pass){ flash('error','Email et mot de passe requis.'); redirect('?page=login'); }

    if(Auth::login($email,$pass,$rem)){
        $ret=clean($_POST['ret']??'');
        redirect(match(Auth::role()){ 'admin'=>'?page=admin','owner'=>'?page=restaurant-dashboard','driver'=>'?page=driver-dashboard', default=>($ret?:'?page=home') });
    }
    flash('error','Email ou mot de passe incorrect.');
    redirect('?page=login');
}

function actRegister(): never
{
    $name=clean($_POST['name']??'');
    $email=clean($_POST['email']??'');
    $phone=clean($_POST['phone']??'');
    $pass=$_POST['password']??'';
    $conf=$_POST['confirm_password']??'';

    if(!$name||!$email||!$pass){ flash('error','Tous les champs obligatoires.'); redirect('?page=register'); }
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)){ flash('error','Email invalide.'); redirect('?page=register'); }
    if(strlen($pass)<6){ flash('error','Mot de passe min 6 caractères.'); redirect('?page=register'); }
    if($pass!==$conf){ flash('error','Mots de passe non identiques.'); redirect('?page=register'); }

    $uid=Auth::register(['name'=>$name,'email'=>$email,'phone'=>$phone,'password'=>$pass]);
    if(!$uid){ flash('error','Email déjà utilisé.'); redirect('?page=register'); }
    Auth::login($email,$pass);
    flash('success','Bienvenue sur DarFood, '.$name.' !');
    redirect('?page=home');
}

function doLogout(): never { Auth::logout(); redirect('?page=home'); }

function actAddCart(): never
{
    if(!Auth::check()) json_out(['ok'=>false,'msg'=>'Connexion requise.','login'=>true],401);
    $pid=(int)($_POST['pid']??0);
    $qty=max(1,(int)($_POST['qty']??1));
    $p=PModel::get($pid);
    if(!$p) json_out(['ok'=>false,'msg'=>'Produit introuvable.'],404);
    $price=(float)($p['discount_price']??0)?:$p['price'];
    $ok=Cart::add(['pid'=>$pid,'rid'=>(int)$p['restaurant_id'],'name'=>$p['name'],'price'=>$price,'qty'=>$qty,'img'=>$p['image']]);
    if(!$ok) json_out(['ok'=>false,'msg'=>'Votre panier contient des articles d\'un autre restaurant.','diffRest'=>true]);
    json_out(['ok'=>true,'msg'=>$p['name'].' ajouté !','count'=>Cart::count(),'total'=>Cart::total()]);
}

function actUpdateCart(): never
{
    $pid=clean($_POST['pid']??'');
    if($pid==='clear'){ Cart::clear(); json_out(['ok'=>true,'count'=>0,'total'=>0,'empty'=>true]); }
    $pid=(int)$pid; $qty=(int)($_POST['qty']??0);
    Cart::update($pid,$qty);
    $dfee=0;
    if(!Cart::empty()){ $r=RModel::get(Cart::rid()); $dfee=(float)($r['delivery_fee']??0); }
    $sub=Cart::total();
    json_out(['ok'=>true,'count'=>Cart::count(),'subtotal'=>$sub,'dfee'=>$dfee,'total'=>$sub+$dfee,'empty'=>Cart::empty()]);
}

function actCoupon(): never
{
    $code=clean($_POST['code']??'');
    $sub=(float)($_POST['subtotal']??0);
    $r=Coupon::validate($code,$sub);
    json_out($r);
}

function actPlaceOrder(): never
{
    Auth::guard();
    if(Cart::empty()){ flash('info','Panier vide.'); redirect('?page=home'); }

    $cart=Cart::get();
    $rest=RModel::get($cart['rid']);
    if(!$rest){ flash('error','Restaurant introuvable.'); redirect('?page=cart'); }

    $dtype=clean($_POST['delivery_type']??'delivery');
    $addr=clean($_POST['address']??'');
    $phone=clean($_POST['phone']??'');
    $pmethod=in_array($_POST['payment_method']??'cash',['cash','card'])?$_POST['payment_method']:'cash';
    $notes=clean($_POST['notes']??'');
    $couponCode=strtoupper(clean($_POST['coupon_code']??''));

    if($dtype==='delivery'&&!$addr){ flash('error','Adresse requise.'); redirect('?page=checkout'); }

    $sub=Cart::total();
    $dfee=$dtype==='delivery'?(float)$rest['delivery_fee']:0;
    $disc=0; $couponId=null;

    if($couponCode){
        $cv=Coupon::validate($couponCode,$sub);
        if($cv['ok']){ $disc=$cv['disc']; $couponId=$cv['id']; }
    }

    $total=max(0,$sub+$dfee-$disc);

    DB::begin();
    try{
        $oid=OModel::create(['cid'=>Auth::id(),'rid'=>$cart['rid'],'dtype'=>$dtype,'address'=>$dtype==='delivery'?$addr:$rest['address'],'phone'=>$phone?:Auth::user()['phone'],'subtotal'=>$sub,'dfee'=>$dfee,'discount'=>$disc,'total'=>$total,'pmethod'=>$pmethod,'coupon'=>$couponCode?:null,'notes'=>$notes]);

        foreach($cart['items'] as $item)
            OModel::addItem(['oid'=>$oid,'pid'=>$item['pid'],'pname'=>$item['name'],'qty'=>$item['qty'],'uprice'=>$item['price']]);

        if($couponId) Coupon::use($couponId);
        DB::ins("INSERT INTO payments (order_id,method,amount,status) VALUES (?,?,?,'pending')",[$oid,$pmethod,$total]);

        Notif::push(Auth::id(),'order','Commande confirmée',"Votre commande #$oid a été envoyée à ".$rest['name'].".");
        Notif::push($rest['owner_id'],'order','Nouvelle commande !','Commande #'.$oid.' · '.money($total));

        DB::commit();
        Cart::clear();
        Auth::log(Auth::id(),'place_order',"#$oid");
        flash('success',"Commande #$oid passée avec succès !");
        redirect("?page=order&id=$oid");
    }catch(Exception $e){
        DB::back();
        flash('error',DEBUG_MODE?$e->getMessage():'Erreur lors de la commande.');
        redirect('?page=checkout');
    }
}

function actRestaurantOrder(): never
{
    Auth::guard('owner');
    $oid=(int)($_POST['oid']??0);
    $sub=clean($_POST['sub']??'');
    $rest=RModel::byOwner(Auth::id());
    if(!$rest) json_out(['ok'=>false,'msg'=>'Non autorisé.'],403);
    $order=OModel::get($oid);
    if(!$order||$order['restaurant_id']!=$rest['id']) json_out(['ok'=>false,'msg'=>'Commande introuvable.'],404);

    $newSt=match($sub){
        'accept'=>'accepted','reject'=>'rejected','prepare'=>'preparing','ready'=>'ready',default=>''
    };
    if(!$newSt) json_out(['ok'=>false,'msg'=>'Sous-action invalide.'],400);

    OModel::setStatus($oid,$newSt);

    $msgs=['accepted'=>"Commande #$oid acceptée !",'rejected'=>"Commande #$oid refusée.",'preparing'=>"Commande #$oid en préparation.",'ready'=>"Commande #$oid est prête !"];
    Notif::push($order['customer_id'],'order','Mise à jour commande',$msgs[$newSt]??'');

    if($newSt==='ready'&&$order['delivery_type']==='delivery'){
        $driver=DB::row("SELECT * FROM drivers WHERE status='online' ORDER BY RAND() LIMIT 1");
        if($driver){ OModel::assignDriver($oid,$driver['id']); Notif::push($driver['user_id'],'delivery','Nouvelle livraison !','Commande #'.$oid); }
    }

    json_out(['ok'=>true,'msg'=>'Statut mis à jour.','status'=>$newSt]);
}

function actAssignDriver(): never
{
    Auth::guard('owner');
    $oid=(int)($_POST['oid']??0); $did=(int)($_POST['did']??0);
    $rest=RModel::byOwner(Auth::id());
    $order=OModel::get($oid);
    if(!$order||!$rest||$order['restaurant_id']!=$rest['id']) json_out(['ok'=>false,'msg'=>'Non autorisé.'],403);
    OModel::assignDriver($oid,$did);
    $drv=DB::row("SELECT * FROM drivers WHERE id=?",[$did]);
    if($drv) Notif::push($drv['user_id'],'delivery','Livraison assignée','Commande #'.$oid);
    json_out(['ok'=>true,'msg'=>'Livreur assigné.']);
}

function actDriverAccept(): never
{
    Auth::guard('driver');
    $oid=(int)($_POST['oid']??0);
    $drv=DB::row("SELECT * FROM drivers WHERE user_id=?",[Auth::id()]);
    if(!$drv) json_out(['ok'=>false,'msg'=>'Non autorisé.'],403);
    $order=OModel::get($oid);
    if(!$order||$order['status']!=='ready') json_out(['ok'=>false,'msg'=>'Non disponible.'],404);
    OModel::assignDriver($oid,$drv['id']);
    json_out(['ok'=>true,'msg'=>'Livraison acceptée !']);
}

function actDriverPickup(): never
{
    Auth::guard('driver');
    $oid=(int)($_POST['oid']??0);
    $drv=DB::row("SELECT * FROM drivers WHERE user_id=?",[Auth::id()]);
    $order=OModel::get($oid);
    if(!$order||$order['driver_id']!=$drv['id']) json_out(['ok'=>false,'msg'=>'Non autorisé.'],403);
    OModel::setStatus($oid,'picked_up');
    DB::run("UPDATE deliveries SET status='picked_up',picked_at=NOW() WHERE order_id=?",[$oid]);
    Notif::push($order['customer_id'],'delivery','En route !','Votre commande #'.$oid.' est en chemin !');
    json_out(['ok'=>true,'msg'=>'Ramassage confirmé.']);
}

function actDriverDeliver(): never
{
    Auth::guard('driver');
    $oid=(int)($_POST['oid']??0);
    $drv=DB::row("SELECT * FROM drivers WHERE user_id=?",[Auth::id()]);
    $order=OModel::get($oid);
    if(!$order||$order['driver_id']!=$drv['id']) json_out(['ok'=>false,'msg'=>'Non autorisé.'],403);
    OModel::setStatus($oid,'delivered');
    DB::run("UPDATE deliveries SET status='delivered',delivered_at=NOW() WHERE order_id=?",[$oid]);
    DB::run("UPDATE payments SET status='completed' WHERE order_id=?",[$oid]);
    DB::run("UPDATE drivers SET total_deliveries=total_deliveries+1,total_earnings=total_earnings+?,status='online' WHERE id=?",[(float)$order['delivery_fee'],$drv['id']]);
    Notif::push($order['customer_id'],'delivered','Livré !','Commande #'.$oid.' livrée. Bon appétit !');
    json_out(['ok'=>true,'msg'=>'Livraison confirmée.']);
}

function actAddProduct(): never
{
    Auth::guard('owner');
    $rest=RModel::byOwner(Auth::id());
    if(!$rest) redirect('?page=restaurant-dashboard');
    PModel::create(['restaurant_id'=>$rest['id'],'name'=>clean($_POST['name']??''),'name_ar'=>clean($_POST['name_ar']??''),'description'=>clean($_POST['description']??''),'price'=>(float)($_POST['price']??0),'discount_price'=>!empty($_POST['discount_price'])?(float)$_POST['discount_price']:null,'image'=>clean($_POST['image']??''),'menu_category'=>clean($_POST['menu_category']??'Principal'),'is_available'=>isset($_POST['is_available'])?1:0,'is_featured'=>isset($_POST['is_featured'])?1:0]);
    flash('success','Produit ajouté.');
    redirect('?page=restaurant-dashboard&tab=menu');
}

function actEditProduct(): never
{
    Auth::guard('owner');
    $pid=(int)($_POST['pid']??0); $rest=RModel::byOwner(Auth::id()); $prod=PModel::get($pid);
    if(!$prod||!$rest||$prod['restaurant_id']!=$rest['id']){ flash('error','Non autorisé.'); redirect('?page=restaurant-dashboard&tab=menu'); }
    PModel::update($pid,['name'=>clean($_POST['name']??$prod['name']),'name_ar'=>clean($_POST['name_ar']??''),'description'=>clean($_POST['description']??''),'price'=>(float)($_POST['price']??$prod['price']),'discount_price'=>!empty($_POST['discount_price'])?(float)$_POST['discount_price']:null,'image'=>clean($_POST['image']??$prod['image']),'menu_category'=>clean($_POST['menu_category']??'Principal'),'is_available'=>isset($_POST['is_available'])?1:0,'is_featured'=>isset($_POST['is_featured'])?1:0]);
    flash('success','Produit modifié.');
    redirect('?page=restaurant-dashboard&tab=menu');
}

function actDeleteProduct(): never
{
    Auth::guard('owner');
    $pid=(int)($_POST['pid']??0); $rest=RModel::byOwner(Auth::id()); $prod=PModel::get($pid);
    if($prod&&$rest&&$prod['restaurant_id']==$rest['id']){ PModel::delete($pid); json_out(['ok'=>true,'msg'=>'Produit supprimé.']); }
    json_out(['ok'=>false,'msg'=>'Non autorisé.'],403);
}

function actUpdateRestaurant(): never
{
    Auth::guard('owner');
    $rest=RModel::byOwner(Auth::id());
    if(!$rest) redirect('?page=restaurant-dashboard');
    RModel::update($rest['id'],['name'=>clean($_POST['name']??$rest['name']),'description'=>clean($_POST['description']??''),'address'=>clean($_POST['address']??''),'phone'=>clean($_POST['phone']??''),'delivery_fee'=>(float)($_POST['delivery_fee']??0),'min_order'=>(float)($_POST['min_order']??0),'delivery_time'=>(int)($_POST['delivery_time']??30)]);
    flash('success','Restaurant mis à jour.');
    redirect('?page=restaurant-dashboard&tab=settings');
}

function actLeaveReview(): never
{
    Auth::guard('customer');
    $oid=(int)($_POST['oid']??0); $rating=min(5,max(1,(int)($_POST['rating']??5))); $comment=clean($_POST['comment']??'');
    $order=OModel::get($oid);
    if(!$order||$order['customer_id']!=Auth::id()||$order['status']!=='delivered'){ flash('error','Impossible.'); redirect('?page=orders'); }
    if(RevModel::hasReviewed(Auth::id(),$oid)){ flash('info','Avis déjà publié.'); redirect("?page=order&id=$oid"); }
    RevModel::create(['cid'=>Auth::id(),'rid'=>$order['restaurant_id'],'oid'=>$oid,'rating'=>$rating,'comment'=>$comment]);
    RModel::recalcRating($order['restaurant_id']);
    flash('success','Avis publié. Merci !');
    redirect("?page=order&id=$oid");
}

function actUpdateProfile(): never
{
    Auth::guard();
    $name=clean($_POST['name']??''); $phone=clean($_POST['phone']??''); $address=clean($_POST['address']??'');
    DB::run("UPDATE users SET name=?,phone=?,address=? WHERE id=?",[$name,$phone,$address,Auth::id()]);
    if(!empty($_POST['new_password'])){
        $u=Auth::user();
        if(password_verify($_POST['current_password']??'',$u['password'])) DB::run("UPDATE users SET password=? WHERE id=?",[password_hash($_POST['new_password'],PASSWORD_DEFAULT),Auth::id()]);
        else { flash('error','Mot de passe actuel incorrect.'); redirect('?page=profile'); }
    }
    $_SESSION['uname']=$name;
    flash('success','Profil mis à jour.');
    redirect('?page=profile');
}

function doMarkNotifs(): never
{
    if(Auth::check()) Notif::markRead(Auth::id());
    json_out(['ok'=>true]);
}

function actAdminUserStatus(): never
{
    Auth::guard('admin');
    $uid=(int)($_POST['uid']??0); $st=in_array($_POST['status']??'',['active','inactive','banned'])?$_POST['status']:'active';
    DB::run("UPDATE users SET status=? WHERE id=?",[$st,$uid]);
    json_out(['ok'=>true]);
}

function actAdminRestaurant(): never
{
    Auth::guard('admin');
    $rid=(int)($_POST['rid']??0); $st=in_array($_POST['status']??'',['active','inactive','pending'])?$_POST['status']:'active';
    DB::run("UPDATE restaurants SET status=? WHERE id=?",[$st,$rid]);
    json_out(['ok'=>true]);
}

/* ── Restaurant Owner Dashboard ──────────────────────────────── */
function pageRestaurantDashboard(): void
{
    Auth::guard('owner');
    $rest=RModel::byOwner(Auth::id());
    if(!$rest){ flash('error','Vous n\'avez pas de restaurant associé.'); redirect('?page=home'); }

    $tab=clean($_GET['tab']??'orders');
    $stats=OModel::stats($rest['id']);
    $orders=OModel::byRestaurant($rest['id'],'',50);
    $products=PModel::byRestaurant($rest['id']);
    $drivers=DB::all("SELECT d.*,u.name uname FROM drivers d LEFT JOIN users u ON d.user_id=u.id WHERE d.status='online' LIMIT 20");
    $reviews=RevModel::byRestaurant($rest['id'],10);
    $recentOrders=OModel::byRestaurant($rest['id'],'pending',10);

    $statusLabels=['pending'=>'En attente','accepted'=>'Acceptée','preparing'=>'Préparation','ready'=>'Prête','assigned'=>'Livreur assigné','picked_up'=>'En route','delivered'=>'Livrée','cancelled'=>'Annulée','rejected'=>'Refusée'];

    header_html('Mon Restaurant — '.$rest['name']);
    ?>
<div class="dash-layout">

<!-- Sidebar -->
<div class="sidebar">
  <div class="sidebar-brand"><i class="fa-solid fa-fire me-2"></i><span class="dar">Dar</span>Food</div>
  <nav class="snav pt-2">
    <div class="snav-lbl">Restaurant</div>
    <a href="?page=restaurant-dashboard" class="<?=$tab==='orders'?'active':''?>"><i class="fa-solid fa-bell"></i>Commandes <span style="background:var(--brand);color:#fff;border-radius:10px;padding:1px 7px;font-size:.72rem;margin-left:auto"><?=$stats['pending']?></span></a>
    <a href="?page=restaurant-dashboard&tab=menu" class="<?=$tab==='menu'?'active':''?>"><i class="fa-solid fa-utensils"></i>Menu</a>
    <a href="?page=restaurant-dashboard&tab=analytics" class="<?=$tab==='analytics'?'active':''?>"><i class="fa-solid fa-chart-bar"></i>Analytiques</a>
    <a href="?page=restaurant-dashboard&tab=reviews" class="<?=$tab==='reviews'?'active':''?>"><i class="fa-solid fa-star"></i>Avis</a>
    <a href="?page=restaurant-dashboard&tab=settings" class="<?=$tab==='settings'?'active':''?>"><i class="fa-solid fa-gear"></i>Paramètres</a>
    <div class="snav-lbl">Navigation</div>
    <a href="?page=restaurant&id=<?=$rest['id']?>"><i class="fa-solid fa-eye"></i>Voir la page</a>
    <a href="?page=home"><i class="fa-solid fa-home"></i>Accueil</a>
    <a href="?action=logout&_csrf=<?=csrf_token()?>" style="color:var(--danger)!important"><i class="fa-solid fa-right-from-bracket" style="color:var(--danger)"></i>Déconnexion</a>
  </nav>
</div>

<!-- Main -->
<div class="dash-main">

<!-- Header -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
  <div>
    <h1 style="font-size:1.4rem;font-weight:800;margin:0"><?=e($rest['name'])?></h1>
    <div style="color:var(--muted);font-size:.85rem"><?=e($rest['category'])?> · <?=e($rest['city'])?></div>
  </div>
  <div class="d-flex align-items-center gap-3">
    <div><?=stars($rest['rating'])?> <span style="font-weight:700"><?=number_format($rest['rating'],1)?></span></div>
    <span style="background:<?=$rest['status']==='active'?'var(--success)':'var(--danger)'?>;color:#fff;padding:4px 12px;border-radius:20px;font-size:.78rem;font-weight:700"><?=$rest['status']==='active'?'Actif':'Inactif'?></span>
  </div>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
  <?php foreach([
    ['Aujourd\'hui','fa-sun','#FEF3C7','#D97706',money($stats['today']),''],
    ['Cette semaine','fa-calendar','#DBEAFE','#2563EB',money($stats['week']),''],
    ['Ce mois','fa-chart-line','#EDE9FE','#7C3AED',money($stats['month']),''],
    ['Total Cmds','fa-receipt','#D1FAE5','#059669',$stats['total'],'commandes'],
    ['En attente','fa-clock','#FEE2E2','#DC2626',$stats['pending'],'actives'],
  ] as [$lbl,$ic,$bg,$col,$val,$unit]): ?>
  <div class="col-sm-6 col-lg">
    <div class="stat-card">
      <div class="stat-icon" style="background:<?=$bg?>;color:<?=$col?>"><i class="fa-solid <?=$ic?>"></i></div>
      <div><div class="stat-val"><?=$val?></div><div class="stat-lbl"><?=$lbl?> <?=$unit?></div></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php if($tab==='orders'): ?>
<!-- Orders Tab -->
<div class="card-df overflow-hidden">
  <div style="padding:18px 22px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
    <h5 class="fw-700 mb-0"><i class="fa-solid fa-bell text-brand me-2"></i>Commandes</h5>
    <button onclick="location.reload()" class="btn-outline btn-sm"><i class="fa-solid fa-rotate me-1"></i>Actualiser</button>
  </div>
  <?php if(!$orders): ?>
  <div class="empty"><i class="fa-regular fa-receipt"></i><h4>Aucune commande</h4></div>
  <?php else: ?>
  <div class="table-responsive">
  <table class="w-100" style="border-collapse:collapse">
    <thead><tr style="background:#F9FAFB">
      <?php foreach(['#','Client','Articles','Total','Type','Statut','Actions'] as $h): ?>
      <th style="padding:11px 16px;text-align:left;font-size:.74rem;font-weight:700;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--border)"><?=$h?></th>
      <?php endforeach; ?>
    </tr></thead>
    <tbody>
    <?php foreach($orders as $o): ?>
    <tr style="border-bottom:1px solid var(--border)">
      <td style="padding:11px 16px;font-size:.85rem;font-weight:700">#<?=$o['id']?></td>
      <td style="padding:11px 16px;font-size:.85rem">
        <div style="font-weight:600"><?=e($o['cname']??'Client')?></div>
        <div style="font-size:.76rem;color:var(--muted)"><?=e($o['cphone']??'')?></div>
      </td>
      <td style="padding:11px 16px;font-size:.82rem;color:var(--muted)"><?=ago($o['created_at'])?></td>
      <td style="padding:11px 16px;font-weight:700;color:var(--brand)"><?=money($o['total'])?></td>
      <td style="padding:11px 16px;font-size:.82rem"><span style="text-transform:capitalize"><?=$o['delivery_type']?></span></td>
      <td style="padding:11px 16px"><span class="sbadge s-<?=e($o['status'])?>"><?=$statusLabels[$o['status']]??$o['status']?></span></td>
      <td style="padding:11px 16px">
        <div class="d-flex gap-1 flex-wrap">
        <?php if($o['status']==='pending'): ?>
          <button class="btn-success btn-sm" onclick="orderAction(<?=$o['id']?>,'accept')">✓ Accepter</button>
          <button class="btn-danger btn-sm" onclick="orderAction(<?=$o['id']?>,'reject')">✗ Refuser</button>
        <?php elseif($o['status']==='accepted'): ?>
          <button class="btn-warn btn-sm" onclick="orderAction(<?=$o['id']?>,'prepare')">🔥 Préparer</button>
        <?php elseif($o['status']==='preparing'): ?>
          <button class="btn-info btn-sm" onclick="orderAction(<?=$o['id']?>,'ready')">✅ Prêt</button>
        <?php elseif($o['status']==='ready'&&$o['delivery_type']==='delivery'&&!$o['driver_id']): ?>
          <select onchange="assignDrv(<?=$o['id']?>,this.value)" class="finp" style="width:auto;padding:5px 10px;font-size:.78rem">
            <option value="">Assigner livreur</option>
            <?php foreach($drivers as $d): ?><option value="<?=$d['id']?>"><?=e($d['uname'])?> (<?=e($d['vehicle_type'])?>)</option><?php endforeach; ?>
          </select>
        <?php else: ?>
          <a href="?page=order&id=<?=$o['id']?>" class="btn-outline btn-sm">Voir</a>
        <?php endif; ?>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<?php elseif($tab==='menu'): ?>
<!-- Menu Tab -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-700 mb-0"><i class="fa-solid fa-utensils text-brand me-2"></i>Gestion du Menu</h5>
  <button class="btn-brand btn-sm" data-bs-toggle="modal" data-bs-target="#addProdModal"><i class="fa-solid fa-plus me-1"></i>Ajouter un produit</button>
</div>
<div class="tbl-wrap">
  <table>
    <thead><tr><?php foreach(['Image','Nom','Catégorie','Prix','Statut','Actions'] as $h): ?><th><?=$h?></th><?php endforeach; ?></tr></thead>
    <tbody>
    <?php if(!$products): ?>
    <tr><td colspan="6" class="text-center py-4" style="color:var(--muted)">Aucun produit. Ajoutez-en un !</td></tr>
    <?php else: foreach($products as $p): ?>
    <tr id="prod-row-<?=$p['id']?>">
      <td><img src="<?=e($p['image']?:pimg($p['id']))?>" alt="" style="width:48px;height:48px;border-radius:8px;object-fit:cover;background:#eee"></td>
      <td>
        <div style="font-weight:600"><?=e($p['name'])?></div>
        <?php if($p['name_ar']): ?><div style="font-size:.75rem;color:var(--muted);direction:rtl"><?=e($p['name_ar'])?></div><?php endif; ?>
      </td>
      <td><?=e($p['menu_category'])?></td>
      <td class="fw-700 text-brand">
        <?php if($p['discount_price']): ?><s style="color:var(--muted);font-weight:400;font-size:.82rem"><?=money($p['price'])?></s><br><?=money($p['discount_price'])?><?php else: ?><?=money($p['price'])?><?php endif; ?>
      </td>
      <td><span style="background:<?=$p['is_available']?'var(--success)':'var(--danger)'?>;color:#fff;padding:2px 9px;border-radius:10px;font-size:.72rem;font-weight:700"><?=$p['is_available']?'Dispo':'Indispo'?></span></td>
      <td>
        <button class="btn-info btn-sm" onclick="openEditProd(<?=htmlspecialchars(json_encode($p),ENT_QUOTES)?>)">Modifier</button>
        <button class="btn-danger btn-sm" onclick="deleteProd(<?=$p['id']?>,this)">Supprimer</button>
      </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProdModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered modal-lg">
<div class="modal-content" style="border-radius:var(--r);border:none">
  <div class="modal-header" style="border-bottom:1px solid var(--border)">
    <h5 class="fw-700">Ajouter un produit</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
  </div>
  <form action="?" method="POST">
    <input type="hidden" name="action" value="add_product">
    <input type="hidden" name="_csrf" value="<?=csrf_token()?>">
    <div class="modal-body">
      <div class="row g-3">
        <div class="col-md-6"><label class="flbl">Nom (FR) *</label><input name="name" class="finp" required placeholder="Pizza Margherita"></div>
        <div class="col-md-6"><label class="flbl">Nom (AR)</label><input name="name_ar" class="finp" placeholder="بيتزا مارغريتا" dir="rtl"></div>
        <div class="col-12"><label class="flbl">Description</label><textarea name="description" class="finp" rows="2" placeholder="Description du plat..."></textarea></div>
        <div class="col-md-4"><label class="flbl">Prix (DA) *</label><input name="price" type="number" step="10" class="finp" required placeholder="850"></div>
        <div class="col-md-4"><label class="flbl">Prix promo (DA)</label><input name="discount_price" type="number" step="10" class="finp" placeholder="700"></div>
        <div class="col-md-4"><label class="flbl">Catégorie</label><input name="menu_category" class="finp" placeholder="Principal" value="Principal"></div>
        <div class="col-12"><label class="flbl">Image URL</label><input name="image" type="url" class="finp" placeholder="https://..."></div>
        <div class="col-md-6"><label style="display:flex;align-items:center;gap:8px;cursor:pointer"><input type="checkbox" name="is_available" checked> Disponible</label></div>
        <div class="col-md-6"><label style="display:flex;align-items:center;gap:8px;cursor:pointer"><input type="checkbox" name="is_featured"> Produit vedette</label></div>
      </div>
    </div>
    <div class="modal-footer" style="border-top:1px solid var(--border)">
      <button type="button" class="btn-outline" data-bs-dismiss="modal">Annuler</button>
      <button type="submit" class="btn-brand"><i class="fa-solid fa-plus me-2"></i>Ajouter</button>
    </div>
  </form>
</div></div></div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProdModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered modal-lg">
<div class="modal-content" style="border-radius:var(--r);border:none">
  <div class="modal-header" style="border-bottom:1px solid var(--border)">
    <h5 class="fw-700">Modifier le produit</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
  </div>
  <form action="?" method="POST" id="editProdForm">
    <input type="hidden" name="action" value="edit_product">
    <input type="hidden" name="_csrf" value="<?=csrf_token()?>">
    <input type="hidden" name="pid" id="ep_pid">
    <div class="modal-body">
      <div class="row g-3">
        <div class="col-md-6"><label class="flbl">Nom (FR)</label><input name="name" id="ep_name" class="finp"></div>
        <div class="col-md-6"><label class="flbl">Nom (AR)</label><input name="name_ar" id="ep_name_ar" class="finp" dir="rtl"></div>
        <div class="col-12"><label class="flbl">Description</label><textarea name="description" id="ep_desc" class="finp" rows="2"></textarea></div>
        <div class="col-md-4"><label class="flbl">Prix (DA)</label><input name="price" id="ep_price" type="number" step="10" class="finp"></div>
        <div class="col-md-4"><label class="flbl">Prix promo</label><input name="discount_price" id="ep_dp" type="number" step="10" class="finp"></div>
        <div class="col-md-4"><label class="flbl">Catégorie</label><input name="menu_category" id="ep_cat" class="finp"></div>
        <div class="col-12"><label class="flbl">Image URL</label><input name="image" id="ep_img" type="url" class="finp"></div>
        <div class="col-md-6"><label style="display:flex;align-items:center;gap:8px;cursor:pointer"><input type="checkbox" name="is_available" id="ep_avail"> Disponible</label></div>
        <div class="col-md-6"><label style="display:flex;align-items:center;gap:8px;cursor:pointer"><input type="checkbox" name="is_featured" id="ep_feat"> Produit vedette</label></div>
      </div>
    </div>
    <div class="modal-footer" style="border-top:1px solid var(--border)">
      <button type="button" class="btn-outline" data-bs-dismiss="modal">Annuler</button>
      <button type="submit" class="btn-brand"><i class="fa-solid fa-save me-2"></i>Sauvegarder</button>
    </div>
  </form>
</div></div></div>

<script>
function openEditProd(p){
  document.getElementById('ep_pid').value=p.id;
  document.getElementById('ep_name').value=p.name||'';
  document.getElementById('ep_name_ar').value=p.name_ar||'';
  document.getElementById('ep_desc').value=p.description||'';
  document.getElementById('ep_price').value=p.price||'';
  document.getElementById('ep_dp').value=p.discount_price||'';
  document.getElementById('ep_cat').value=p.menu_category||'';
  document.getElementById('ep_img').value=p.image||'';
  document.getElementById('ep_avail').checked=p.is_available==1;
  document.getElementById('ep_feat').checked=p.is_featured==1;
  new bootstrap.Modal(document.getElementById('editProdModal')).show();
}
async function deleteProd(pid,btn){
  if(!confirm('Supprimer ce produit ?')) return;
  const r=await post({action:'delete_product',pid});
  if(r.ok){document.getElementById('prod-row-'+pid)?.remove();toast(r.msg,'s');}
  else toast(r.msg,'e');
}
async function assignDrv(oid,did){
  if(!did) return;
  const r=await post({action:'assign_driver',oid,did});
  if(r.ok){toast(r.msg,'s');setTimeout(()=>location.reload(),800);}
  else toast(r.msg,'e');
}
</script>

<?php elseif($tab==='analytics'): ?>
<!-- Analytics Tab -->
<div class="row g-4 mb-4">
  <div class="col-lg-8">
    <div class="card-df p-4">
      <h5 class="fw-700 mb-4"><i class="fa-solid fa-chart-line text-brand me-2"></i>Revenus (30 derniers jours)</h5>
      <canvas id="revChart" height="100"></canvas>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card-df p-4">
      <h5 class="fw-700 mb-4"><i class="fa-solid fa-trophy text-brand me-2"></i>Top Produits</h5>
      <?php
      $topProds=DB::all("SELECT p.name,SUM(oi.quantity) qty FROM order_items oi JOIN products p ON oi.product_id=p.id JOIN orders o ON oi.order_id=o.id WHERE o.restaurant_id=? AND o.status='delivered' GROUP BY p.id ORDER BY qty DESC LIMIT 8",[$rest['id']]);
      foreach($topProds as $i=>$tp): ?>
      <div class="d-flex align-items-center gap-3 mb-3">
        <span style="width:22px;height:22px;background:var(--brand);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;flex-shrink:0"><?=$i+1?></span>
        <div style="flex:1;font-size:.87rem;font-weight:600"><?=e($tp['name'])?></div>
        <span style="font-weight:700;color:var(--brand)"><?=$tp['qty']?> vendus</span>
      </div>
      <?php endforeach; if(!$topProds): ?><p style="color:var(--muted);font-size:.87rem">Pas encore de données.</p><?php endif; ?>
    </div>
  </div>
</div>
<?php
$chart=AdminModel::revenueChart();
$dates=json_encode(array_column($chart,'d'));
$revs=json_encode(array_column($chart,'rev'));
?>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  const ctx=document.getElementById('revChart');
  if(ctx) new Chart(ctx,{type:'line',data:{labels:<?=$dates?>,datasets:[{label:'Revenus (DA)',data:<?=$revs?>,borderColor:'#FF5722',backgroundColor:'rgba(255,87,34,.08)',tension:.4,fill:true,pointRadius:3}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{grid:{color:'#f0f0f0'}}}}});
});
</script>

<?php elseif($tab==='reviews'): ?>
<!-- Reviews Tab -->
<div class="card-df p-4">
  <h5 class="fw-700 mb-4"><i class="fa-solid fa-star text-brand me-2"></i>Avis Clients — Note: <?=number_format($rest['rating'],1)?>/5</h5>
  <?php if(!$reviews): ?><div class="empty"><i class="fa-regular fa-star"></i><h4>Aucun avis</h4></div>
  <?php else: foreach($reviews as $rv): ?>
  <div style="padding:16px 0;border-bottom:1px solid var(--border)">
    <div class="d-flex align-items-center gap-3 mb-2">
      <img src="<?=ava($rv['cname']??'C')?>" alt="" style="width:38px;height:38px;border-radius:50%">
      <div class="flex-grow-1">
        <div style="font-weight:700;font-size:.9rem"><?=e($rv['cname']??'Client')?></div>
        <div style="font-size:.76rem;color:var(--muted)"><?=date('d/m/Y',strtotime($rv['created_at']))?></div>
      </div>
      <div><?=stars($rv['rating'])?></div>
    </div>
    <?php if($rv['comment']): ?><p style="font-size:.87rem;margin:0;color:var(--dark)"><?=e($rv['comment'])?></p><?php endif; ?>
  </div>
  <?php endforeach; endif; ?>
</div>

<?php elseif($tab==='settings'): ?>
<!-- Settings Tab -->
<div class="card-df p-4" style="max-width:640px">
  <h5 class="fw-700 mb-4"><i class="fa-solid fa-gear text-brand me-2"></i>Paramètres du Restaurant</h5>
  <form action="?" method="POST">
    <input type="hidden" name="action" value="update_restaurant">
    <input type="hidden" name="_csrf" value="<?=csrf_token()?>">
    <div class="fg"><label class="flbl">Nom du restaurant</label><input name="name" class="finp" value="<?=e($rest['name'])?>"></div>
    <div class="fg"><label class="flbl">Description</label><textarea name="description" class="finp" rows="3"><?=e($rest['description']??'')?></textarea></div>
    <div class="fg"><label class="flbl">Adresse</label><input name="address" class="finp" value="<?=e($rest['address']??'')?>"></div>
    <div class="fg"><label class="flbl">Téléphone</label><input name="phone" type="tel" class="finp" value="<?=e($rest['phone']??'')?>"></div>
    <div class="row g-3">
      <div class="col-md-4"><label class="flbl">Frais de livraison (DA)</label><input name="delivery_fee" type="number" step="10" class="finp" value="<?=$rest['delivery_fee']?>"></div>
      <div class="col-md-4"><label class="flbl">Commande minimum (DA)</label><input name="min_order" type="number" step="10" class="finp" value="<?=$rest['min_order']?>"></div>
      <div class="col-md-4"><label class="flbl">Temps livraison (min)</label><input name="delivery_time" type="number" class="finp" value="<?=$rest['delivery_time']?>"></div>
    </div>
    <div class="mt-3"><button type="submit" class="btn-brand"><i class="fa-solid fa-save me-2"></i>Sauvegarder</button></div>
  </form>
</div>
<?php endif; ?>

</div><!-- /dash-main -->
</div><!-- /dash-layout -->
<?php footer_html();
}

/* ── Driver Dashboard ────────────────────────────────────────── */
function pageDriverDashboard(): void
{
    Auth::guard('driver');
    $driver=DB::row("SELECT d.*,u.name uname,u.email FROM drivers d LEFT JOIN users u ON d.user_id=u.id WHERE d.user_id=?",[Auth::id()]);
    if(!$driver){ flash('error','Profil livreur introuvable.'); redirect('?page=home'); }

    $myOrders=OModel::byDriver($driver['id']);
    $available=OModel::available(15);
    $statusLabels=['assigned'=>'Assignée','picked_up'=>'Ramassée'];

    header_html('Espace Livreur');
    ?>
<div class="dash-layout">

<!-- Sidebar -->
<div class="sidebar">
  <div class="sidebar-brand"><i class="fa-solid fa-fire me-2"></i><span class="dar">Dar</span>Food</div>
  <nav class="snav pt-2">
    <div class="snav-lbl">Livreur</div>
    <a href="?page=driver-dashboard" class="active"><i class="fa-solid fa-motorcycle"></i>Tableau de bord</a>
    <a href="?page=home"><i class="fa-solid fa-home"></i>Accueil</a>
    <a href="?action=logout&_csrf=<?=csrf_token()?>" style="color:var(--danger)!important"><i class="fa-solid fa-right-from-bracket" style="color:var(--danger)"></i>Déconnexion</a>
  </nav>
</div>

<!-- Main -->
<div class="dash-main">

<!-- Driver info -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
  <div class="d-flex align-items-center gap-3">
    <img src="<?=ava($driver['uname']??'L')?>" alt="" style="width:56px;height:56px;border-radius:50%">
    <div>
      <h1 style="font-size:1.35rem;font-weight:800;margin:0"><?=e($driver['uname']??'')?></h1>
      <div style="font-size:.83rem;color:var(--muted)"><?=e($driver['vehicle_type']??'')?> · <?=e($driver['vehicle_plate']??'')?></div>
    </div>
  </div>
  <!-- Online toggle -->
  <div class="d-flex align-items-center gap-3">
    <span style="font-size:.87rem;font-weight:600">Statut:</span>
    <div style="display:flex;gap:8px">
      <?php foreach(['online','offline'] as $st): ?>
      <button onclick="setStatus('<?=$st?>')" class="<?=$driver['status']===$st?'btn-brand':'btn-outline'?> btn-sm">
        <?=$st==='online'?'🟢 En ligne':'⚫ Hors ligne'?>
      </button>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
  <?php foreach([
    ['Livraisons totales','fa-motorcycle','#DBEAFE','#2563EB',$driver['total_deliveries'],''],
    ['Gains totaux','fa-wallet','#D1FAE5','#059669',money($driver['total_earnings']),''],
    ['Note','fa-star','#FEF3C7','#D97706',number_format($driver['rating'],1).'/5',''],
    ['En cours','fa-road','#EDE9FE','#7C3AED',count($myOrders),'commandes'],
  ] as [$lbl,$ic,$bg,$col,$val,$unit]): ?>
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:<?=$bg?>;color:<?=$col?>"><i class="fa-solid <?=$ic?>"></i></div>
      <div><div class="stat-val"><?=$val?></div><div class="stat-lbl"><?=$lbl?> <?=$unit?></div></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- My Active Orders -->
<?php if($myOrders): ?>
<div style="margin-bottom:28px">
  <h5 class="fw-700 mb-3"><i class="fa-solid fa-road text-brand me-2"></i>Mes Livraisons en Cours</h5>
  <?php foreach($myOrders as $o): ?>
  <div class="drv-order card-df p-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
      <div>
        <div style="font-weight:800">Commande #<?=$o['id']?></div>
        <div style="font-size:.83rem;color:var(--muted)"><?=ago($o['created_at'])?></div>
      </div>
      <span class="sbadge s-<?=e($o['status'])?>"><?=$statusLabels[$o['status']]??$o['status']?></span>
    </div>
    <div class="row g-2 mb-3" style="font-size:.87rem">
      <div class="col-sm-6"><i class="fa-solid fa-store text-brand me-2"></i><strong>Retrait:</strong> <?=e($o['raddr']??$o['rname']??'')?></div>
      <div class="col-sm-6"><i class="fa-solid fa-house text-brand me-2"></i><strong>Livrer à:</strong> <?=e($o['address']??'')?></div>
      <div class="col-sm-6"><i class="fa-solid fa-user text-brand me-2"></i><?=e($o['cname']??'')?></div>
      <div class="col-sm-6"><i class="fa-solid fa-money-bill text-brand me-2"></i><?=money($o['total'])?></div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <?php if($o['status']==='assigned'): ?>
      <button class="btn-warn" onclick="driverPickup(<?=$o['id']?>)"><i class="fa-solid fa-bag-shopping me-2"></i>Confirmer Ramassage</button>
      <?php elseif($o['status']==='picked_up'): ?>
      <button class="btn-success" onclick="driverDeliver(<?=$o['id']?>)"><i class="fa-solid fa-house-circle-check me-2"></i>Confirmer Livraison</button>
      <?php endif; ?>
      <a href="?page=order&id=<?=$o['id']?>" class="btn-outline btn-sm">Détails</a>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Available Orders -->
<div>
  <h5 class="fw-700 mb-3"><i class="fa-solid fa-bell text-brand me-2"></i>Commandes Disponibles</h5>
  <?php if(!$available): ?>
  <div class="empty card-df p-4"><i class="fa-solid fa-bell"></i><h4>Pas de commandes disponibles</h4><p>Revenez dans quelques instants.</p></div>
  <?php else: foreach($available as $o): ?>
  <div class="drv-order card-df p-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
      <div><div style="font-weight:800">Commande #<?=$o['id']?></div><div style="font-size:.83rem;color:var(--muted)"><?=ago($o['created_at'])?></div></div>
      <div style="font-weight:800;color:var(--success);font-size:1.05rem"><?=money($o['total'])?></div>
    </div>
    <div class="row g-2 mb-3" style="font-size:.87rem">
      <div class="col-sm-6"><i class="fa-solid fa-store text-brand me-2"></i><?=e($o['rname']??'')?></div>
      <div class="col-sm-6"><i class="fa-solid fa-map-marker-alt text-brand me-2"></i><?=e($o['raddr']??'')?></div>
    </div>
    <?php if($driver['status']==='online'): ?>
    <button class="btn-brand" onclick="driverAccept(<?=$o['id']?>)"><i class="fa-solid fa-check me-2"></i>Accepter la livraison</button>
    <?php else: ?>
    <div class="alert alert-w" style="margin:0"><i class="fa-solid fa-triangle-exclamation"></i>Passez en ligne pour accepter des livraisons.</div>
    <?php endif; ?>
  </div>
  <?php endforeach; endif; ?>
</div>

</div>
</div>
<script>
async function setStatus(st){
  const r=await post({action:'update_driver_status',status:st,_csrf:CSRF});
  location.reload();
}
</script>
<?php footer_html();
}

/* ── Admin Dashboard ─────────────────────────────────────────── */
function pageAdmin(): void
{
    Auth::guard('admin');
    $tab=clean($_GET['tab']??'dashboard');
    $stats=AdminModel::stats();
    $chart=AdminModel::revenueChart();
    $topR=AdminModel::topRestaurants();

    header_html('Administration');
    ?>
<div class="dash-layout">

<!-- Sidebar -->
<div class="sidebar">
  <div class="sidebar-brand"><i class="fa-solid fa-gauge me-2"></i>Admin</div>
  <nav class="snav pt-2">
    <div class="snav-lbl">Tableau de bord</div>
    <a href="?page=admin" class="<?=$tab==='dashboard'?'active':''?>"><i class="fa-solid fa-gauge"></i>Aperçu</a>
    <a href="?page=admin&tab=orders" class="<?=$tab==='orders'?'active':''?>"><i class="fa-solid fa-receipt"></i>Commandes <span style="background:var(--brand);color:#fff;border-radius:10px;padding:1px 7px;font-size:.72rem;margin-left:auto"><?=$stats['pending']?></span></a>
    <div class="snav-lbl">Gestion</div>
    <a href="?page=admin&tab=restaurants" class="<?=$tab==='restaurants'?'active':''?>"><i class="fa-solid fa-store"></i>Restaurants</a>
    <a href="?page=admin&tab=users" class="<?=$tab==='users'?'active':''?>"><i class="fa-solid fa-users"></i>Utilisateurs</a>
    <a href="?page=admin&tab=drivers" class="<?=$tab==='drivers'?'active':''?>"><i class="fa-solid fa-motorcycle"></i>Livreurs</a>
    <a href="?page=admin&tab=coupons" class="<?=$tab==='coupons'?'active':''?>"><i class="fa-solid fa-tag"></i>Coupons</a>
    <a href="?page=admin&tab=logs" class="<?=$tab==='logs'?'active':''?>"><i class="fa-solid fa-list"></i>Journaux</a>
    <div class="snav-lbl">App</div>
    <a href="?page=home"><i class="fa-solid fa-home"></i>Accueil</a>
    <a href="?action=logout&_csrf=<?=csrf_token()?>" style="color:var(--danger)!important"><i class="fa-solid fa-right-from-bracket" style="color:var(--danger)"></i>Déconnexion</a>
  </nav>
</div>

<!-- Main -->
<div class="dash-main">

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
  <h1 style="font-size:1.4rem;font-weight:800;margin:0"><i class="fa-solid fa-gauge text-brand me-2"></i>Administration DarFood</h1>
  <div style="font-size:.83rem;color:var(--muted)"><i class="fa-regular fa-clock me-1"></i><?=date('d/m/Y H:i')?></div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
<?php foreach([
  ['Clients','fa-users','#DBEAFE','#2563EB',$stats['customers'],''],
  ['Restaurants','fa-store','#D1FAE5','#059669',$stats['restaurants'],'actifs'],
  ['Commandes','fa-receipt','#EDE9FE','#7C3AED',$stats['orders'],'total'],
  ['Livreurs','fa-motorcycle','#FEF3C7','#D97706',$stats['drivers'],''],
  ['Rev. Aujourd\'hui','fa-sun','#FEE2E2','#DC2626',money($stats['rev_today']),''],
  ['Rev. Ce Mois','fa-chart-line','#CFFAFE','#0E7490',money($stats['rev_month']),''],
] as [$lbl,$ic,$bg,$col,$val,$unit]): ?>
<div class="col-sm-6 col-lg-4 col-xl-2">
  <div class="stat-card">
    <div class="stat-icon" style="background:<?=$bg?>;color:<?=$col?>"><i class="fa-solid <?=$ic?>"></i></div>
    <div><div class="stat-val" style="font-size:1.4rem"><?=$val?></div><div class="stat-lbl"><?=$lbl?> <?=$unit?></div></div>
  </div>
</div>
<?php endforeach; ?>
</div>

<?php if($tab==='dashboard'): ?>
<!-- Revenue Chart -->
<div class="row g-4 mb-4">
  <div class="col-lg-8">
    <div class="card-df p-4">
      <h5 class="fw-700 mb-4"><i class="fa-solid fa-chart-area text-brand me-2"></i>Revenus (30 derniers jours)</h5>
      <canvas id="revChart" height="90"></canvas>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card-df p-4">
      <h5 class="fw-700 mb-4"><i class="fa-solid fa-trophy text-brand me-2"></i>Top Restaurants</h5>
      <?php foreach($topR as $i=>$r): ?>
      <div class="d-flex align-items-center gap-3 mb-3">
        <span style="width:22px;height:22px;background:var(--brand);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;flex-shrink:0"><?=$i+1?></span>
        <div style="flex:1;font-size:.87rem;font-weight:600"><?=e($r['name'])?></div>
        <span style="font-size:.8rem;font-weight:700;color:var(--brand)"><?=money($r['rev'])?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php
$dates2=json_encode(array_column($chart,'d'));
$revs2=json_encode(array_column($chart,'rev'));
$cnts2=json_encode(array_column($chart,'cnt'));
?>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  const ctx=document.getElementById('revChart');
  if(ctx) new Chart(ctx,{
    type:'bar',
    data:{labels:<?=$dates2?>,datasets:[
      {label:'Revenus (DA)',data:<?=$revs2?>,backgroundColor:'rgba(255,87,34,.7)',borderRadius:4,yAxisID:'y'},
      {type:'line',label:'Commandes',data:<?=$cnts2?>,borderColor:'#3B82F6',tension:.4,pointRadius:3,yAxisID:'y1'}
    ]},
    options:{responsive:true,scales:{y:{grid:{color:'#f0f0f0'}},y1:{position:'right',grid:{display:false}}}}
  });
});
</script>

<?php elseif($tab==='orders'): ?>
<?php $allOrders=AdminModel::orders(60); $statusLabels2=['pending'=>'En attente','accepted'=>'Acceptée','preparing'=>'Préparation','ready'=>'Prête','assigned'=>'Assignée','picked_up'=>'En route','delivered'=>'Livrée','cancelled'=>'Annulée','rejected'=>'Refusée']; ?>
<div class="tbl-wrap">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border)"><h5 class="fw-700 mb-0">Toutes les Commandes</h5></div>
  <div class="table-responsive">
  <table>
    <thead><tr><th>#</th><th>Client</th><th>Restaurant</th><th>Total</th><th>Paiement</th><th>Statut</th><th>Date</th></tr></thead>
    <tbody>
    <?php foreach($allOrders as $o): ?>
    <tr onclick="location.href='?page=order&id=<?=$o['id']?>'" style="cursor:pointer">
      <td class="fw-700">#<?=$o['id']?></td>
      <td><?=e($o['cname']??'—')?></td>
      <td><?=e($o['rname']??'—')?></td>
      <td class="fw-700 text-brand"><?=money($o['total'])?></td>
      <td><?=ucfirst($o['payment_method'])?></td>
      <td><span class="sbadge s-<?=e($o['status'])?>"><?=$statusLabels2[$o['status']]??$o['status']?></span></td>
      <td style="color:var(--muted)"><?=ago($o['created_at'])?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<?php elseif($tab==='restaurants'): ?>
<?php $allRests=AdminModel::restaurants(); ?>
<div class="tbl-wrap">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border)"><h5 class="fw-700 mb-0">Restaurants</h5></div>
  <div class="table-responsive">
  <table>
    <thead><tr><th>Logo</th><th>Nom</th><th>Propriétaire</th><th>Catégorie</th><th>Note</th><th>Statut</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($allRests as $r): ?>
    <tr>
      <td><img src="<?=e($r['logo']?:ava($r['name']))?>" alt="" style="width:40px;height:40px;border-radius:8px;object-fit:cover;background:#eee"></td>
      <td class="fw-700"><?=e($r['name'])?></td>
      <td style="color:var(--muted)"><?=e($r['owner_name']??'—')?></td>
      <td><?=e($r['category'])?></td>
      <td><?=number_format($r['rating'],1)?> ⭐ (<?=$r['rating_count']?>)</td>
      <td><span class="sbadge s-<?=$r['status']==='active'?'delivered':'cancelled'?>"><?=ucfirst($r['status'])?></span></td>
      <td>
        <select onchange="adminRest(<?=$r['id']?>,this.value)" class="finp" style="width:auto;padding:4px 8px;font-size:.78rem">
          <?php foreach(['active','inactive','pending'] as $st): ?><option <?=$r['status']===$st?'selected':''?> value="<?=$st?>"><?=ucfirst($st)?></option><?php endforeach; ?>
        </select>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<script>
async function adminRest(rid,status){const r=await post({action:'admin_restaurant',rid,status});if(r.ok)toast('Statut mis à jour.','s');else toast(r.msg,'e');}
</script>

<?php elseif($tab==='users'): ?>
<?php $allUsers=AdminModel::users(); ?>
<div class="tbl-wrap">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border)"><h5 class="fw-700 mb-0">Utilisateurs</h5></div>
  <div class="table-responsive">
  <table>
    <thead><tr><th>Avatar</th><th>Nom</th><th>Email</th><th>Rôle</th><th>Statut</th><th>Inscription</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($allUsers as $u): ?>
    <tr>
      <td><img src="<?=ava($u['name'])?>" alt="" style="width:36px;height:36px;border-radius:50%"></td>
      <td class="fw-700"><?=e($u['name'])?></td>
      <td style="color:var(--muted);font-size:.83rem"><?=e($u['email'])?></td>
      <td><span style="background:var(--brand-l);color:var(--brand);padding:2px 8px;border-radius:10px;font-size:.75rem;font-weight:700"><?=ucfirst($u['role'])?></span></td>
      <td><span class="sbadge s-<?=$u['status']==='active'?'delivered':'cancelled'?>"><?=ucfirst($u['status'])?></span></td>
      <td style="color:var(--muted);font-size:.83rem"><?=date('d/m/Y',strtotime($u['created_at']))?></td>
      <td>
        <select onchange="adminUser(<?=$u['id']?>,this.value)" class="finp" style="width:auto;padding:4px 8px;font-size:.78rem">
          <?php foreach(['active','inactive','banned'] as $st): ?><option <?=$u['status']===$st?'selected':''?> value="<?=$st?>"><?=ucfirst($st)?></option><?php endforeach; ?>
        </select>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<script>
async function adminUser(uid,status){const r=await post({action:'admin_user_status',uid,status});if(r.ok)toast('Statut mis à jour.','s');else toast(r.msg,'e');}
</script>

<?php elseif($tab==='drivers'): ?>
<?php $allDrivers=AdminModel::drivers(); ?>
<div class="tbl-wrap">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border)"><h5 class="fw-700 mb-0">Livreurs</h5></div>
  <div class="table-responsive">
  <table>
    <thead><tr><th>Nom</th><th>Téléphone</th><th>Véhicule</th><th>Plaque</th><th>Statut</th><th>Livraisons</th><th>Gains</th><th>Note</th></tr></thead>
    <tbody>
    <?php foreach($allDrivers as $d): ?>
    <tr>
      <td class="fw-700"><?=e($d['name']??'')?></td>
      <td><?=e($d['phone']??'')?></td>
      <td><?=e($d['vehicle_type']??'')?></td>
      <td style="color:var(--muted)"><?=e($d['vehicle_plate']??'')?></td>
      <td>
        <span style="background:<?=$d['status']==='online'?'var(--success)':($d['status']==='busy'?'var(--warn)':'var(--border)')?>;color:<?=$d['status']==='online'?'#fff':($d['status']==='busy'?'var(--dark)':'var(--muted)')?>;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:700">
          <?=ucfirst($d['status'])?>
        </span>
      </td>
      <td class="fw-700"><?=$d['total_deliveries']?></td>
      <td class="fw-700 text-brand"><?=money($d['total_earnings'])?></td>
      <td><?=number_format($d['rating'],1)?> ⭐</td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<?php elseif($tab==='coupons'): ?>
<?php $coupons=DB::all("SELECT * FROM coupons ORDER BY created_at DESC"); ?>
<div class="tbl-wrap">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border)"><h5 class="fw-700 mb-0">Coupons</h5></div>
  <div class="table-responsive">
  <table>
    <thead><tr><th>Code</th><th>Type</th><th>Valeur</th><th>Min. Cmde</th><th>Utilisations</th><th>Statut</th><th>Expire</th></tr></thead>
    <tbody>
    <?php foreach($coupons as $c): ?>
    <tr>
      <td><span style="font-family:monospace;background:#F3F4F6;padding:3px 9px;border-radius:5px;font-weight:700"><?=e($c['code'])?></span></td>
      <td><?=$c['type']==='percent'?'Pourcentage':'Fixe'?></td>
      <td class="fw-700"><?=$c['type']==='percent'?$c['value'].'%':money($c['value'])?></td>
      <td><?=money($c['min_order'])?></td>
      <td><?=$c['used_count']?><?=$c['max_uses']>0?' / '.$c['max_uses']:''?></td>
      <td><span class="sbadge s-<?=$c['status']==='active'?'delivered':'cancelled'?>"><?=ucfirst($c['status'])?></span></td>
      <td style="color:var(--muted)"><?=$c['expires_at']?date('d/m/Y',strtotime($c['expires_at'])):'Jamais'?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<?php elseif($tab==='logs'): ?>
<?php $logs=AdminModel::logs(); ?>
<div class="tbl-wrap">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border)"><h5 class="fw-700 mb-0">Journaux d'activité</h5></div>
  <div class="table-responsive">
  <table>
    <thead><tr><th>#</th><th>Utilisateur</th><th>Action</th><th>Détails</th><th>IP</th><th>Date</th></tr></thead>
    <tbody>
    <?php foreach($logs as $l): ?>
    <tr>
      <td style="color:var(--muted)"><?=$l['id']?></td>
      <td><?=e($l['uname']??'Système')?></td>
      <td><code style="background:#F3F4F6;padding:2px 7px;border-radius:4px;font-size:.8rem"><?=e($l['action'])?></code></td>
      <td style="color:var(--muted);font-size:.82rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?=e($l['details']??'')?></td>
      <td style="color:var(--muted);font-size:.82rem"><?=e($l['ip']??'')?></td>
      <td style="color:var(--muted);font-size:.82rem"><?=ago($l['created_at'])?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>

</div><!-- /dash-main -->
</div><!-- /dash-layout -->
<?php footer_html();
}

/* ── 404 ─────────────────────────────────────────────────────── */
function page404(): void
{
    http_response_code(404);
    header_html('Page introuvable');
    ?>
<div style="min-height:60vh;display:flex;align-items:center;justify-content:center">
<div style="text-align:center;padding:40px">
  <div style="font-size:6rem;line-height:1;margin-bottom:16px">🍽</div>
  <h1 style="font-size:3rem;font-weight:800;color:var(--brand);margin-bottom:8px">404</h1>
  <h2 style="font-weight:700;margin-bottom:12px">Page introuvable</h2>
  <p style="color:var(--muted);margin-bottom:24px">La page que vous cherchez n'existe pas ou a été déplacée.</p>
  <a href="?page=home" class="btn-brand" style="border-radius:50px;padding:12px 32px"><i class="fa-solid fa-home me-2"></i>Retour à l'accueil</a>
</div>
</div>
<?php footer_html();
}

// ====================================================================
// SECTION 10 — ROUTER
// ====================================================================
function dispatch(): void
{
    // Handle POST/GET actions first
    $action = clean($_POST['action'] ?? $_GET['action'] ?? '');
    if($action && $action !== 'logout') {
        handleAction();
        return;
    }
    if($action === 'logout') {
        doLogout();
        return;
    }

    // Route pages
    $page = clean($_GET['page'] ?? 'home');

    match($page) {
        'home','restaurants'  => pageHome(),
        'restaurant'          => pageRestaurant(),
        'product-modal'       => pageProductModal(),
        'checkout'            => pageCheckout(),
        'order'               => pageOrder(),
        'orders'              => pageOrders(),
        'profile'             => pageProfile(),
        'login'               => pageLogin(),
        'register'            => pageRegister(),
        'restaurant-dashboard'=> pageRestaurantDashboard(),
        'driver-dashboard'    => pageDriverDashboard(),
        'admin'               => pageAdmin(),
        default               => page404(),
    };
}

// ====================================================================
// SECTION 11 — EXECUTE
// ====================================================================
dispatch();
