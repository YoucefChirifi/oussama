<?php
/**
 * Mayase - Talent Discovery Platform
 * Single file PHP application with Tailwind CDN & MySQL
 * Auto-creates database, tables, and sample data if missing.
 */

// ================== CONFIGURATION ==================
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'mayase_db';

session_start();

// ================== DATABASE HANDLER ==================
function getPDO(): PDO {
    global $db_host, $db_user, $db_pass, $db_name;
    static $pdo = null;
    if ($pdo) return $pdo;

    try {
        // Connect without database first to create it if needed
        $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db_name`");
        createTablesIfNeeded($pdo);
        insertSampleDataIfEmpty($pdo);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
    return $pdo;
}

function createTablesIfNeeded(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code_name VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('talent','project_owner') NOT NULL,
            location VARCHAR(100) DEFAULT '',
            bio TEXT DEFAULT '',
            category VARCHAR(100) DEFAULT '',
            skills TEXT DEFAULT '',
            portfolio JSON DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            owner_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            required_categories VARCHAR(255),
            budget_range VARCHAR(100),
            deadline DATE,
            status ENUM('open','closed') DEFAULT 'open',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS match_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            talent_id INT NOT NULL,
            from_user_id INT NOT NULL,
            status ENUM('pending','accepted','rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (talent_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_match (project_id, talent_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

function insertSampleDataIfEmpty(PDO $pdo): void {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() > 0) return;

    $password = password_hash('password123', PASSWORD_DEFAULT);

    // Sample talents
    $talents = [
        ['code_name' => 'NeonStar', 'email' => 'neon@example.com', 'role' => 'talent', 'location' => 'Algiers', 'category' => 'acting', 'skills' => 'drama,comedy,theatre', 'portfolio' => json_encode([['type' => 'image', 'url' => 'https://picsum.photos/400/300?random=1', 'title' => 'Stage Performance'], ['type' => 'video', 'url' => 'https://www.w3schools.com/html/mov_bbb.mp4', 'title' => 'Showreel']])],
        ['code_name' => 'PixelArtist', 'email' => 'pixel@example.com', 'role' => 'talent', 'location' => 'Oran', 'category' => 'graphic design', 'skills' => 'photoshop,illustrator,ui', 'portfolio' => json_encode([['type' => 'image', 'url' => 'https://picsum.photos/400/300?random=2', 'title' => 'Logo Design']])],
        ['code_name' => 'FilmCrafter', 'email' => 'film@example.com', 'role' => 'talent', 'location' => 'Constantine', 'category' => 'editing', 'skills' => 'premiere,davinci,color grading', 'portfolio' => json_encode([['type' => 'video', 'url' => 'https://www.w3schools.com/html/mov_bbb.mp4', 'title' => 'Movie Trailer']])],
        ['code_name' => 'ScriptMaster', 'email' => 'script@example.com', 'role' => 'talent', 'location' => 'Annaba', 'category' => 'writing', 'skills' => 'screenwriting,dialogue,storyboard', 'portfolio' => null],
    ];

    $stmt = $pdo->prepare("INSERT INTO users (code_name, email, password_hash, role, location, category, skills, portfolio) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($talents as $t) {
        $stmt->execute([$t['code_name'], $t['email'], $password, $t['role'], $t['location'], $t['category'], $t['skills'], $t['portfolio']]);
    }

    // Sample project owner
    $stmt->execute(['ProducerOne', 'producer@example.com', $password, 'project_owner', 'Algiers', 'film producer', 'management', null]);
    $ownerId = $pdo->lastInsertId();

    // Sample projects
    $projects = [
        ['owner_id' => $ownerId, 'title' => 'Short Film "Dreams of the City"', 'description' => 'Looking for lead actor, scriptwriter, and editor.', 'required_categories' => 'acting,writing,editing', 'budget_range' => '50000-100000 DZD', 'deadline' => '2025-12-31'],
        ['owner_id' => $ownerId, 'title' => 'Music Video Production', 'description' => 'Need a graphic designer for motion graphics.', 'required_categories' => 'graphic design', 'budget_range' => '30000-50000 DZD', 'deadline' => '2025-10-15'],
    ];
    $stmt = $pdo->prepare("INSERT INTO projects (owner_id, title, description, required_categories, budget_range, deadline) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($projects as $p) {
        $stmt->execute([$p['owner_id'], $p['title'], $p['description'], $p['required_categories'], $p['budget_range'], $p['deadline']]);
    }

    // Sample match request
    $stmt = $pdo->prepare("INSERT INTO match_requests (project_id, talent_id, from_user_id, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([1, 1, $ownerId]); // Producer invites NeonStar to project 1
}

// ================== HELPER FUNCTIONS ==================
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function redirect($url = 'index.php'): void {
    header("Location: $url");
    exit;
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// ================== ROUTING & ACTIONS ==================
$page = $_GET['page'] ?? 'home';
$action = $_POST['action'] ?? '';

// Logout
if ($page === 'logout') {
    session_destroy();
    redirect('index.php?page=login');
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getPDO();

    // Registration
    if ($action === 'register') {
        $code_name = trim($_POST['code_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';
        $location = trim($_POST['location'] ?? '');
        $category = $_POST['category'] ?? '';
        $skills = trim($_POST['skills'] ?? '');

        $errors = [];
        if (strlen($code_name) < 3) $errors[] = "Code name must be at least 3 characters.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email.";
        if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
        if (!in_array($role, ['talent', 'project_owner'])) $errors[] = "Invalid role.";
        if (empty($location)) $errors[] = "Location is required.";

        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO users (code_name, email, password_hash, role, location, category, skills) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$code_name, $email, $hash, $role, $location, $category, $skills]);
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['success'] = "Account created successfully!";
                redirect('index.php?page=home');
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) $errors[] = "Code name or email already taken.";
                else $errors[] = "Database error: " . $e->getMessage();
            }
        }
        $_SESSION['errors'] = $errors;
        redirect('index.php?page=register');
    }

    // Login
    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['success'] = "Welcome back!";
            redirect('index.php?page=home');
        } else {
            $_SESSION['errors'] = ["Invalid email or password."];
            redirect('index.php?page=login');
        }
    }

    // Update profile (talent only for portfolio)
    if ($action === 'update_profile') {
        if (!isLoggedIn()) redirect('index.php?page=login');
        $user = currentUser();
        $bio = trim($_POST['bio'] ?? '');
        $skills = trim($_POST['skills'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $category = $_POST['category'] ?? '';

        // Handle portfolio additions (simplified JSON update)
        $portfolio = $user['portfolio'] ? json_decode($user['portfolio'], true) : [];
        if (!empty($_POST['new_portfolio_url'])) {
            $newItem = [
                'type' => $_POST['new_portfolio_type'] ?? 'image',
                'url' => trim($_POST['new_portfolio_url']),
                'title' => trim($_POST['new_portfolio_title'] ?? ''),
            ];
            if (filter_var($newItem['url'], FILTER_VALIDATE_URL)) {
                $portfolio[] = $newItem;
            }
        }
        // Remove portfolio item
        if (isset($_POST['remove_portfolio_index']) && is_numeric($_POST['remove_portfolio_index'])) {
            $index = (int)$_POST['remove_portfolio_index'];
            if (isset($portfolio[$index])) {
                array_splice($portfolio, $index, 1);
            }
        }

        $portfolioJson = json_encode($portfolio);
        $stmt = $pdo->prepare("UPDATE users SET bio = ?, skills = ?, location = ?, category = ?, portfolio = ? WHERE id = ?");
        $stmt->execute([$bio, $skills, $location, $category, $portfolioJson, $user['id']]);
        $_SESSION['success'] = "Profile updated.";
        redirect('index.php?page=profile');
    }

    // Project creation
    if ($action === 'create_project') {
        if (!isLoggedIn() || currentUser()['role'] !== 'project_owner') redirect('index.php?page=login');
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $required_categories = trim($_POST['required_categories'] ?? '');
        $budget = trim($_POST['budget_range'] ?? '');
        $deadline = $_POST['deadline'] ?? '';

        if (empty($title) || empty($required_categories)) {
            $_SESSION['errors'] = ["Title and required categories are mandatory."];
            redirect('index.php?page=create_project');
        }

        $stmt = $pdo->prepare("INSERT INTO projects (owner_id, title, description, required_categories, budget_range, deadline) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $title, $description, $required_categories, $budget, $deadline ?: null]);
        $_SESSION['success'] = "Project created!";
        redirect('index.php?page=my_projects');
    }

    // Send match request (project owner invites talent)
    if ($action === 'send_match_request') {
        if (!isLoggedIn() || currentUser()['role'] !== 'project_owner') redirect('index.php?page=login');
        $talent_id = (int)$_POST['talent_id'];
        $project_id = (int)$_POST['project_id'];

        // Verify project belongs to owner
        $stmt = $pdo->prepare("SELECT id FROM projects WHERE id = ? AND owner_id = ?");
        $stmt->execute([$project_id, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            $_SESSION['errors'] = ["Invalid project."];
            redirect('index.php?page=talents');
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO match_requests (project_id, talent_id, from_user_id) VALUES (?, ?, ?)");
            $stmt->execute([$project_id, $talent_id, $_SESSION['user_id']]);
            $_SESSION['success'] = "Invitation sent!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) $_SESSION['errors'] = ["You already invited this talent to that project."];
            else $_SESSION['errors'] = ["Error sending invitation."];
        }
        redirect('index.php?page=talents');
    }

    // Handle match response (accept/reject)
    if ($action === 'respond_match') {
        if (!isLoggedIn()) redirect('index.php?page=login');
        $request_id = (int)$_POST['request_id'];
        $response = $_POST['response'] === 'accepted' ? 'accepted' : 'rejected';

        $stmt = $pdo->prepare("UPDATE match_requests SET status = ? WHERE id = ? AND talent_id = ?");
        $stmt->execute([$response, $request_id, $_SESSION['user_id']]);
        $_SESSION['success'] = "Response recorded.";
        redirect('index.php?page=inbox');
    }
}

// ================== PAGE RENDER FUNCTIONS ==================
function renderPage(string $page): void {
    $pdo = getPDO();
    $user = currentUser();

    // Flash messages
    $errors = $_SESSION['errors'] ?? [];
    $success = $_SESSION['success'] ?? '';
    unset($_SESSION['errors'], $_SESSION['success']);

    ?>
    <!DOCTYPE html>
    <html lang="en" class="h-full bg-gray-950 text-white">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Mayase - Talent Hub</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            body { background-color: #0a0a0a; }
            .card-hover:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
            .gradient-text { background: linear-gradient(90deg, #ec4899, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        </style>
    </head>
    <body class="flex flex-col min-h-screen">
        <!-- Navigation -->
        <nav class="bg-gray-900 border-b border-gray-800 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <a href="index.php?page=home" class="text-2xl font-bold gradient-text">MAYASE</a>
                    <div class="flex items-center space-x-4">
                        <?php if ($user): ?>
                            <a href="index.php?page=home" class="text-gray-300 hover:text-white">Home</a>
                            <?php if ($user['role'] === 'talent'): ?>
                                <a href="index.php?page=projects" class="text-gray-300 hover:text-white">Projects</a>
                            <?php else: ?>
                                <a href="index.php?page=talents" class="text-gray-300 hover:text-white">Talents</a>
                                <a href="index.php?page=my_projects" class="text-gray-300 hover:text-white">My Projects</a>
                            <?php endif; ?>
                            <a href="index.php?page=inbox" class="text-gray-300 hover:text-white">Inbox</a>
                            <a href="index.php?page=profile" class="text-gray-300 hover:text-white">Profile</a>
                            <span class="text-pink-500"><?= e($user['code_name']) ?></span>
                            <a href="index.php?page=logout" class="bg-pink-600 hover:bg-pink-700 px-3 py-1 rounded text-sm">Logout</a>
                        <?php else: ?>
                            <a href="index.php?page=login" class="text-gray-300 hover:text-white">Login</a>
                            <a href="index.php?page=register" class="bg-pink-600 hover:bg-pink-700 px-3 py-1 rounded text-sm">Register</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Flash Messages -->
        <?php if ($errors): ?>
            <div class="max-w-7xl mx-auto mt-4 px-4">
                <?php foreach ($errors as $err): ?>
                    <div class="bg-red-500/20 border border-red-500 text-red-300 p-3 rounded mb-2"><?= e($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="max-w-7xl mx-auto mt-4 px-4">
                <div class="bg-green-500/20 border border-green-500 text-green-300 p-3 rounded mb-2"><?= e($success) ?></div>
            </div>
        <?php endif; ?>

        <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
            <?php
            switch ($page) {
                case 'login': requireLoginPage(); break;
                case 'register': requireRegisterPage(); break;
                case 'home': requireHomePage($pdo); break;
                case 'talents': requireTalentsPage($pdo); break;
                case 'talent_detail': requireTalentDetailPage($pdo); break;
                case 'projects': requireProjectsPage($pdo); break;
                case 'project_detail': requireProjectDetailPage($pdo); break;
                case 'my_projects': requireMyProjectsPage($pdo); break;
                case 'create_project': requireCreateProjectPage(); break;
                case 'profile': requireProfilePage($user); break;
                case 'inbox': requireInboxPage($pdo, $user); break;
                default: requireHomePage($pdo);
            }
            ?>
        </main>
    </body>
    </html>
    <?php
}

// ================== PAGE IMPLEMENTATIONS ==================

function requireLoginPage(): void { ?>
    <div class="max-w-md mx-auto bg-gray-900 p-8 rounded-lg shadow-2xl mt-12">
        <h2 class="text-3xl font-bold mb-6 gradient-text">Welcome Back</h2>
        <form method="POST" action="index.php?page=login">
            <input type="hidden" name="action" value="login">
            <div class="mb-4">
                <label class="block text-gray-400 mb-1">Email</label>
                <input type="email" name="email" required class="w-full bg-gray-800 border border-gray-700 rounded p-2 text-white">
            </div>
            <div class="mb-6">
                <label class="block text-gray-400 mb-1">Password</label>
                <input type="password" name="password" required class="w-full bg-gray-800 border border-gray-700 rounded p-2 text-white">
            </div>
            <button type="submit" class="w-full bg-pink-600 hover:bg-pink-700 py-2 rounded font-bold">Login</button>
        </form>
        <p class="mt-4 text-gray-400 text-center">New here? <a href="index.php?page=register" class="text-pink-500">Create account</a></p>
    </div>
<?php }

function requireRegisterPage(): void { ?>
    <div class="max-w-lg mx-auto bg-gray-900 p-8 rounded-lg shadow-2xl mt-8">
        <h2 class="text-3xl font-bold mb-6 gradient-text">Join Mayase</h2>
        <form method="POST" action="index.php?page=register">
            <input type="hidden" name="action" value="register">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-gray-400 mb-1">Code Name (public)</label>
                    <input type="text" name="code_name" required class="w-full bg-gray-800 border border-gray-700 rounded p-2 text-white">
                </div>
                <div class="col-span-2">
                    <label class="block text-gray-400 mb-1">Email</label>
                    <input type="email" name="email" required class="w-full bg-gray-800 border border-gray-700 rounded p-2 text-white">
                </div>
                <div class="col-span-2">
                    <label class="block text-gray-400 mb-1">Password</label>
                    <input type="password" name="password" required class="w-full bg-gray-800 border border-gray-700 rounded p-2 text-white">
                </div>
                <div>
                    <label class="block text-gray-400 mb-1">Role</label>
                    <select name="role" required class="w-full bg-gray-800 border border-gray-700 rounded p-2 text-white">
                        <option value="talent">Talent (Artist)</option>
                        <option value="project_owner">Project Owner (Producer/Director)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-400 mb-1">Location</label>
                    <input type="text" name="location" required class="w-full bg-gray-800 border border-gray-700 rounded p-2 text-white">
                </div>
                <div class="col-span-2">
                    <label class="block text-gray-400 mb-1">Category (e.g., acting, editing)</label>
                    <input type="text" name="category" class="w-full bg-gray-800 border border-gray-700 rounded p-2 text-white">
                </div>
                <div class="col-span-2">
                    <label class="block text-gray-400 mb-1">Skills (comma separated)</label>
                    <input type="text" name="skills" class="w-full bg-gray-800 border border-gray-700 rounded p-2 text-white">
                </div>
            </div>
            <button type="submit" class="w-full bg-pink-600 hover:bg-pink-700 py-2 rounded font-bold mt-6">Create Account</button>
        </form>
    </div>
<?php }

function requireHomePage(PDO $pdo): void {
    // Fetch featured talents (random 4)
    $talents = $pdo->query("SELECT * FROM users WHERE role = 'talent' ORDER BY RAND() LIMIT 4")->fetchAll();
    $projects = $pdo->query("SELECT p.*, u.code_name as owner_name FROM projects p JOIN users u ON p.owner_id = u.id WHERE p.status = 'open' ORDER BY p.created_at DESC LIMIT 4")->fetchAll();
    ?>
    <!-- Hero -->
    <div class="text-center mb-12">
        <h1 class="text-5xl font-extrabold gradient-text mb-4">Discover Creative Talent</h1>
        <p class="text-gray-400 max-w-2xl mx-auto text-lg">Mayase connects Algeria's finest actors, writers, designers, and filmmakers with the projects that need them.</p>
        <?php if (!isLoggedIn()): ?>
            <a href="index.php?page=register" class="mt-6 inline-block bg-pink-600 hover:bg-pink-700 px-8 py-3 rounded-full text-lg font-semibold">Get Started</a>
        <?php endif; ?>
    </div>

    <!-- Featured Talents -->
    <section class="mb-12">
        <h2 class="text-2xl font-bold mb-6">✨ Featured Talents</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($talents as $t): ?>
                <a href="index.php?page=talent_detail&id=<?= $t['id'] ?>" class="bg-gray-900 rounded-xl overflow-hidden card-hover transition-all duration-300">
                    <?php $portfolio = json_decode($t['portfolio'] ?? '[]', true); ?>
                    <?php if ($portfolio && isset($portfolio[0]['url'])): ?>
                        <img src="<?= e($portfolio[0]['url']) ?>" class="w-full h-40 object-cover">
                    <?php else: ?>
                        <div class="w-full h-40 bg-gradient-to-br from-pink-900 to-purple-900 flex items-center justify-center text-3xl">🎭</div>
                    <?php endif; ?>
                    <div class="p-4">
                        <h3 class="font-bold text-lg"><?= e($t['code_name']) ?></h3>
                        <p class="text-gray-400 text-sm"><?= e($t['category']) ?> · <?= e($t['location']) ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Open Projects -->
    <section>
        <h2 class="text-2xl font-bold mb-6">🚀 Open Projects</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($projects as $p): ?>
                <a href="index.php?page=project_detail&id=<?= $p['id'] ?>" class="bg-gray-900 p-6 rounded-xl card-hover transition-all duration-300">
                    <h3 class="font-bold text-xl"><?= e($p['title']) ?></h3>
                    <p class="text-gray-400 mt-1">by <?= e($p['owner_name']) ?></p>
                    <p class="mt-2 text-sm text-gray-300"><?= e(substr($p['description'], 0, 100)) ?>...</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <?php foreach (explode(',', $p['required_categories']) as $cat): ?>
                            <span class="px-2 py-1 bg-pink-900/50 text-pink-300 text-xs rounded"><?= e(trim($cat)) ?></span>
                        <?php endforeach; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php
}

function requireTalentsPage(PDO $pdo): void {
    $search = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? '';
    $where = "WHERE role = 'talent'";
    $params = [];
    if ($search) {
        $where .= " AND (code_name LIKE ? OR skills LIKE ? OR category LIKE ?)";
        $s = "%$search%";
        $params = [$s, $s, $s];
    }
    if ($category) {
        $where .= " AND category = ?";
        $params[] = $category;
    }
    $stmt = $pdo->prepare("SELECT * FROM users $where ORDER BY created_at DESC");
    $stmt->execute($params);
    $talents = $stmt->fetchAll();
    ?>
    <div class="mb-8 flex flex-wrap gap-4 items-end">
        <h2 class="text-2xl font-bold">🎨 Talents</h2>
        <form method="GET" action="index.php" class="flex gap-2 flex-wrap">
            <input type="hidden" name="page" value="talents">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search talents..." class="bg-gray-800 border border-gray-700 rounded p-2 text-white">
            <select name="category" class="bg-gray-800 border border-gray-700 rounded p-2 text-white">
                <option value="">All categories</option>
                <?php
                $cats = $pdo->query("SELECT DISTINCT category FROM users WHERE role='talent' AND category != ''")->fetchAll();
                foreach ($cats as $c): ?>
                    <option <?= $category === $c['category'] ? 'selected' : '' ?>><?= e($c['category']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded">Filter</button>
        </form>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($talents as $t): ?>
            <div class="bg-gray-900 rounded-xl overflow-hidden card-hover transition-all duration-300">
                <?php $portfolio = json_decode($t['portfolio'] ?? '[]', true); ?>
                <?php if ($portfolio && isset($portfolio[0]['url'])): ?>
                    <img src="<?= e($portfolio[0]['url']) ?>" class="w-full h-44 object-cover">
                <?php else: ?>
                    <div class="w-full h-44 bg-gradient-to-br from-pink-900 to-purple-900 flex items-center justify-center text-4xl">🎬</div>
                <?php endif; ?>
                <div class="p-4">
                    <h3 class="font-bold text-lg"><?= e($t['code_name']) ?></h3>
                    <p class="text-gray-400 text-sm"><?= e($t['category']) ?> · <?= e($t['location']) ?></p>
                    <p class="text-gray-500 text-sm mt-1"><?= e($t['skills']) ?></p>
                    <a href="index.php?page=talent_detail&id=<?= $t['id'] ?>" class="mt-3 inline-block text-pink-400 hover:underline">View Profile →</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php
}

function requireTalentDetailPage(PDO $pdo): void {
    $id = (int)($_GET['id'] ?? 0);
    $talent = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'talent'");
    $talent->execute([$id]);
    $talent = $talent->fetch();
    if (!$talent) { echo "<p class='text-red-400'>Talent not found.</p>"; return; }
    $portfolio = json_decode($talent['portfolio'] ?? '[]', true);
    $user = currentUser();
    $projects = [];
    if ($user && $user['role'] === 'project_owner') {
        $projects = $pdo->prepare("SELECT id, title FROM projects WHERE owner_id = ? AND status = 'open'");
        $projects->execute([$user['id']]);
        $projects = $projects->fetchAll();
    }
    ?>
    <div class="max-w-4xl mx-auto">
        <div class="bg-gray-900 rounded-xl p-6 flex flex-col md:flex-row gap-8">
            <div class="md:w-1/3">
                <?php if ($portfolio && isset($portfolio[0]['url'])): ?>
                    <img src="<?= e($portfolio[0]['url']) ?>" class="w-full rounded-lg">
                <?php else: ?>
                    <div class="w-full h-48 bg-gradient-to-br from-pink-900 to-purple-900 rounded-lg flex items-center justify-center text-5xl">🎭</div>
                <?php endif; ?>
            </div>
            <div class="md:w-2/3">
                <h2 class="text-3xl font-bold"><?= e($talent['code_name']) ?></h2>
                <p class="text-gray-400"><?= e($talent['category']) ?> · <?= e($talent['location']) ?></p>
                <p class="mt-3 text-gray-300"><?= nl2br(e($talent['bio'] ?: 'No bio yet.')) ?></p>
                <p class="mt-2"><span class="text-gray-500">Skills:</span> <?= e($talent['skills']) ?></p>

                <?php if ($user && $user['role'] === 'project_owner' && $user['id'] !== $talent['id']): ?>
                    <form method="POST" action="index.php?page=talents" class="mt-6 flex gap-2 flex-wrap items-end">
                        <input type="hidden" name="action" value="send_match_request">
                        <input type="hidden" name="talent_id" value="<?= $talent['id'] ?>">
                        <select name="project_id" required class="bg-gray-800 border border-gray-700 rounded p-2 text-white">
                            <option value="">Select project...</option>
                            <?php foreach ($projects as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= e($p['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="bg-pink-600 hover:bg-pink-700 px-4 py-2 rounded">Invite to Project</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <!-- Portfolio Grid -->
        <?php if ($portfolio): ?>
            <h3 class="text-xl font-bold mt-8 mb-4">Portfolio</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <?php foreach ($portfolio as $item): ?>
                    <div class="bg-gray-900 rounded overflow-hidden">
                        <?php if ($item['type'] === 'video'): ?>
                            <video controls class="w-full h-40 object-cover"><source src="<?= e($item['url']) ?>"></video>
                        <?php else: ?>
                            <img src="<?= e($item['url']) ?>" class="w-full h-40 object-cover">
                        <?php endif; ?>
                        <div class="p-2 text-sm"><?= e($item['title'] ?? '') ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php
}

function requireProjectsPage(PDO $pdo): void {
    $projects = $pdo->query("SELECT p.*, u.code_name as owner_name FROM projects p JOIN users u ON p.owner_id = u.id WHERE p.status = 'open' ORDER BY p.created_at DESC")->fetchAll();
    ?>
    <h2 class="text-2xl font-bold mb-6">🎯 Available Projects</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php foreach ($projects as $p): ?>
            <a href="index.php?page=project_detail&id=<?= $p['id'] ?>" class="bg-gray-900 p-6 rounded-xl card-hover transition-all duration-300">
                <h3 class="font-bold text-xl"><?= e($p['title']) ?></h3>
                <p class="text-gray-400">by <?= e($p['owner_name']) ?></p>
                <p class="text-gray-300 mt-2"><?= e(substr($p['description'], 0, 120)) ?>...</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <?php foreach (explode(',', $p['required_categories']) as $cat): ?>
                        <span class="px-2 py-1 bg-pink-900/50 text-pink-300 text-xs rounded"><?= e(trim($cat)) ?></span>
                    <?php endforeach; ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php
}

function requireProjectDetailPage(PDO $pdo): void {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT p.*, u.code_name as owner_name FROM projects p JOIN users u ON p.owner_id = u.id WHERE p.id = ?");
    $stmt->execute([$id]);
    $project = $stmt->fetch();
    if (!$project) { echo "<p class='text-red-400'>Project not found.</p>"; return; }
    ?>
    <div class="max-w-3xl mx-auto bg-gray-900 p-8 rounded-xl">
        <h2 class="text-3xl font-bold"><?= e($project['title']) ?></h2>
        <p class="text-gray-400">by <?= e($project['owner_name']) ?> · Status: <?= e($project['status']) ?></p>
        <p class="mt-4 text-gray-300"><?= nl2br(e($project['description'])) ?></p>
        <div class="mt-4 flex flex-wrap gap-4 text-sm">
            <span class="bg-gray-800 px-3 py-1 rounded">Budget: <?= e($project['budget_range']) ?></span>
            <span class="bg-gray-800 px-3 py-1 rounded">Deadline: <?= e($project['deadline']) ?></span>
            <span class="bg-gray-800 px-3 py-1 rounded">Needs: <?= e($project['required_categories']) ?></span>
        </div>
    </div>
<?php
}

function requireMyProjectsPage(PDO $pdo): void {
    $user = currentUser();
    if (!$user || $user['role'] !== 'project_owner') { echo "<p class='text-red-400'>Access denied.</p>"; return; }
    $projects = $pdo->prepare("SELECT * FROM projects WHERE owner_id = ? ORDER BY created_at DESC");
    $projects->execute([$user['id']]);
    $projects = $projects->fetchAll();
    ?>
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">📁 My Projects</h2>
        <a href="index.php?page=create_project" class="bg-pink-600 hover:bg-pink-700 px-4 py-2 rounded">+ New Project</a>
    </div>
    <div class="space-y-4">
        <?php foreach ($projects as $p): ?>
            <div class="bg-gray-900 p-6 rounded-xl flex justify-between items-center">
                <div>
                    <h3 class="font-bold text-lg"><?= e($p['title']) ?></h3>
                    <p class="text-gray-400 text-sm">Status: <?= e($p['status']) ?> · Deadline: <?= e($p['deadline']) ?></p>
                </div>
                <a href="index.php?page=project_detail&id=<?= $p['id'] ?>" class="text-pink-400 hover:underline">View</a>
            </div>
        <?php endforeach; ?>
    </div>
<?php
}

function requireCreateProjectPage(): void {
    if (!isLoggedIn() || currentUser()['role'] !== 'project_owner') redirect('index.php?page=login');
    ?>
    <div class="max-w-lg mx-auto bg-gray-900 p-8 rounded-xl">
        <h2 class="text-2xl font-bold mb-6 gradient-text">New Project</h2>
        <form method="POST" action="index.php?page=create_project">
            <input type="hidden" name="action" value="create_project">
            <div class="space-y-4">
                <div>
                    <label class="block text-gray-400 mb-1">Title</label>
                    <input type="text" name="title" required class="w-full bg-gray-800 border border-gray-700 rounded p-2 text-white">
                </div>
                <div>
                    <label class="block text-gray-400 mb-1">Description</label>
                    <textarea name="description" rows="4" class="w-full bg-gray-800 border border-gray-700 rounded p-2 text-white"></textarea>
                </div>
                <div>
                    <label class="block text-gray-400 mb-1">Required Categories (comma separated)</label>
                    <input type="text" name="required_categories" required class="w-full bg-gray-800 border border-gray-700 rounded p-2 text-white">
                </div>
                <div>
                    <label class="block text-gray-400 mb-1">Budget Range</label>
                    <input type="text" name="budget_range" class="w-full bg-gray-800 border border-gray-700 rounded p-2 text-white">
                </div>
                <div>
                    <label class="block text-gray-400 mb-1">Deadline</label>
                    <input type="date" name="deadline" class="w-full bg-gray-800 border border-gray-700 rounded p-2 text-white">
                </div>
            </div>
            <button type="submit" class="w-full bg-pink-600 hover:bg-pink-700 py-2 rounded font-bold mt-6">Create Project</button>
        </form>
    </div>
<?php
}

function requireProfilePage(?array $user): void {
    if (!$user) redirect('index.php?page=login');
    $portfolio = json_decode($user['portfolio'] ?? '[]', true);
    ?>
    <div class="max-w-2xl mx-auto bg-gray-900 p-8 rounded-xl">
        <h2 class="text-2xl font-bold mb-6">👤 Profile: <?= e($user['code_name']) ?></h2>
        <form method="POST" action="index.php?page=profile">
            <input type="hidden" name="action" value="update_profile">
            <div class="space-y-4">
                <div>
                    <label class="block text-gray-400">Bio</label>
                    <textarea name="bio" rows="3" class="w-full bg-gray-800 border border-gray-700 rounded p-2 text-white"><?= e($user['bio']) ?></textarea>
                </div>
                <div>
                    <label class="block text-gray-400">Location</label>
                    <input type="text" name="location" value="<?= e($user['location']) ?>" class="w-full bg-gray-800 border border-gray-700 rounded p-2 text-white">
                </div>
                <div>
                    <label class="block text-gray-400">Category</label>
                    <input type="text" name="category" value="<?= e($user['category']) ?>" class="w-full bg-gray-800 border border-gray-700 rounded p-2 text-white">
                </div>
                <div>
                    <label class="block text-gray-400">Skills</label>
                    <input type="text" name="skills" value="<?= e($user['skills']) ?>" class="w-full bg-gray-800 border border-gray-700 rounded p-2 text-white">
                </div>
            </div>
            <button type="submit" class="mt-6 bg-pink-600 hover:bg-pink-700 px-6 py-2 rounded">Update Info</button>
        </form>

        <!-- Portfolio management -->
        <div class="mt-10">
            <h3 class="text-xl font-bold mb-4">Portfolio Items</h3>
            <?php if ($portfolio): ?>
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <?php foreach ($portfolio as $index => $item): ?>
                        <div class="bg-gray-800 rounded p-3 relative group">
                            <?php if ($item['type'] === 'video'): ?>
                                <video controls class="w-full h-32 object-cover"><source src="<?= e($item['url']) ?>"></video>
                            <?php else: ?>
                                <img src="<?= e($item['url']) ?>" class="w-full h-32 object-cover rounded">
                            <?php endif; ?>
                            <p class="text-sm mt-1"><?= e($item['title'] ?? '') ?></p>
                            <form method="POST" class="absolute top-2 right-2">
                                <input type="hidden" name="action" value="update_profile">
                                <input type="hidden" name="remove_portfolio_index" value="<?= $index ?>">
                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-xs px-2 py-1 rounded">✕</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="bg-gray-800 p-4 rounded">
                <input type="hidden" name="action" value="update_profile">
                <div class="grid grid-cols-3 gap-3">
                    <select name="new_portfolio_type" class="bg-gray-700 border border-gray-600 rounded p-2 text-white">
                        <option value="image">Image</option>
                        <option value="video">Video</option>
                    </select>
                    <input type="text" name="new_portfolio_title" placeholder="Title" class="bg-gray-700 border border-gray-600 rounded p-2 text-white">
                    <input type="url" name="new_portfolio_url" placeholder="URL" required class="bg-gray-700 border border-gray-600 rounded p-2 text-white">
                </div>
                <button type="submit" class="mt-3 bg-pink-600 hover:bg-pink-700 px-4 py-1 rounded text-sm">Add Item</button>
            </form>
        </div>
    </div>
<?php
}

function requireInboxPage(PDO $pdo, ?array $user): void {
    if (!$user) redirect('index.php?page=login');
    if ($user['role'] === 'talent') {
        $requests = $pdo->prepare("SELECT mr.*, p.title as project_title, u.code_name as owner_name FROM match_requests mr JOIN projects p ON mr.project_id = p.id JOIN users u ON mr.from_user_id = u.id WHERE mr.talent_id = ? ORDER BY mr.created_at DESC");
        $requests->execute([$user['id']]);
        $requests = $requests->fetchAll();
    } else {
        $requests = $pdo->prepare("SELECT mr.*, p.title as project_title, u.code_name as talent_name FROM match_requests mr JOIN projects p ON mr.project_id = p.id JOIN users u ON mr.talent_id = u.id WHERE mr.from_user_id = ? ORDER BY mr.created_at DESC");
        $requests->execute([$user['id']]);
        $requests = $requests->fetchAll();
    }
    ?>
    <h2 class="text-2xl font-bold mb-6">📬 Inbox</h2>
    <?php if (empty($requests)): ?>
        <p class="text-gray-400">No match requests yet.</p>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($requests as $r): ?>
                <div class="bg-gray-900 p-5 rounded-xl flex justify-between items-center">
                    <div>
                        <p class="font-semibold">Project: <?= e($r['project_title']) ?></p>
                        <?php if ($user['role'] === 'talent'): ?>
                            <p class="text-gray-400 text-sm">From: <?= e($r['owner_name']) ?></p>
                        <?php else: ?>
                            <p class="text-gray-400 text-sm">Talent: <?= e($r['talent_name']) ?></p>
                        <?php endif; ?>
                        <span class="text-xs px-2 py-1 rounded <?= $r['status'] === 'pending' ? 'bg-yellow-800 text-yellow-300' : ($r['status'] === 'accepted' ? 'bg-green-800 text-green-300' : 'bg-red-800 text-red-300') ?>"><?= e($r['status']) ?></span>
                    </div>
                    <?php if ($user['role'] === 'talent' && $r['status'] === 'pending'): ?>
                        <div class="flex gap-2">
                            <form method="POST" action="index.php?page=inbox">
                                <input type="hidden" name="action" value="respond_match">
                                <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                <button name="response" value="accepted" class="bg-green-600 hover:bg-green-700 px-3 py-1 rounded text-sm">Accept</button>
                                <button name="response" value="rejected" class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded text-sm">Reject</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif;
}

// ================== MAIN EXECUTION ==================
try {
    $pdo = getPDO(); // ensures DB and tables exist, sample data filled
    renderPage($page);
} catch (Exception $e) {
    echo "<div style='color:red; padding:2rem;'>Fatal error: " . e($e->getMessage()) . "</div>";
}