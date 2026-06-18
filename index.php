
<?php
/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║   MAYASE — Digital Talent Discovery Platform                 ║
 * ║   Single-File MVP v1.0 | PHP 8+ | MySQL | TailwindCSS        ║
 * ╠══════════════════════════════════════════════════════════════╣
 * ║  SETUP: Place in xampp/htdocs/mayase/index.php               ║
 * ║         MySQL must be running                                 ║
 * ╠══════════════════════════════════════════════════════════════╣
 * ║  DEFAULT ACCOUNTS (auto-created on first visit):             ║
 * ║   Admin  → admin@mayase.dz        / admin123                 ║
 * ║   Talent → amira.bensalem@mail.dz / talent123                ║
 * ║   Client → malik.bennouar@mail.dz / client123                ║
 * ╚══════════════════════════════════════════════════════════════╝
 */

// ══════════════════════════════════════════════════════
// 0. BOOTSTRAP
// ══════════════════════════════════════════════════════
session_start();
error_reporting(0);

// ← EDIT WITH YOUR INFINITYFREE CREDENTIALS
define('APP_NAME', 'Mayase');
define('DB_HOST',  'sql200.infinityfree.com');
define('DB_USER',  'if0_42216937');
define('DB_PASS',  'u2BfgaPKUU2DX');
define('DB_NAME',  'if0_42216937_mayase');

foreach (['uploads', 'uploads/photos', 'uploads/portfolio'] as $d) {
    $p = __DIR__ . "/$d";
    if (!is_dir($p)) @mkdir($p, 0755, true);
}

// ══════════════════════════════════════════════════════
// 1. DATABASE LAYER
// ══════════════════════════════════════════════════════
function db(): PDO {
    static $c;
    if ($c) return $c;
    try {
        $c = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        if (!$c->query("SHOW TABLES LIKE 'users'")->rowCount()) {
            db_install($c);
        }
    } catch (PDOException $ex) {
        die('<body style="font:15px/1.8 system-ui;background:#0a0a0a;color:#ec4899;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:2rem;box-sizing:border-box"><div><h1 style="font-size:2rem;margin:0 0 .5rem">⚠ MySQL Connection Error</h1><p style="color:#999;margin:0 0 1rem">Check DB_HOST, DB_USER, DB_PASS and DB_NAME at top of file. On InfinityFree, DB_HOST is never "localhost".</p><pre style="background:#111;color:#f87171;padding:1rem;border-radius:8px;font-size:.8rem;overflow:auto">'.htmlspecialchars($ex->getMessage()).'</pre></div>');
    }
    return $c;
}
function q(string $sql, array $p=[]): PDOStatement { $s=db()->prepare($sql); $s->execute($p); return $s; }
function row(string $sql, array $p=[]): ?array     { $r=q($sql,$p)->fetch(); return $r?:null; }
function rows(string $sql, array $p=[]): array     { return q($sql,$p)->fetchAll(); }
// ══════════════════════════════════════════════════════
// 2. SCHEMA
// ══════════════════════════════════════════════════════
function db_install(PDO $c): void {
    $stmts = array_filter(array_map('trim', explode(';', db_schema())));
    foreach ($stmts as $s) $c->exec($s);
    db_seed($c);
}

function db_schema(): string { return "
CREATE TABLE categories (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    pfx  CHAR(3)      NOT NULL,
    icon VARCHAR(20)  DEFAULT ''
);
CREATE TABLE users (
    id         INT          AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(255) UNIQUE NOT NULL,
    pwhash     VARCHAR(255) NOT NULL,
    role       ENUM('admin','talent','client') DEFAULT 'talent',
    real_name  VARCHAR(255) NOT NULL,
    phone      VARCHAR(30)  DEFAULT '',
    status     ENUM('active','suspended','pending') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE talents (
    id           INT          AUTO_INCREMENT PRIMARY KEY,
    user_id      INT          NOT NULL,
    code         VARCHAR(20)  UNIQUE NOT NULL,
    nickname     VARCHAR(120) NOT NULL,
    cat_id       INT          NOT NULL,
    province     VARCHAR(100) NOT NULL,
    bio          TEXT         DEFAULT '',
    skills       TEXT         DEFAULT '',
    experience   TEXT         DEFAULT '',
    availability ENUM('available','busy','unavailable') DEFAULT 'available',
    photo        VARCHAR(500) DEFAULT '',
    approved     TINYINT(1)   DEFAULT 1,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (cat_id)  REFERENCES categories(id)
);
CREATE TABLE clients (
    id           INT          AUTO_INCREMENT PRIMARY KEY,
    user_id      INT          NOT NULL,
    display_name VARCHAR(150) NOT NULL,
    ctype        ENUM('Director','Producer','Agency','Content Creator','Other') DEFAULT 'Other',
    province     VARCHAR(100) DEFAULT '',
    bio          TEXT         DEFAULT '',
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE projects (
    id          INT          AUTO_INCREMENT PRIMARY KEY,
    client_id   INT          NOT NULL,
    title       VARCHAR(255) NOT NULL,
    description TEXT         NOT NULL,
    cat_id      INT,
    province    VARCHAR(100) DEFAULT '',
    budget      VARCHAR(100) DEFAULT '',
    deadline    DATE,
    status      ENUM('open','closed','draft') DEFAULT 'open',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (cat_id) REFERENCES categories(id)
);
CREATE TABLE applications (
    id         INT  AUTO_INCREMENT PRIMARY KEY,
    project_id INT  NOT NULL,
    talent_id  INT  NOT NULL,
    message    TEXT DEFAULT '',
    status     ENUM('pending','accepted','rejected') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_app (project_id, talent_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (talent_id)  REFERENCES talents(id)  ON DELETE CASCADE
);
CREATE TABLE requests (
    id         INT  AUTO_INCREMENT PRIMARY KEY,
    client_id  INT  NOT NULL,
    talent_id  INT  NOT NULL,
    message    TEXT DEFAULT '',
    status     ENUM('pending','in_review','responded','closed') DEFAULT 'pending',
    admin_note TEXT DEFAULT '',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (talent_id) REFERENCES talents(id) ON DELETE CASCADE
);
CREATE TABLE portfolio_items (
    id        INT          AUTO_INCREMENT PRIMARY KEY,
    talent_id INT          NOT NULL,
    ptype     ENUM('image','video') NOT NULL,
    url       VARCHAR(500) NOT NULL,
    title     VARCHAR(255) DEFAULT '',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (talent_id) REFERENCES talents(id) ON DELETE CASCADE
);
CREATE TABLE notifications (
    id        INT  AUTO_INCREMENT PRIMARY KEY,
    user_id   INT  NOT NULL,
    message   TEXT NOT NULL,
    link      VARCHAR(500) DEFAULT '',
    is_read   TINYINT(1)   DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)"; }

// ══════════════════════════════════════════════════════
// 3. SEED DATA
// ══════════════════════════════════════════════════════
function db_seed(PDO $c): void {
    // CATEGORIES
    $sc = $c->prepare("INSERT INTO categories (name,pfx,icon) VALUES (?,?,?)");
   foreach ([
    ['Acteur','ACT',''],['Scénariste','SCR',''],['Photographe','PHO',''],
    ['Monteur Vidéo','EDI',''],['Graphiste','DES',''],['Maquilleur','MUA',''],['Styliste','STY',''],
] as $r) $sc->execute($r);

    // ADMIN
    $su = $c->prepare("INSERT INTO users (email,pwhash,role,real_name,phone) VALUES (?,?,?,?,?)");
    $su->execute(['admin@mayase.dz', password_hash('admin123',PASSWORD_DEFAULT), 'admin','Admin Mayase','+213 555 000 001']);

    // TALENTS
    $th  = password_hash('talent123', PASSWORD_DEFAULT);
    $st  = $c->prepare("INSERT INTO talents (user_id,code,nickname,cat_id,province,bio,skills,experience,availability,photo,approved) VALUES (?,?,?,?,?,?,?,?,?,?,1)");

    $tlist = [
        ['amira.bensalem@mail.dz','Amira Bensalem','+213 555 101 001','Amira B.',1,'Alger',
         "Actrice professionnelle avec 8 ans d'expérience en cinéma et théâtre algérien. Passionnée par les rôles dramatiques et les personnages complexes.",
         'Comédie dramatique, Théâtre classique, Improvisation, Doublage, Arabe/Français',
         'INSM Alger. 5 courts-métrages, 3 séries TV, Prix du meilleur rôle Oran 2023.','available','ACT-1023'],
        ['karim.toumi@mail.dz','Karim Toumi','+213 555 101 002','Karim T.',2,'Oran',
         "Scénariste créatif spécialisé dans les drames sociaux et comédies. Auteur de scripts primés au Festival de Cinéma d'Oran.",
         'Scénario long-métrage, Série TV, Dialogue, Story-boarding, Format 52 min',
         '3 longs-métrages produits, 2 séries web, Collaborateur régulier studios oranais.','available','SCR-5021'],
        ['nadia.belkacem@mail.dz','Nadia Belkacem','+213 555 101 003','Nadia B.',3,'Constantine',
         "Photographe de mode et portrait avec un style épuré et contemporain. Spécialisée en shooting de marques locales et portraits artistiques.",
         'Mode, Portrait, Produit, Architecture, Retouche Lightroom/Photoshop',
         '6 ans, collaboration 20+ marques algériennes, expositions régionales.','available','PHO-2045'],
        ['yacine.hamid@mail.dz','Yacine Hamid','+213 555 101 004','Yacine H.',4,'Alger',
         "Monteur vidéo expert en post-production cinématographique. Colorimétrie professionnelle et motion design.",
         'Premiere Pro, DaVinci Resolve, After Effects, Color Grading, Sound Design',
         '10 ans post-production, 50+ clips musicaux, 15+ publicités TV montées.','available','EDI-8021'],
        ['sara.meziane@mail.dz','Sara Meziane','+213 555 101 005','Sara M.',5,'Blida',
         "Graphiste spécialisée en identité visuelle et branding pour entreprises algériennes. Combinaison unique d'esthétique moderne et sensibilité culturelle.",
         'Illustrator, Photoshop, InDesign, Motion Design, Branding, UI/UX',
         '7 ans, 80+ clients, Membre Association Designers Algérie.','available','DES-4031'],
        ['samia.gharbi@mail.dz','Samia Gharbi','+213 555 101 006','Samia G.',6,'Oran',
         "Maquilleuse professionnelle pour cinéma, TV et mariages. Experte en maquillage artistique et transformation pour productions audiovisuelles.",
         'Maquillage SFX, Mariée, Artistique, Effets spéciaux, Perruques, Airbrush',
         '9 ans, 200+ mariages, 30+ productions cinéma et TV.','available','MUA-6542'],
        ['lyes.brahimi@mail.dz','Lyes Brahimi','+213 555 101 007','Lyes B.',1,'Tizi Ouzou',
         "Acteur théâtre et cinéma, formé à Paris et Alger. Polyvalent en registre dramatique, comique et action.",
         'Théâtre contemporain, Comédie, Action, Kabyle/Français/Arabe/Anglais',
         'Conservatoire Paris. 12 pièces de théâtre, 6 films.','available','ACT-1087'],
        ['fatima.benali@mail.dz','Fatima Z. Benali','+213 555 101 008','Fatima Z.',7,'Alger',
         "Styliste de mode créant des collections alliant modernité et patrimoine algérien. Expérience magazines, publicités et télévision.",
         'Stylisme éditorial, Publicitaire, Costume design, Shopping set, Tendances',
         '8 ans fashion, Vogue Arabia collaborations, 5 collections personnelles.','available','STY-9912'],
        ['amine.djaidi@mail.dz','Amine Djaidi','+213 555 101 009','Amine D.',3,'Sétif',
         "Photographe documentaire et commercial. Reconnu pour son approche storytelling unique et son authenticité.",
         'Documentaire, Commercial, Paysage, Reportage, Film photography, Drone',
         '5 ans, publications El Watan Weekend, expositions nationales.','available','PHO-2098'],
        ['rania.oukaci@mail.dz','Rania Oukaci','+213 555 101 010','Rania O.',4,'Béjaïa',
         "Monteuse vidéo créative spécialisée en contenu digital et publicité. Experte en contenus engageants pour réseaux sociaux.",
         'Premiere Pro, Final Cut, Motion Graphics, Reels Instagram, YouTube editing',
         '4 ans, 300+ vidéos montées, Canal YouTube 50K abonnés.','available','EDI-8076'],
        ['sofiane.bouzid@mail.dz','Sofiane Bouzid','+213 555 101 011','Sofiane B.',2,'Alger',
         "Scénariste spécialisé en web séries et contenu humoristique algérien contemporain.",
         'Web series, Contenu digital, Humour, Formats courts, Adaptation culturelle',
         '2 web séries produites, collaboration PlayTV Algérie, auteur primé.','busy','SCR-5067'],
        ['insaf.mekki@mail.dz','Insaf Mekki','+213 555 101 012','Insaf M.',5,'Annaba',
         "Graphiste et illustratrice spécialisée design print et packaging. Forte sensibilité esthétique contemporaine.",
         'Illustration vectorielle, Packaging, Print, Sérigraphie, Brand identity',
         '6 ans, clients: Cevital, Condor Electronics, startups algériennes.','available','DES-4089'],
        ['hakim.slimani@mail.dz','Hakim Slimani','+213 555 101 013','Hakim S.',1,'Constantine',
         "Acteur et stand-up comedian avec une forte présence scénique. Festivals de théâtre de l'Est algérien.",
         'Stand-up, Théâtre, Cinéma, Improvisation, Présentation événements',
         '10 ans de scène, 5 one-man shows, rôles principaux 4 films indépendants.','available','ACT-1156'],
        ['yasmine.kaci@mail.dz','Yasmine Kaci','+213 555 101 014','Yasmine K.',6,'Alger',
         "Maquilleuse et beauty artist spécialisée makeup éditorial et artistique. TV nationale et magazines.",
         'Makeup éditorial, SFX léger, Airbrush, Soins peau, Formations makeup',
         '5 ans, Canal Algérie, Ennahar TV. 3 collections mode.','available','MUA-6601'],
        ['rachid.messaoud@mail.dz','Rachid Messaoud','+213 555 101 015','Rachid M.',4,'Oran',
         "Monteur-réalisateur expert publicité et corporate video. Créatif et méticuleux en post-production.",
         'Montage, Motion design, 3D basique, Corporate video, Publicité',
         '8 ans agences communication Oran, 100+ spots publicitaires.','available','EDI-8134'],
        ['lina.boudiaf@mail.dz','Lina Boudiaf','+213 555 101 016','Lina B.',3,'Batna',
         "Photographe nature et portrait. Valorisation du patrimoine algérien à travers la photographie.",
         'Nature, Portrait, Patrimoine, HDR, Drone photography',
         '4 ans, reportages APS, expositions Batna et Alger.','available','PHO-2156'],
        ['omar.teffah@mail.dz','Omar Teffah','+213 555 101 017','Omar T.',1,'Tlemcen',
         "Acteur polyvalent basé à Tlemcen. Excelle dans les rôles historiques et personnages du patrimoine algérien.",
         'Théâtre classique, Rôles historiques, Arabe classique, Hassaniya, Équitation',
         'Troupe théâtrale Tlemcen 8 ans, 3 films nationaux.','available','ACT-1234'],
        ['amel.cherif@mail.dz','Amel Cherif','+213 555 101 018','Amel C.',7,'Alger',
         "Styliste et costumière pour productions cinéma et TV. Spécialisée historique et mode contemporaine algérienne.",
         'Costume design, Stylisme TV/Cinéma, Couture, Recherche historique, Shopping',
         '11 ans, 20+ productions TV, série historique Nedjma sur ENTV.','available','STY-9967'],
        ['bilal.mebarki@mail.dz','Bilal Mebarki','+213 555 101 019','Bilal M.',5,'Sidi Bel Abbès',
         "Graphiste et directeur artistique freelance. Expert en identités visuelles pour entreprises et institutions.",
         'Direction artistique, Logo design, Charte graphique, Web design, Print',
         '5 ans freelance, 60+ identités visuelles créées.','busy','DES-4145'],
        ['houda.belkacemi@mail.dz','Houda Belkacemi','+213 555 101 020','Houda B.',2,'Mostaganem',
         "Scénariste et auteure dramatique. Drames familiaux et adaptations littéraires algériennes.",
         'Drama familial, Adaptation littéraire, Dialogue, Format TV 26 épisodes, Arabe/Français',
         '6 ans, 2 feuilletons Ramadhan diffusés nationalement, Prix meilleur scénario.','available','SCR-5112'],
    ];

    foreach ($tlist as [$email,$real,$phone,$nick,$cat,$prov,$bio,$skills,$exp,$avail,$code]) {
        $su->execute([$email,$th,'talent',$real,$phone]);
        $uid = $c->lastInsertId();
        $photo = 'https://ui-avatars.com/api/?name='.urlencode($nick).'&background=ec4899&color=fff&size=200&bold=true&font-size=0.33';
        $st->execute([$uid,$code,$nick,$cat,$prov,$bio,$skills,$exp,$avail,$photo]);
    }

    // CLIENTS
    $ch  = password_hash('client123', PASSWORD_DEFAULT);
    $scc = $c->prepare("INSERT INTO clients (user_id,display_name,ctype,province,bio) VALUES (?,?,?,?,?)");
    $clist = [
        ['malik.bennouar@mail.dz','Malik Bennouar','+213 555 201 001','Malik Bennouar Productions','Director','Alger',
         "Réalisateur algérien 15 ans d'expérience. Long-métrage et documentaires culturels."],
        ['imane@imaneproductions.dz','Imane Rahmani','+213 555 201 002','Imane Productions','Agency','Oran',
         "Agence production audiovisuelle Oran. Spécialisée publicité et contenus corporate."],
        ['karima.ziani@studio.dz','Karima Ziani','+213 555 201 003','K. Ziani Studio','Producer','Alger',
         "Productrice indépendante, cinéma engagé et fiction sociale algérienne."],
        ['contact@contentbox.dz','Content Box Team','+213 555 201 004','Content Box Agency','Agency','Alger',
         "Agence digitale création contenu, marketing et branding marques algériennes."],
        ['tariq.fares@creator.dz','Tariq Fares','+213 555 201 005','Tariq Fares','Content Creator','Sétif',
         "Créateur contenu, influenceur 500K abonnés. Projets mode, lifestyle, gastronomie."],
    ];
    foreach ($clist as [$email,$real,$phone,$display,$type,$prov,$bio]) {
        $su->execute([$email,$ch,'client',$real,$phone]);
        $uid = $c->lastInsertId();
        $scc->execute([$uid,$display,$type,$prov,$bio]);
    }

    // PROJECTS
    $sp = $c->prepare("INSERT INTO projects (client_id,title,description,cat_id,province,budget,deadline,status) VALUES (?,?,?,?,?,?,?,?)");
    foreach ([
        [1,"L'Autre Algérie","Long-métrage dramatique explorant l'identité algérienne contemporaine. Recherche acteurs principaux et scénariste pour développement du script.",1,'Alger','500 000 – 1 500 000 DZD','2025-09-30','open'],
        [2,"Couleurs d'Oran","Projet photographique documentant la vie culturelle d'Oran. Recherche photographe avec sensibilité documentaire et storytelling.",3,'Oran','150 000 – 300 000 DZD','2025-07-15','open'],
        [3,"Identité Visuelle Startup Tech","Startup fintech algérienne cherche graphiste pour identité visuelle complète: logo, charte graphique, supports communication.",5,'Alger','200 000 – 400 000 DZD','2025-08-01','open'],
        [1,"Spot Corporate 30 Secondes","Production spot institutionnel 30 secondes. Besoin monteur vidéo expert motion design et colorimétrie cinématographique.",4,'Constantine','300 000 – 600 000 DZD','2025-08-20','open'],
        [2,"Collection Été 2025","Shooting mode pour collection été d'une marque algérienne. Besoin photographe et styliste pour 2 jours de tournage.",3,'Oran','250 000 – 500 000 DZD','2025-07-01','open'],
        [3,"Court-Métrage SFX Fantasy","Court-métrage fantastique nécessitant maquilleur SFX expérimenté et transformations complexes. Tournage 5 jours.",6,'Alger','100 000 – 200 000 DZD','2025-08-15','open'],
        [4,"Web Série Pilote — 4 Épisodes","Pilote web série humoristique 4 épisodes 15 min. Recherche acteur principal et scénariste pour co-développement.",1,'Alger','400 000 – 800 000 DZD','2025-10-01','open'],
        [4,"Rebranding PME Import-Export","PME algérienne cherche graphiste pour refonte identité visuelle complète et création supports print/digitaux.",5,'Blida','150 000 – 300 000 DZD','2025-07-30','open'],
        [5,"Clip Musical — Artiste Pop","Clip musical artiste pop algérienne émergente. Monteur créatif maîtrisant effets visuels et color grading.",4,'Sétif','200 000 – 350 000 DZD','2025-07-20','open'],
        [5,"Livre d'Art — Artisans de Tlemcen","Projet livre d'art documentant les artisans de Tlemcen. Photographe expérimenté portrait et patrimoine.",3,'Tlemcen','180 000 – 280 000 DZD','2025-09-01','open'],
    ] as $r) $sp->execute($r);

    // APPLICATIONS
    $sa = $c->prepare("INSERT INTO applications (project_id,talent_id,message,status) VALUES (?,?,?,?)");
    foreach ([
        [1,1,"Très intéressée par ce projet. Mon expérience dramatique correspond à votre vision.",'pending'],
        [1,7,"Acteur polyvalent, je peux apporter authenticité et profondeur à ce rôle.",'pending'],
        [2,3,"La photographie documentaire est ma spécialité. Portfolio disponible sur demande.",'accepted'],
        [6,6,"Maquillage SFX est mon domaine d'expertise principale avec 9 ans d'expérience.",'pending'],
        [8,5,"Mon portefeuille inclut plusieurs refontes d'identité pour PME algériennes.",'pending'],
        [7,1,"Je serais parfaite pour le rôle principal de cette web série humoristique.",'pending'],
        [9,4,"Expert color grading et motion design pour clips musicaux, 50+ références.",'pending'],
    ] as $r) $sa->execute($r);

    // PORTFOLIO
    $spi = $c->prepare("INSERT INTO portfolio_items (talent_id,ptype,url,title) VALUES (?,?,?,?)");
    foreach ([
        [1,'image','https://images.unsplash.com/photo-1503095396549-807759245b35?w=600&q=80','Rôle dramatique - Théâtre National'],
        [1,'image','https://images.unsplash.com/photo-1516728778615-2d590ea1855e?w=600&q=80','Scène de film 2024'],
        [3,'image','https://images.unsplash.com/photo-1531746020798-e6953c6e8e04?w=600&q=80','Portrait Studio Mode'],
        [3,'image','https://images.unsplash.com/photo-1469334031218-e382a71b716b?w=600&q=80','Shooting Mode Constantine'],
        [3,'image','https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?w=600&q=80','Architecture Urbaine'],
        [4,'image','https://images.unsplash.com/photo-1574717024653-61fd2cf4d44d?w=600&q=80','Post-production Spot TV'],
        [5,'image','https://images.unsplash.com/photo-1561070791-2526d30994b5?w=600&q=80','Identité Visuelle — Projet Alger'],
        [5,'image','https://images.unsplash.com/photo-1626785774573-4b799315345d?w=600&q=80','Packaging Design 2024'],
        [7,'image','https://images.unsplash.com/photo-1517602302552-471fe67acf66?w=600&q=80','Pièce de théâtre — Tizi Ouzou'],
        [9,'image','https://images.unsplash.com/photo-1492691527719-9d1e07e534b4?w=600&q=80','Reportage documentaire — Sétif'],
        [13,'image','https://images.unsplash.com/photo-1520812183191-82e4fd9f5252?w=600&q=80','Festival du Théâtre — Constantine'],
        [14,'image','https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=600&q=80','Makeup éditorial 2024'],
        [8,'image','https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=600&q=80','Stylisme éditorial — Collection'],
        [6,'image','https://images.unsplash.com/photo-1487222477894-8943e31ef7b2?w=600&q=80','Transformation SFX — Cinéma'],
    ] as $r) $spi->execute($r);

    // REQUESTS
    $sr = $c->prepare("INSERT INTO requests (client_id,talent_id,message,status) VALUES (?,?,?,?)");
    foreach ([
        [1,1,"Intéressé par ACT-1023 pour court-métrage. Merci de faciliter le contact.",'pending'],
        [3,3,"Shooting produit urgent. Besoin photographe PHO-2045 disponible cette semaine.",'in_review'],
    ] as $r) $sr->execute($r);

    // ADMIN NOTIFICATIONS
    $sn = $c->prepare("INSERT INTO notifications (user_id,message,link) VALUES (?,?,?)");
    foreach ([
        [1,"Nouvelle demande de contact: Malik Bennouar pour ACT-1023","?p=admin-requests"],
        [1,"20 talents actifs dans la base — Voir le répertoire","?p=admin-talents"],
        [1,"10 projets publiés par 5 clients","?p=admin-projects"],
        [1,"Demande en traitement: PHO-2045 par K. Ziani Studio","?p=admin-requests"],
    ] as $r) $sn->execute($r);
}

// ══════════════════════════════════════════════════════
// 4. HELPERS
// ══════════════════════════════════════════════════════
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES|ENT_HTML5, 'UTF-8'); }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_input(): string { return '<input type="hidden" name="_csrf" value="'.e(csrf_token()).'">'; }
function csrf_check(): void {
    if (!isset($_POST['_csrf']) || !hash_equals(csrf_token(), $_POST['_csrf'])) {
        flash('error','Jeton de sécurité invalide. Veuillez réessayer.');
        redirect(current_url());
    }
}

function flash(string $k, string $v=''): string {
    if ($v !== '') { $_SESSION['flash'][$k]=$v; return ''; }
    $m = $_SESSION['flash'][$k] ?? ''; unset($_SESSION['flash'][$k]); return $m;
}
function redirect(string $url): never { header("Location: $url"); exit; }
function current_url(): string { return $_SERVER['REQUEST_URI'] ?? '?'; }

function notify(int $uid, string $msg, string $link=''): void {
    q("INSERT INTO notifications (user_id,message,link) VALUES (?,?,?)",[$uid,$msg,$link]);
}
function unread_notifs(int $uid): int {
    return (int)q("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0",[$uid])->fetchColumn();
}

function provinces(): array {
    return ['Adrar','Aïn Defla','Aïn Témouchent','Alger','Annaba','Batna','Béchar','Béjaïa','Biskra',
            'Blida','Bordj Bou Arréridj','Boumerdès','Chlef','Constantine','Djelfa','El Bayadh',
            'El Oued','El Tarf','Ghardaïa','Guelma','Illizi','Jijel','Khenchela','Laghouat','M\'sila',
            'Mascara','Médéa','Mila','Mostaganem','Msila','Naâma','Oran','Ouargla','Oum El Bouaghi',
            'Relizane','Saïda','Sétif','Sidi Bel Abbès','Skikda','Souk Ahras','Tamanrasset','Tébessa',
            'Tiaret','Tindouf','Tipaza','Tissemsilt','Tizi Ouzou','Tlemcen'];
}

function gen_code(int $cat_id): string {
    $map = [1=>'ACT',2=>'SCR',3=>'PHO',4=>'EDI',5=>'DES',6=>'MUA',7=>'STY'];
    $pfx = $map[$cat_id] ?? 'TLT';
    $last = row("SELECT code FROM talents WHERE code LIKE ? ORDER BY id DESC LIMIT 1",[$pfx.'-%']);
    if ($last) return $pfx.'-'.(((int)substr($last['code'], strlen($pfx)+1))+1);
    $start = [1=>1000,2=>5000,3=>2000,4=>8000,5=>4000,6=>6000,7=>9000];
    return $pfx.'-'.($start[$cat_id]??1000);
}

function avatar_url(string $name): string {
    return 'https://ui-avatars.com/api/?name='.urlencode($name).'&background=ec4899&color=fff&size=200&bold=true&font-size=0.33';
}

function avail_badge(string $s): string {
    return match($s) {
        'available'   => '<span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full bg-green-100 text-green-700"><span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>Disponible</span>',
        'busy'        => '<span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700"><span class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></span>Occupé</span>',
        'unavailable' => '<span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full bg-red-100 text-red-700"><span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>Indisponible</span>',
        default       => '',
    };
}

function status_badge(string $s): string {
    return match($s) {
        'pending'   => '<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">En attente</span>',
        'accepted'  => '<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Accepté</span>',
        'rejected'  => '<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Refusé</span>',
        'open'      => '<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Ouvert</span>',
        'closed'    => '<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-600">Fermé</span>',
        'draft'     => '<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Brouillon</span>',
        'in_review' => '<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">En traitement</span>',
        'responded' => '<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">Répondu</span>',
        default     => '<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">'.e($s).'</span>',
    };
}

function ago(string $dt): string {
    $d = time()-strtotime($dt);
    if ($d<60)      return "À l'instant";
    if ($d<3600)    return floor($d/60).' min';
    if ($d<86400)   return floor($d/3600).'h';
    if ($d<2592000) return floor($d/86400).'j';
    return date('d/m/Y',strtotime($dt));
}

// ══════════════════════════════════════════════════════
// 5. AUTH
// ══════════════════════════════════════════════════════
function user(): ?array { return $_SESSION['user'] ?? null; }

function require_auth(?string $role=null): array {
    $u = user();
    if (!$u) { flash('error','Connexion requise.'); redirect('?p=login'); }
    if ($role && $u['role']!==$role) { flash('error','Accès non autorisé.'); redirect('?p=dashboard'); }
    return $u;
}

// ══════════════════════════════════════════════════════
// 6. ACTION HANDLERS
// ══════════════════════════════════════════════════════
function handle_actions(): void {
    if ($_SERVER['REQUEST_METHOD']!=='POST') return;
    $act = $_POST['_act'] ?? '';
    match($act) {
        'login'          => do_login(),
        'register'       => do_register(),
        'update-talent'  => do_update_talent(),
        'add-portfolio'  => do_add_portfolio(),
        'del-portfolio'  => do_del_portfolio(),
        'post-project'   => do_post_project(),
        'close-project'  => do_close_project(),
        'apply'          => do_apply(),
        'req-talent'     => do_request_talent(),
        'approve-talent' => do_approve_talent(),
        'upd-req'        => do_upd_req(),
        'upd-app'        => do_upd_app(),
        'mark-read'      => do_mark_read(),
        'upd-client'     => do_upd_client(),
        'upd-user-status'=> do_upd_user_status(),
        default          => null,
    };
}

function do_login(): void {
    csrf_check();
    $email = trim($_POST['email']??'');
    $pw    = $_POST['password']??'';
    if (!$email||!$pw) { flash('error','Remplissez tous les champs.'); redirect('?p=login'); }
    $u = row("SELECT * FROM users WHERE email=? AND status='active'",[$email]);
    if (!$u||!password_verify($pw,$u['pwhash'])) { flash('error','Email ou mot de passe incorrect.'); redirect('?p=login'); }
    $_SESSION['user'] = ['id'=>$u['id'],'email'=>$u['email'],'role'=>$u['role'],'real_name'=>$u['real_name']];
    redirect('?p=dashboard');
}

function do_register(): void {
    csrf_check();
    $type  = in_array($_POST['type']??'',['talent','client']) ? $_POST['type'] : 'talent';
    $email = trim($_POST['email']??'');
    $pw    = $_POST['password']??'';
    $real  = trim($_POST['real_name']??'');
    $phone = trim($_POST['phone']??'');
    if (!$email||!$pw||!$real) { flash('error','Champs obligatoires manquants.'); redirect("?p=register&type=$type"); }
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) { flash('error','Email invalide.'); redirect("?p=register&type=$type"); }
    if (strlen($pw)<6) { flash('error','Mot de passe trop court (min 6).'); redirect("?p=register&type=$type"); }
    if (row("SELECT id FROM users WHERE email=?",[$email])) { flash('error','Email déjà utilisé.'); redirect("?p=register&type=$type"); }
    $hash = password_hash($pw,PASSWORD_DEFAULT);
    q("INSERT INTO users (email,pwhash,role,real_name,phone) VALUES (?,?,?,?,?)",[$email,$hash,$type,$real,$phone]);
    $uid = db()->lastInsertId();
    if ($type==='talent') {
        $cat   = max(1,min(7,(int)($_POST['cat_id']??1)));
        $nick  = trim($_POST['nickname']??'') ?: $real;
        $prov  = trim($_POST['province']??'');
        $code  = gen_code($cat);
        $photo = avatar_url($nick);
        q("INSERT INTO talents (user_id,code,nickname,cat_id,province,photo) VALUES (?,?,?,?,?,?)",[$uid,$code,$nick,$cat,$prov,$photo]);
        $admin = row("SELECT id FROM users WHERE role='admin' LIMIT 1");
        if ($admin) notify($admin['id'],"Nouveau talent inscrit: $nick ($code)","?p=admin-talents");
    } else {
        $display = trim($_POST['display_name']??'') ?: $real;
        $ctype   = $_POST['ctype']??'Other';
        $prov    = trim($_POST['province']??'');
        q("INSERT INTO clients (user_id,display_name,ctype,province) VALUES (?,?,?,?)",[$uid,$display,$ctype,$prov]);
    }
    $_SESSION['user'] = ['id'=>$uid,'email'=>$email,'role'=>$type,'real_name'=>$real];
    flash('success','Compte créé! Bienvenue sur '.APP_NAME.'.');
    redirect('?p=dashboard');
}

function do_update_talent(): void {
    $u = require_auth('talent');
    csrf_check();
    $t = row("SELECT id FROM talents WHERE user_id=?",[$u['id']]);
    if (!$t) redirect('?p=t-dash');
    $data = [];
    foreach (['nickname','province','bio','skills','experience','availability'] as $f)
        $data[$f] = trim($_POST[$f]??'');
    if (!empty($_FILES['photo']['name']) && !$_FILES['photo']['error']) {
        $file = $_FILES['photo'];
        $ext  = strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
        if (in_array($ext,['jpg','jpeg','png','webp']) && $file['size']<=5*1024*1024) {
            $name = uniqid('ph_').".$ext";
            move_uploaded_file($file['tmp_name'],__DIR__."/uploads/photos/$name");
            $data['photo'] = "uploads/photos/$name";
        }
    }
    $set  = implode(',',array_map(fn($k)=>"$k=?",array_keys($data)));
    $vals = [...array_values($data),$t['id']];
    q("UPDATE talents SET $set WHERE id=?",$vals);
    flash('success','Profil mis à jour.');
    redirect('?p=t-edit');
}

function do_upd_client(): void {
    $u = require_auth('client');
    csrf_check();
    $c = row("SELECT id FROM clients WHERE user_id=?",[$u['id']]);
    if (!$c) redirect('?p=c-dash');
    $d = trim($_POST['display_name']??'');
    $ct= $_POST['ctype']??'Other';
    $pv= trim($_POST['province']??'');
    $bi= trim($_POST['bio']??'');
    q("UPDATE clients SET display_name=?,ctype=?,province=?,bio=? WHERE id=?",[$d,$ct,$pv,$bi,$c['id']]);
    flash('success','Profil mis à jour.');
    redirect('?p=c-dash');
}

function do_add_portfolio(): void {
    $u = require_auth('talent');
    csrf_check();
    $t = row("SELECT id FROM talents WHERE user_id=?",[$u['id']]);
    if (!$t) redirect('?p=t-portfolio');
    $ptype = $_POST['ptype']??'image';
    $url   = trim($_POST['url']??'');
    $title = trim($_POST['title']??'');
    if (!empty($_FILES['file']['name']) && !$_FILES['file']['error']) {
        $file = $_FILES['file'];
        $ext  = strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
        $allowed = $ptype==='video' ? ['mp4','webm'] : ['jpg','jpeg','png','webp'];
        if (in_array($ext,$allowed) && $file['size']<=10*1024*1024) {
            $name = uniqid('ptf_').".$ext";
            move_uploaded_file($file['tmp_name'],__DIR__."/uploads/portfolio/$name");
            $url = "uploads/portfolio/$name";
        }
    }
    if (!$url) { flash('error','Fournissez une URL ou uploadez un fichier.'); redirect('?p=t-portfolio'); }
    q("INSERT INTO portfolio_items (talent_id,ptype,url,title) VALUES (?,?,?,?)",[$t['id'],$ptype,$url,$title]);
    flash('success','Ajouté au portfolio.');
    redirect('?p=t-portfolio');
}

function do_del_portfolio(): void {
    $u = require_auth('talent');
    csrf_check();
    $t  = row("SELECT id FROM talents WHERE user_id=?",[$u['id']]);
    $id = (int)($_POST['item_id']??0);
    if ($t&&$id) {
        $item = row("SELECT * FROM portfolio_items WHERE id=? AND talent_id=?",[$id,$t['id']]);
        if ($item) {
            if (!str_starts_with($item['url'],'http') && file_exists(__DIR__.'/'.$item['url'])) @unlink(__DIR__.'/'.$item['url']);
            q("DELETE FROM portfolio_items WHERE id=?",[$id]);
            flash('success','Élément supprimé.');
        }
    }
    redirect('?p=t-portfolio');
}

function do_post_project(): void {
    $u = require_auth('client');
    csrf_check();
    $c  = row("SELECT id FROM clients WHERE user_id=?",[$u['id']]);
    if (!$c) redirect('?p=c-dash');
    $id   = (int)($_POST['project_id']??0);
    $titl = trim($_POST['title']??'');
    $desc = trim($_POST['description']??'');
    $cat  = (int)($_POST['cat_id']??0) ?: null;
    $prov = trim($_POST['province']??'');
    $budg = trim($_POST['budget']??'');
    $dead = $_POST['deadline']??null;
    $stat = in_array($_POST['status']??'',['open','draft']) ? $_POST['status'] : 'open';
    if (!$titl||!$desc) { flash('error','Titre et description requis.'); redirect($id?"?p=c-post-project&id=$id":'?p=c-post-project'); }
    if ($id) {
        $ex = row("SELECT id FROM projects WHERE id=? AND client_id=?",[$id,$c['id']]);
        if ($ex) { q("UPDATE projects SET title=?,description=?,cat_id=?,province=?,budget=?,deadline=?,status=? WHERE id=?",[$titl,$desc,$cat,$prov,$budg,$dead?:null,$stat,$id]); flash('success','Projet mis à jour.'); }
    } else {
        q("INSERT INTO projects (client_id,title,description,cat_id,province,budget,deadline,status) VALUES (?,?,?,?,?,?,?,?)",[$c['id'],$titl,$desc,$cat,$prov,$budg,$dead?:null,$stat]);
        $admin = row("SELECT id FROM users WHERE role='admin' LIMIT 1");
        if ($admin) notify($admin['id'],"Nouveau projet publié: $titl","?p=admin-projects");
        flash('success','Projet publié!');
    }
    redirect('?p=c-projects');
}

function do_close_project(): void {
    $u = require_auth('client');
    csrf_check();
    $c = row("SELECT id FROM clients WHERE user_id=?",[$u['id']]);
    $id=(int)($_POST['project_id']??0);
    if ($c&&$id) { q("UPDATE projects SET status='closed' WHERE id=? AND client_id=?",[$id,$c['id']]); flash('success','Projet fermé.'); }
    redirect('?p=c-projects');
}

function do_apply(): void {
    $u = require_auth('talent');
    csrf_check();
    $t  = row("SELECT id FROM talents WHERE user_id=?",[$u['id']]);
    if (!$t) redirect('?p=projects');
    $pid = (int)($_POST['project_id']??0);
    $msg = trim($_POST['message']??'');
    $prj = row("SELECT p.*,c.user_id cu FROM projects p JOIN clients c ON p.client_id=c.id WHERE p.id=? AND p.status='open'",[$pid]);
    if (!$prj) { flash('error','Projet introuvable ou fermé.'); redirect('?p=projects'); }
    if (row("SELECT id FROM applications WHERE project_id=? AND talent_id=?",[$pid,$t['id']])) { flash('error','Candidature déjà envoyée.'); redirect("?p=project&id=$pid"); }
    q("INSERT INTO applications (project_id,talent_id,message) VALUES (?,?,?)",[$pid,$t['id'],$msg]);
    notify($prj['cu'],"Nouvelle candidature: ".$prj['title'],"?p=c-apps");
    $admin=row("SELECT id FROM users WHERE role='admin' LIMIT 1");
    if ($admin) notify($admin['id'],"Nouvelle candidature projet #$pid","?p=admin-apps");
    flash('success','Candidature envoyée!');
    redirect("?p=project&id=$pid");
}

function do_request_talent(): void {
    $u = require_auth('client');
    csrf_check();
    $c  = row("SELECT id FROM clients WHERE user_id=?",[$u['id']]);
    if (!$c) redirect('?p=talents');
    $tid = (int)($_POST['talent_id']??0);
    $msg = trim($_POST['message']??'');
    $t   = row("SELECT * FROM talents WHERE id=? AND approved=1",[$tid]);
    if (!$t) redirect('?p=talents');
    q("INSERT INTO requests (client_id,talent_id,message) VALUES (?,?,?)",[$c['id'],$tid,$msg]);
    $admin=row("SELECT id FROM users WHERE role='admin' LIMIT 1");
    if ($admin) notify($admin['id'],"Nouvelle demande contact: ".$t['code']." par client #".$c['id'],"?p=admin-requests");
    flash('success',"Demande envoyée! L'équipe Mayase vous contactera sous 24h.");
    redirect("?p=talent&id=$tid");
}

function do_approve_talent(): void {
    require_auth('admin');
    csrf_check();
    $id  = (int)($_POST['talent_id']??0);
    $act = $_POST['talent_action']??'approve';
    if ($id) {
        $val = $act==='approve'?1:0;
        q("UPDATE talents SET approved=? WHERE id=?",[$val,$id]);
        flash('success',$act==='approve'?'Talent approuvé.':'Talent suspendu.');
    }
    redirect('?p=admin-talents');
}

function do_upd_req(): void {
    require_auth('admin');
    csrf_check();
    $id   = (int)($_POST['req_id']??0);
    $stat = $_POST['req_status']??'pending';
    $note = trim($_POST['admin_note']??'');
    if ($id) { q("UPDATE requests SET status=?,admin_note=? WHERE id=?",[$stat,$note,$id]); flash('success','Demande mise à jour.'); }
    redirect('?p=admin-requests');
}

function do_upd_app(): void {
    require_auth('admin');
    csrf_check();
    $id   = (int)($_POST['app_id']??0);
    $stat = $_POST['app_status']??'pending';
    if ($id) { q("UPDATE applications SET status=? WHERE id=?",[$stat,$id]); flash('success','Candidature mise à jour.'); }
    redirect('?p=admin-apps');
}

function do_mark_read(): void {
    $u = user(); if (!$u) redirect('?p=login');
    q("UPDATE notifications SET is_read=1 WHERE user_id=?",[$u['id']]);
    redirect($_POST['back']??'?p=dashboard');
}

function do_upd_user_status(): void {
    require_auth('admin');
    csrf_check();
    $id   = (int)($_POST['user_id']??0);
    $stat = in_array($_POST['user_status']??'',['active','suspended']) ? $_POST['user_status'] : 'active';
    if ($id>1) { q("UPDATE users SET status=? WHERE id=?",[$stat,$id]); flash('success','Utilisateur mis à jour.'); }
    redirect('?p=admin-users');
}

// ══════════════════════════════════════════════════════
// 7. ROUTING
// ══════════════════════════════════════════════════════
handle_actions();

$p = $_GET['p'] ?? 'home';

// Logout (GET)
if ($p==='logout') { session_destroy(); redirect('?p=home'); }

// Dashboard redirect
if ($p==='dashboard') {
    $u = user();
    if (!$u) redirect('?p=login');
    redirect(match($u['role']) { 'admin'=>'?p=admin', 'talent'=>'?p=t-dash', 'client'=>'?p=c-dash', default=>'?p=home' });
}

// Page title map
$titles = [
    'home'=>'Accueil','talents'=>'Talents','talent'=>'Profil Talent','projects'=>'Projets','project'=>'Détail Projet',
    'login'=>'Connexion','register'=>"S'inscrire",'t-dash'=>'Mon Dashboard','t-edit'=>'Mon Profil',
    't-portfolio'=>'Mon Portfolio','t-apps'=>'Mes Candidatures','c-dash'=>'Dashboard Client',
    'c-projects'=>'Mes Projets','c-post-project'=>'Publier un Projet','c-requests'=>'Mes Demandes','c-apps'=>'Candidatures',
    'admin'=>'Admin Dashboard','admin-talents'=>'Gérer les Talents','admin-projects'=>'Gérer les Projets',
    'admin-requests'=>'Demandes','admin-apps'=>'Candidatures','admin-users'=>'Utilisateurs',
];
$title = $titles[$p] ?? 'Page';

// ══════════════════════════════════════════════════════
// 8. RENDER: LAYOUT WRAPPERS
// ══════════════════════════════════════════════════════
function start_html(string $title): void { ?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($title)?> — <?=APP_NAME?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = { theme: { extend: { colors: {
    brand: { DEFAULT:'#ec4899', 50:'#fdf2f8', 100:'#fce7f3', 500:'#ec4899', 600:'#db2777', 700:'#be185d' }
}}}}
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%23ec4899'/><text y='.9em' font-size='70' x='50%' dominant-baseline='middle' text-anchor='middle' font-family='system-ui' font-weight='900' fill='white'>M</text></svg>">
<style>
*{font-family:'Inter',system-ui,sans-serif}
.grad-text{background:linear-gradient(135deg,#ec4899,#db2777);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.hero-bg{background:linear-gradient(135deg,#000000 0%,#0d0008 60%,#1a0012 100%)}
.card-lift{transition:transform .18s ease,box-shadow .18s ease}
.card-lift:hover{transform:translateY(-3px);box-shadow:0 20px 40px rgba(0,0,0,.12)}
input:focus,textarea:focus,select:focus{outline:none;ring:0;border-color:#ec4899!important;box-shadow:0 0 0 3px rgba(236,72,153,.15)!important}
.sidebar-active{background:#fdf2f8;color:#ec4899;border-right:3px solid #ec4899;font-weight:600}
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:#f1f1f1}
::-webkit-scrollbar-thumb{background:#ec4899;border-radius:10px}
.line-clamp-2{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
</style>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen">
<?php }

function render_nav(): void {
    $u     = user();
    $notif = $u ? unread_notifs($u['id']) : 0;
    $pg    = $_GET['p'] ?? 'home';
    $nl    = fn($pp) => $pg===$pp ? 'text-pink-400 font-semibold' : 'text-gray-300 hover:text-white'; ?>
<nav class="bg-black sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4">
    <div class="flex items-center justify-between h-16">
      <a href="?p=home" class="flex items-center gap-2 flex-shrink-0">
        <span class="text-2xl font-black text-white leading-none">M<span class="text-pink-500">.</span></span>
        <span class="text-white font-bold text-lg hidden sm:block tracking-tight"><?=APP_NAME?></span>
      </a>
      <div class="hidden md:flex items-center gap-1">
        <a href="?p=home"     class="px-3 py-2 text-sm rounded-lg transition-colors <?=$nl('home')?>">Accueil</a>
        <a href="?p=talents"  class="px-3 py-2 text-sm rounded-lg transition-colors <?=$nl('talents')?>">Talents</a>
        <a href="?p=projects" class="px-3 py-2 text-sm rounded-lg transition-colors <?=$nl('projects')?>">Projets</a>
      </div>
      <div class="flex items-center gap-2">
        <?php if ($u): ?>
          <?php if ($notif>0): ?>
          <a href="?p=<?=$u['role']==='admin'?'admin':'dashboard'?>" class="relative p-2 text-gray-300 hover:text-white transition-colors" title="<?=$notif?> notification(s)">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            <span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-pink-500 text-white text-xs rounded-full flex items-center justify-center leading-none"><?=$notif?></span>
          </a>
          <?php endif ?>
          <a href="?p=dashboard" class="flex items-center gap-1.5 px-3 py-1.5 bg-white/10 hover:bg-white/20 rounded-lg transition-colors">
            <span class="w-6 h-6 bg-pink-500 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"><?=strtoupper(substr($u['real_name'],0,1))?></span>
            <span class="text-sm font-medium text-white hidden sm:block"><?=e(explode(' ',$u['real_name'])[0])?></span>
          </a>
          <a href="?p=logout" onclick="return confirm('Se déconnecter?')" class="p-2 text-gray-400 hover:text-white transition-colors" title="Déconnexion">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          </a>
        <?php else: ?>
          <a href="?p=login"    class="px-4 py-2 text-sm font-medium text-gray-300 hover:text-white transition-colors">Connexion</a>
          <a href="?p=register" class="px-4 py-2 text-sm font-semibold bg-pink-500 hover:bg-pink-600 text-white rounded-lg transition-colors">S'inscrire</a>
        <?php endif ?>
        <button onclick="document.getElementById('mob-menu').classList.toggle('hidden')" class="md:hidden p-2 text-gray-300 hover:text-white">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
      </div>
    </div>
    <div id="mob-menu" class="hidden md:hidden border-t border-white/10 py-3 space-y-1">
      <a href="?p=home"     class="block px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/5 rounded-lg">Accueil</a>
      <a href="?p=talents"  class="block px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/5 rounded-lg">Talents</a>
      <a href="?p=projects" class="block px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/5 rounded-lg">Projets</a>
    </div>
  </div>
</nav>
<?php }

function render_flash(): void {
    $err = flash('error');
    $ok  = flash('success'); ?>
<?php if($err): ?>
<div id="fe" class="fixed top-20 right-4 z-50 max-w-sm bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-2xl shadow-xl flex items-start gap-3 animate-bounce-once">
  <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
  <span class="text-sm flex-1"><?=e($err)?></span>
  <button onclick="document.getElementById('fe').remove()" class="text-red-400 hover:text-red-600 font-bold text-lg leading-none">&times;</button>
</div>
<?php endif; if($ok): ?>
<div id="fs" class="fixed top-20 right-4 z-50 max-w-sm bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-2xl shadow-xl flex items-start gap-3">
  <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
  <span class="text-sm flex-1"><?=e($ok)?></span>
  <button onclick="document.getElementById('fs').remove()" class="text-green-400 hover:text-green-600 font-bold text-lg leading-none">&times;</button>
</div>
<?php endif; }

function render_footer(): void { ?>
<footer class="bg-black text-gray-400 mt-20">
  <div class="max-w-7xl mx-auto px-4 py-14">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-10">
      <div class="md:col-span-2">
        <div class="flex items-center gap-2 mb-4"><span class="text-3xl font-black text-white">M<span class="text-pink-500">.</span></span><span class="text-white font-bold text-xl"><?=APP_NAME?></span></div>
        <p class="text-sm leading-relaxed text-gray-500 mb-5 max-w-sm">La plateforme de médiation qui connecte les talents créatifs algériens — acteurs, photographes, graphistes, stylistes — aux meilleurs projets.</p>
      </div>
      <div>
        <h4 class="text-white text-sm font-semibold mb-4">Plateforme</h4>
        <ul class="space-y-2 text-sm">
          <li><a href="?p=talents" class="hover:text-pink-400 transition-colors">Répertoire Talents</a></li>
          <li><a href="?p=projects" class="hover:text-pink-400 transition-colors">Projets Ouverts</a></li>
          <li><a href="?p=register&type=talent" class="hover:text-pink-400 transition-colors">Devenir Talent</a></li>
          <li><a href="?p=register&type=client" class="hover:text-pink-400 transition-colors">Publier un Projet</a></li>
        </ul>
      </div>
      <div>
        <h4 class="text-white text-sm font-semibold mb-4">Catégories</h4>
        <ul class="space-y-2 text-sm">
          <?php foreach(rows("SELECT * FROM categories") as $c): ?>
          <li><a href="?p=talents&cat=<?=$c['id']?>" class="hover:text-pink-400 transition-colors"><?=e($c['icon'])?> <?=e($c['name'])?></a></li>
          <?php endforeach ?>
        </ul>
      </div>
    </div>
    <div class="border-t border-white/10 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-600">
      <p>© <?=date('Y')?> <?=APP_NAME?>. Tous droits réservés.</p>
      <p>Système de médiation — Aucune coordonnée directe partagée.</p>
    </div>
  </div>
</footer>
<script>
function openLightbox(src){document.getElementById('lb-img').src=src;document.getElementById('lb').classList.remove('hidden');}
setTimeout(()=>{const fe=document.getElementById('fe');if(fe)fe.style.opacity='0';const fs=document.getElementById('fs');if(fs)fs.style.opacity='0';},4000);
</script>
<div id="lb" class="hidden fixed inset-0 bg-black/90 flex items-center justify-center z-[100] p-4 cursor-zoom-out" onclick="this.classList.add('hidden')">
  <img id="lb-img" src="" class="max-w-full max-h-full rounded-2xl shadow-2xl object-contain">
</div>
</body></html>
<?php }

// ══════════════════════════════════════════════════════
// 9. TALENT CARD (reusable)
// ══════════════════════════════════════════════════════
function talent_card(array $t): void { ?>
<div class="card-lift bg-white border border-gray-200 rounded-2xl overflow-hidden">
  <div class="p-5">
    <div class="flex items-start gap-3 mb-3">
      <img src="<?=e($t['photo']?:avatar_url($t['nickname']))?>" alt="" class="w-14 h-14 rounded-xl object-cover flex-shrink-0">
      <div class="flex-1 min-w-0">
        <h3 class="font-bold text-gray-900 truncate"><?=e($t['nickname'])?></h3>
        <div class="text-xs text-gray-500 mt-0.5"><?=e($t['icon']??'')?> <?=e($t['cat_name']??'')?></div>
        <code class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded-md mt-1 inline-block font-mono"><?=e($t['code'])?></code>
      </div>
      <div class="flex-shrink-0"><?=avail_badge($t['availability'])?></div>
    </div>
    <?php if($t['bio']): ?>
    <p class="text-gray-500 text-xs line-clamp-2 mb-3 leading-relaxed"><?=e(mb_substr($t['bio'],0,110))?></p>
    <?php endif ?>
    <div class="flex items-center justify-between pt-2 border-t border-gray-100">
      <span class="text-xs text-gray-400 flex items-center gap-1">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
        <?=e($t['province'])?>
      </span>
      <a href="?p=talent&id=<?=$t['id']?>" class="px-3 py-1.5 bg-pink-500 hover:bg-pink-600 text-white text-xs font-semibold rounded-lg transition-colors">Voir Profil</a>
    </div>
  </div>
</div>
<?php }

// ══════════════════════════════════════════════════════
// 10. PAGE: HOME
// ══════════════════════════════════════════════════════
function page_home(): void {
    $stats = [
        'talents'    => (int)(row("SELECT COUNT(*) c FROM talents WHERE approved=1")['c']??0),
        'projects'   => (int)(row("SELECT COUNT(*) c FROM projects WHERE status='open'")['c']??0),
        'clients'    => (int)(row("SELECT COUNT(*) c FROM clients")['c']??0),
        'categories' => (int)(row("SELECT COUNT(*) c FROM categories")['c']??0),
    ];
    $featured  = rows("SELECT t.*,c.name cat_name,c.icon FROM talents t JOIN categories c ON t.cat_id=c.id WHERE t.approved=1 ORDER BY RAND() LIMIT 6");
    $lat_projs = rows("SELECT p.*,c.name cat_name,c.icon,cl.display_name cl_name FROM projects p LEFT JOIN categories c ON p.cat_id=c.id JOIN clients cl ON p.client_id=cl.id WHERE p.status='open' ORDER BY p.created_at DESC LIMIT 4");
    $categories= rows("SELECT c.*,COUNT(t.id) cnt FROM categories c LEFT JOIN talents t ON t.cat_id=c.id AND t.approved=1 GROUP BY c.id ORDER BY c.id"); ?>

    <!-- HERO -->
    <section class="hero-bg text-white py-24 md:py-40 relative overflow-hidden">
      <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-1/3 left-1/4 w-96 h-96 bg-pink-500/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 right-1/4 w-80 h-80 bg-pink-700/8 rounded-full blur-3xl"></div>
      </div>
      <div class="max-w-7xl mx-auto px-4 text-center relative z-10">
        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/10 px-4 py-2 rounded-full text-sm mb-8">
          <span class="w-2 h-2 bg-pink-400 rounded-full animate-pulse"></span>
          Plateforme N°1 des talents créatifs en Algérie
        </div>
        <h1 class="text-5xl sm:text-6xl md:text-8xl font-black mb-6 leading-[1.05] tracking-tight">
          Découvrez<br>les <span class="grad-text">Talents.</span><br>Créez les <span class="grad-text">Opportunités.</span>
        </h1>
        <p class="text-lg md:text-xl text-gray-300 mb-10 max-w-2xl mx-auto leading-relaxed">Acteurs, photographes, graphistes, stylistes — Mayase connecte les créatifs algériens aux projets qui les méritent.</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center mb-16">
          <a href="?p=talents" class="px-8 py-4 bg-pink-500 hover:bg-pink-600 text-white font-bold rounded-2xl text-base transition-all hover:scale-105 shadow-lg shadow-pink-500/30">Explorer les Talents</a>
          <a href="?p=<?=user()?'c-post-project':'register&type=client'?>" class="px-8 py-4 bg-white/10 hover:bg-white/20 text-white font-bold rounded-2xl text-base transition-all border border-white/20">Publier un Projet</a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 max-w-3xl mx-auto">
          <?php foreach([
            [$stats['talents'],'Talents'],[$stats['projects'],'Projets actifs'],
            [$stats['clients'],'Clients'],[$stats['categories'],'Catégories'],
          ] as [$n,$l]): ?>
          <div class="text-center"><div class="text-4xl font-black text-pink-400"><?=$n?>+</div><div class="text-gray-500 text-sm mt-1"><?=$l?></div></div>
          <?php endforeach ?>
        </div>
      </div>
    </section>

    <!-- CATEGORIES -->
    <section class="py-16 bg-white">
      <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-10">
          <h2 class="text-3xl font-bold mb-2">Explorez par Catégorie</h2>
          <p class="text-gray-500 text-sm">Trouvez le profil créatif dont vous avez besoin</p>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
          <?php foreach($categories as $cat): ?>
          <a href="?p=talents&cat=<?=$cat['id']?>" class="card-lift flex flex-col items-center p-4 bg-gray-50 hover:bg-pink-50 rounded-2xl text-center border border-transparent hover:border-pink-200 transition-all group">
            <span class="text-3xl mb-2"><?=$cat['icon']?></span>
            <span class="font-semibold text-xs text-gray-800 group-hover:text-pink-700"><?=e($cat['name'])?></span>
            <span class="text-xs text-gray-400 mt-0.5"><?=$cat['cnt']?> talents</span>
          </a>
          <?php endforeach ?>
        </div>
      </div>
    </section>

    <!-- FEATURED TALENTS -->
    <section class="py-16 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-end justify-between mb-10">
          <div>
            <p class="text-pink-500 text-sm font-semibold uppercase tracking-wider mb-1">À la une</p>
            <h2 class="text-3xl font-bold">Talents en Vedette</h2>
          </div>
          <a href="?p=talents" class="hidden sm:flex items-center gap-1 text-sm font-medium text-pink-500 hover:text-pink-600">Voir tous <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
          <?php foreach($featured as $t) talent_card($t); ?>
        </div>
        <div class="text-center mt-8"><a href="?p=talents" class="inline-flex items-center gap-2 px-6 py-3 border border-pink-200 text-pink-600 hover:bg-pink-50 rounded-xl font-medium text-sm transition-colors">Voir tous les talents →</a></div>
      </div>
    </section>

    <!-- LATEST PROJECTS -->
    <section class="py-16 bg-white">
      <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-end justify-between mb-10">
          <div>
            <p class="text-pink-500 text-sm font-semibold uppercase tracking-wider mb-1">Opportunités</p>
            <h2 class="text-3xl font-bold">Projets Récents</h2>
          </div>
          <a href="?p=projects" class="hidden sm:flex items-center gap-1 text-sm font-medium text-pink-500 hover:text-pink-600">Voir tous <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <?php foreach($lat_projs as $p): ?>
          <a href="?p=project&id=<?=$p['id']?>" class="card-lift block bg-white border border-gray-200 hover:border-pink-200 rounded-2xl p-6 transition-all">
            <div class="flex items-start justify-between gap-3 mb-3">
              <h3 class="font-bold text-gray-900 leading-tight"><?=e($p['title'])?></h3>
              <?=status_badge($p['status'])?>
            </div>
            <p class="text-gray-500 text-sm mb-4 line-clamp-2 leading-relaxed"><?=e(mb_substr($p['description'],0,140))?></p>
            <div class="flex flex-wrap gap-3 text-xs text-gray-400">
              <?php if($p['cat_name']): ?><span><?=e($p['icon']??'🎬')?> <?=e($p['cat_name'])?></span><?php endif ?>
              <?php if($p['province']): ?><span>📍 <?=e($p['province'])?></span><?php endif ?>
              <?php if($p['budget']): ?><span>💰 <?=e($p['budget'])?></span><?php endif ?>
            </div>
          </a>
          <?php endforeach ?>
        </div>
      </div>
    </section>

    <!-- HOW IT WORKS -->
    <section class="py-20 bg-gray-950 text-white">
      <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-14">
          <p class="text-pink-400 text-sm font-semibold uppercase tracking-wider mb-2">Processus</p>
          <h2 class="text-3xl font-bold mb-3">Comment ça marche ?</h2>
          <p class="text-gray-500 max-w-md mx-auto text-sm">Un système de médiation pour protéger les talents et garantir la qualité des collaborations</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
          <?php foreach([
            ['🎭','Créez votre Profil',"Inscrivez-vous et construisez un profil professionnel avec portfolio, compétences et expérience.",'01'],
            ['🔍','Soyez Découvert',"Les clients et directeurs parcourent la plateforme et font une demande de contact via notre équipe.",'02'],
            ['🤝','Collaboration Sécurisée',"Notre équipe joue le rôle de médiateur. Vos coordonnées restent privées jusqu'à votre accord.",'03'],
          ] as [$ic,$tt,$dd,$nn]): ?>
          <div class="text-center">
            <div class="relative inline-block mb-6">
              <div class="w-18 h-18 w-[72px] h-[72px] bg-pink-500/15 border border-pink-500/20 rounded-3xl flex items-center justify-center text-3xl mx-auto"><?=$ic?></div>
              <span class="absolute -top-2 -right-2 w-6 h-6 bg-pink-500 text-white text-xs font-bold rounded-full flex items-center justify-center"><?=$nn?></span>
            </div>
            <h3 class="text-lg font-bold mb-2"><?=$tt?></h3>
            <p class="text-gray-500 text-sm leading-relaxed max-w-xs mx-auto"><?=$dd?></p>
          </div>
          <?php endforeach ?>
        </div>
        <div class="text-center mt-12">
          <a href="?p=register" class="px-8 py-4 bg-pink-500 hover:bg-pink-600 text-white font-bold rounded-2xl transition-all hover:scale-105 shadow-lg shadow-pink-500/30 inline-block">Rejoindre Mayase</a>
        </div>
      </div>
    </section>
<?php }

// ══════════════════════════════════════════════════════
// 11. PAGE: TALENTS DIRECTORY
// ══════════════════════════════════════════════════════
function page_talents(): void {
    $cat  = (int)($_GET['cat']??0);
    $prov = trim($_GET['province']??'');
    $q    = trim($_GET['q']??'');
    $avail= trim($_GET['avail']??'');
    $where= ["t.approved=1"]; $params=[];
    if ($cat)   { $where[]="t.cat_id=?";    $params[]=$cat; }
    if ($prov)  { $where[]="t.province=?";  $params[]=$prov; }
    if ($avail) { $where[]="t.availability=?"; $params[]=$avail; }
    if ($q)     { $where[]="(t.nickname LIKE ? OR t.bio LIKE ? OR t.skills LIKE ? OR t.code LIKE ?)"; $s="%$q%"; $params=array_merge($params,[$s,$s,$s,$s]); }
    $sql = "SELECT t.*,c.name cat_name,c.icon FROM talents t JOIN categories c ON t.cat_id=c.id WHERE ".implode(' AND ',$where)." ORDER BY t.created_at DESC";
    $talents = rows($sql,$params);
    $cats    = rows("SELECT * FROM categories"); ?>
    <div class="bg-white border-b border-gray-100">
      <div class="max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-1">Répertoire des Talents</h1>
        <p class="text-gray-500 text-sm"><?=count($talents)?> talent<?=count($talents)>1?'s':''?> créatif<?=count($talents)>1?'s':''?> trouvé<?=count($talents)>1?'s':''?></p>
      </div>
    </div>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <div class="bg-white rounded-2xl border border-gray-200 p-5 mb-7">
        <form method="GET" class="flex flex-col md:flex-row gap-3">
          <input type="hidden" name="p" value="talents">
          <input type="text" name="q" value="<?=e($q)?>" placeholder="Rechercher nom, code, compétence..." class="flex-1 px-4 py-2.5 border border-gray-300 rounded-xl text-sm">
          <select name="cat" class="px-4 py-2.5 border border-gray-300 rounded-xl text-sm bg-white">
            <option value="">Toutes catégories</option>
            <?php foreach($cats as $c): ?><option value="<?=$c['id']?>" <?=$cat==$c['id']?'selected':''?>><?=e($c['icon'])?> <?=e($c['name'])?></option><?php endforeach ?>
          </select>
          <select name="province" class="px-4 py-2.5 border border-gray-300 rounded-xl text-sm bg-white">
            <option value="">Toutes wilayas</option>
            <?php foreach(provinces() as $pp): ?><option value="<?=e($pp)?>" <?=$prov===$pp?'selected':''?>><?=e($pp)?></option><?php endforeach ?>
          </select>
          <select name="avail" class="px-4 py-2.5 border border-gray-300 rounded-xl text-sm bg-white">
            <option value="">Disponibilité</option>
            <option value="available" <?=$avail==='available'?'selected':''?>>Disponible</option>
            <option value="busy" <?=$avail==='busy'?'selected':''?>>Occupé</option>
          </select>
          <button type="submit" class="px-6 py-2.5 bg-pink-500 hover:bg-pink-600 text-white font-medium rounded-xl text-sm">Filtrer</button>
          <?php if($cat||$prov||$q||$avail): ?><a href="?p=talents" class="px-5 py-2.5 border border-gray-300 text-gray-600 hover:bg-gray-50 font-medium rounded-xl text-sm text-center">✕</a><?php endif ?>
        </form>
      </div>
      <div class="flex gap-2 flex-wrap mb-7">
        <a href="?p=talents" class="px-4 py-1.5 rounded-full text-xs font-semibold <?=!$cat?'bg-pink-500 text-white':'bg-white border border-gray-200 text-gray-600 hover:border-pink-300'?> transition-colors">Tous</a>
        <?php foreach($cats as $c): ?>
        <a href="?p=talents&cat=<?=$c['id']?>" class="px-4 py-1.5 rounded-full text-xs font-semibold <?=$cat==$c['id']?'bg-pink-500 text-white':'bg-white border border-gray-200 text-gray-600 hover:border-pink-300'?> transition-colors"><?=e($c['icon'])?> <?=e($c['name'])?></a>
        <?php endforeach ?>
      </div>
      <?php if(empty($talents)): ?>
      <div class="text-center py-20 text-gray-400">
        <div class="text-6xl mb-4">🔍</div>
        <h3 class="text-xl font-semibold mb-2 text-gray-600">Aucun talent trouvé</h3>
        <p class="text-sm">Modifiez vos critères de recherche</p>
        <a href="?p=talents" class="inline-block mt-4 px-5 py-2.5 bg-pink-500 text-white rounded-xl text-sm font-medium">Réinitialiser</a>
      </div>
      <?php else: ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
        <?php foreach($talents as $t) talent_card($t); ?>
      </div>
      <?php endif ?>
    </div>
<?php }

// ══════════════════════════════════════════════════════
// 12. PAGE: TALENT PROFILE
// ══════════════════════════════════════════════════════
function page_talent(): void {
    $id = (int)($_GET['id']??0);
    $t  = row("SELECT t.*,c.name cat_name,c.icon,u.created_at reg FROM talents t JOIN categories c ON t.cat_id=c.id JOIN users u ON t.user_id=u.id WHERE t.id=? AND t.approved=1",[$id]);
    if (!$t) { ?>
    <div class="max-w-lg mx-auto text-center py-32 px-4">
      <div class="text-5xl mb-4">😕</div><h1 class="text-2xl font-bold mb-2">Talent introuvable</h1>
      <p class="text-gray-500 mb-6">Ce profil n'existe pas ou n'est pas encore approuvé.</p>
      <a href="?p=talents" class="px-6 py-3 bg-pink-500 text-white rounded-xl font-medium">← Retour aux Talents</a>
    </div>
    <?php return; }
    $portfolio   = rows("SELECT * FROM portfolio_items WHERE talent_id=? ORDER BY created_at DESC",[$t['id']]);
    $u           = user();
    $is_client   = $u && $u['role']==='client';
    $client      = $is_client ? row("SELECT * FROM clients WHERE user_id=?",[$u['id']]) : null;
    $already_req = $client  ? row("SELECT id FROM requests WHERE client_id=? AND talent_id=? AND status NOT IN ('closed')",[$client['id'],$t['id']]) : null;
    $is_talent   = $u && $u['role']==='talent';
    $is_admin    = $u && $u['role']==='admin'; ?>

    <div class="max-w-5xl mx-auto px-4 py-10">
      <a href="?p=talents" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-pink-500 mb-6 transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Répertoire des Talents</a>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- SIDEBAR -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-2xl border border-gray-200 p-6 sticky top-24">
            <div class="text-center mb-5">
              <img src="<?=e($t['photo']?:avatar_url($t['nickname']))?>" class="w-28 h-28 rounded-2xl mx-auto mb-4 object-cover border-4 border-pink-100 shadow-md" alt="">
              <h1 class="text-xl font-bold text-gray-900"><?=e($t['nickname'])?></h1>
              <p class="text-sm text-gray-500 mt-1"><?=e($t['icon'])?> <?=e($t['cat_name'])?></p>
              <code class="block mt-2 text-sm font-mono bg-gray-100 text-gray-600 px-3 py-1 rounded-lg"><?=e($t['code'])?></code>
              <div class="mt-3"><?=avail_badge($t['availability'])?></div>
            </div>
            <div class="space-y-2.5 text-sm border-t border-gray-100 pt-4 mb-5">
              <div class="flex items-center gap-2 text-gray-600"><svg class="w-4 h-4 text-pink-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg><?=e($t['province'])?></div>
              <div class="flex items-center gap-2 text-gray-600"><svg class="w-4 h-4 text-pink-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>Membre depuis <?=date('M Y',strtotime($t['reg']))?></div>
            </div>
            <?php if($is_admin): ?>
            <div class="bg-gray-50 rounded-xl p-3 text-xs text-gray-500 text-center">Mode Admin — Coordonnées visibles dans <a href="?p=admin-talents" class="text-pink-500 font-medium">Gérer Talents</a></div>
            <?php elseif($is_client&&$client): ?>
              <?php if($already_req): ?>
              <div class="bg-green-50 border border-green-200 rounded-xl p-3 text-center text-sm text-green-700">✓ Demande déjà envoyée<br><span class="text-xs text-green-600">Notre équipe vous contactera bientôt</span></div>
              <?php else: ?>
              <button onclick="document.getElementById('rq-modal').classList.remove('hidden')" class="w-full py-3 bg-pink-500 hover:bg-pink-600 text-white font-bold rounded-xl transition-colors">📩 Contacter ce Talent</button>
              <?php endif ?>
            <?php elseif($is_talent): ?>
            <div class="bg-gray-50 rounded-xl p-3 text-xs text-gray-500 text-center">Connecté en tant que Talent</div>
            <?php else: ?>
            <a href="?p=login" class="block text-center w-full py-3 bg-pink-500 hover:bg-pink-600 text-white font-bold rounded-xl transition-colors">Connectez-vous pour contacter</a>
            <?php endif ?>
          </div>
        </div>

        <!-- MAIN -->
        <div class="lg:col-span-2 space-y-5">
          <?php if($t['bio']): ?>
          <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h2 class="text-lg font-bold mb-3">À propos</h2>
            <p class="text-gray-600 leading-relaxed text-sm"><?=nl2br(e($t['bio']))?></p>
          </div>
          <?php endif ?>
          <?php if($t['skills']): ?>
          <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h2 class="text-lg font-bold mb-4">Compétences</h2>
            <div class="flex flex-wrap gap-2">
              <?php foreach(explode(',',$t['skills']) as $sk): ?>
              <span class="px-3 py-1.5 bg-pink-50 border border-pink-100 text-pink-700 rounded-full text-xs font-medium"><?=e(trim($sk))?></span>
              <?php endforeach ?>
            </div>
          </div>
          <?php endif ?>
          <?php if($t['experience']): ?>
          <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h2 class="text-lg font-bold mb-3">Expérience</h2>
            <p class="text-gray-600 leading-relaxed text-sm"><?=nl2br(e($t['experience']))?></p>
          </div>
          <?php endif ?>
          <?php if(!empty($portfolio)): ?>
          <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h2 class="text-lg font-bold mb-4">Portfolio (<?=count($portfolio)?>)</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
              <?php foreach($portfolio as $item): ?>
              <?php if($item['ptype']==='image'): ?>
              <div class="group relative rounded-xl overflow-hidden aspect-square bg-gray-100 cursor-zoom-in" onclick="openLightbox('<?=e($item['url'])?>')">
                <img src="<?=e($item['url'])?>" alt="<?=e($item['title'])?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                <?php if($item['title']): ?>
                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 p-2 translate-y-full group-hover:translate-y-0 transition-transform duration-200">
                  <p class="text-white text-xs truncate"><?=e($item['title'])?></p>
                </div>
                <?php endif ?>
              </div>
              <?php else: ?>
              <div class="rounded-xl overflow-hidden aspect-square bg-gray-900 flex items-center justify-center">
                <?php if(str_contains($item['url'],'youtube')||str_contains($item['url'],'youtu.be')): ?>
                <a href="<?=e($item['url'])?>" target="_blank" rel="noopener" class="flex flex-col items-center gap-2 text-white p-3">
                  <div class="w-12 h-12 bg-red-600 rounded-full flex items-center justify-center"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></div>
                  <span class="text-xs text-gray-300 text-center"><?=e($item['title']?:'Voir sur YouTube')?></span>
                </a>
                <?php else: ?>
                <video src="<?=e($item['url'])?>" controls class="w-full h-full object-cover rounded-xl"></video>
                <?php endif ?>
              </div>
              <?php endif ?>
              <?php endforeach ?>
            </div>
          </div>
          <?php endif ?>
        </div>
      </div>
    </div>

    <?php if($is_client&&$client&&!$already_req): ?>
    <div id="rq-modal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-7 relative">
        <button onclick="document.getElementById('rq-modal').classList.add('hidden')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-2xl font-light leading-none">&times;</button>
        <h3 class="text-xl font-bold mb-1">Contacter <?=e($t['nickname'])?></h3>
        <p class="text-gray-500 text-sm mb-4">Code: <code class="font-mono bg-gray-100 px-2 py-0.5 rounded"><?=e($t['code'])?></code></p>
        <div class="bg-pink-50 border border-pink-100 rounded-xl p-3 mb-4">
          <p class="text-sm text-pink-700"><strong>🔒 Système de médiation Mayase:</strong> Vos coordonnées et celles du talent restent privées. Notre équipe facilite la mise en relation.</p>
        </div>
        <form method="POST">
          <?=csrf_input()?>
          <input type="hidden" name="_act" value="req-talent">
          <input type="hidden" name="talent_id" value="<?=$t['id']?>">
          <label class="block text-sm font-medium text-gray-700 mb-2">Message (décrivez votre projet)</label>
          <textarea name="message" rows="4" placeholder="Décrivez votre projet, le rôle recherché et le contexte..." class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm resize-none mb-4" required></textarea>
          <button type="submit" class="w-full py-3 bg-pink-500 hover:bg-pink-600 text-white font-bold rounded-xl transition-colors">Envoyer la Demande</button>
        </form>
      </div>
    </div>
    <?php endif ?>
<?php }

// ══════════════════════════════════════════════════════
// 13. PAGE: PROJECTS
// ══════════════════════════════════════════════════════
function page_projects(): void {
    $cat  = (int)($_GET['cat']??0);
    $prov = trim($_GET['province']??'');
    $where= ["p.status='open'"]; $params=[];
    if ($cat)  { $where[]="p.cat_id=?";   $params[]=$cat; }
    if ($prov) { $where[]="p.province=?"; $params[]=$prov; }
    $sql  = "SELECT p.*,c.name cat_name,c.icon,cl.display_name cl_name FROM projects p LEFT JOIN categories c ON p.cat_id=c.id JOIN clients cl ON p.client_id=cl.id WHERE ".implode(' AND ',$where)." ORDER BY p.created_at DESC";
    $projs= rows($sql,$params);
    $cats = rows("SELECT * FROM categories");
    $u    = user();
    $tal  = ($u&&$u['role']==='talent') ? row("SELECT id FROM talents WHERE user_id=?",[$u['id']]) : null; ?>
    <div class="bg-white border-b border-gray-100">
      <div class="max-w-7xl mx-auto px-4 py-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div><h1 class="text-3xl font-bold mb-1">Projets Ouverts</h1><p class="text-gray-500 text-sm"><?=count($projs)?> projet(s) disponibles</p></div>
        <?php if($u&&$u['role']==='client'): ?><a href="?p=c-post-project" class="px-5 py-2.5 bg-pink-500 hover:bg-pink-600 text-white font-semibold rounded-xl text-sm transition-colors">+ Publier un Projet</a><?php endif ?>
      </div>
    </div>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <form method="GET" class="flex flex-col md:flex-row gap-3 mb-8">
        <input type="hidden" name="p" value="projects">
        <select name="cat" class="px-4 py-2.5 border border-gray-300 rounded-xl text-sm bg-white">
          <option value="">Toutes catégories</option>
          <?php foreach($cats as $c): ?><option value="<?=$c['id']?>" <?=$cat==$c['id']?'selected':''?>><?=e($c['icon'])?> <?=e($c['name'])?></option><?php endforeach ?>
        </select>
        <select name="province" class="px-4 py-2.5 border border-gray-300 rounded-xl text-sm bg-white">
          <option value="">Toutes wilayas</option>
          <?php foreach(provinces() as $pp): ?><option value="<?=e($pp)?>" <?=$prov===$pp?'selected':''?>><?=e($pp)?></option><?php endforeach ?>
        </select>
        <button type="submit" class="px-6 py-2.5 bg-pink-500 hover:bg-pink-600 text-white font-medium rounded-xl text-sm">Filtrer</button>
        <?php if($cat||$prov): ?><a href="?p=projects" class="px-5 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm text-center">✕</a><?php endif ?>
      </form>
      <div class="space-y-4">
        <?php foreach($projs as $p):
            $applied = $tal ? row("SELECT status FROM applications WHERE project_id=? AND talent_id=?",[$p['id'],$tal['id']]) : null; ?>
        <div class="card-lift bg-white border border-gray-200 rounded-2xl p-6">
          <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
            <div class="flex-1">
              <div class="flex flex-wrap items-center gap-2 mb-2">
                <h3 class="text-lg font-bold text-gray-900"><?=e($p['title'])?></h3>
                <?=status_badge($p['status'])?>
                <?php if($applied): ?><?=status_badge($applied['status'])?><?php endif ?>
              </div>
              <p class="text-gray-600 text-sm mb-3 leading-relaxed"><?=e(mb_substr($p['description'],0,180).'...')?></p>
              <div class="flex flex-wrap gap-3 text-xs text-gray-500">
                <?php if($p['cat_name']): ?><span><?=e($p['icon']??'🎬')?> <?=e($p['cat_name'])?></span><?php endif ?>
                <?php if($p['province']): ?><span>📍 <?=e($p['province'])?></span><?php endif ?>
                <?php if($p['budget']): ?><span>💰 <?=e($p['budget'])?></span><?php endif ?>
                <?php if($p['deadline']): ?><span>📅 <?=date('d/m/Y',strtotime($p['deadline']))?></span><?php endif ?>
              </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
              <a href="?p=project&id=<?=$p['id']?>" class="px-4 py-2 border border-gray-200 text-gray-700 hover:border-pink-300 rounded-xl text-sm font-medium transition-colors">Détails</a>
              <?php if($tal): ?>
                <?php if(!$applied): ?>
                <button onclick="document.getElementById('ap-<?=$p['id']?>').classList.toggle('hidden')" class="px-4 py-2 bg-pink-500 hover:bg-pink-600 text-white rounded-xl text-sm font-semibold transition-colors">Postuler</button>
                <?php else: ?>
                <span class="px-4 py-2 bg-gray-100 text-gray-500 rounded-xl text-sm">Envoyée</span>
                <?php endif ?>
              <?php elseif(!$u): ?>
              <a href="?p=login" class="px-4 py-2 bg-pink-500 hover:bg-pink-600 text-white rounded-xl text-sm font-semibold transition-colors">Postuler</a>
              <?php endif ?>
            </div>
          </div>
          <?php if($tal&&!$applied): ?>
          <div id="ap-<?=$p['id']?>" class="hidden mt-4 bg-gray-50 rounded-xl p-4 border border-gray-200">
            <h4 class="font-semibold text-sm mb-3">Candidature pour: <?=e($p['title'])?></h4>
            <form method="POST">
              <?=csrf_input()?><input type="hidden" name="_act" value="apply"><input type="hidden" name="project_id" value="<?=$p['id']?>">
              <textarea name="message" rows="3" placeholder="Présentez-vous et expliquez pourquoi vous êtes le talent idéal pour ce projet..." class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm resize-none mb-3"></textarea>
              <div class="flex gap-2">
                <button type="submit" class="px-5 py-2 bg-pink-500 hover:bg-pink-600 text-white rounded-xl text-sm font-semibold">Envoyer</button>
                <button type="button" onclick="document.getElementById('ap-<?=$p['id']?>').classList.add('hidden')" class="px-5 py-2 border border-gray-300 text-gray-600 rounded-xl text-sm">Annuler</button>
              </div>
            </form>
          </div>
          <?php endif ?>
        </div>
        <?php endforeach ?>
        <?php if(empty($projs)): ?>
        <div class="text-center py-20 text-gray-400">
          <div class="text-5xl mb-4">📂</div>
          <h3 class="text-xl font-semibold mb-2 text-gray-600">Aucun projet disponible</h3>
          <?php if($u&&$u['role']==='client'): ?><a href="?p=c-post-project" class="inline-block mt-4 px-6 py-3 bg-pink-500 text-white rounded-xl font-medium text-sm">Publiez le premier</a><?php endif ?>
        </div>
        <?php endif ?>
      </div>
    </div>
<?php }

// PAGE: PROJECT DETAIL
function page_project(): void {
    $id = (int)($_GET['id']??0);
    $p  = row("SELECT p.*,c.name cat_name,c.icon,cl.display_name cl_name,cl.ctype,cl.province cp FROM projects p LEFT JOIN categories c ON p.cat_id=c.id JOIN clients cl ON p.client_id=cl.id WHERE p.id=?",[$id]);
    if (!$p) { ?>
    <div class="max-w-lg mx-auto text-center py-32 px-4">
      <h1 class="text-2xl font-bold mb-2">Projet introuvable</h1>
      <a href="?p=projects" class="text-pink-500">← Retour aux projets</a>
    </div>
    <?php return; }
    $apps_count = (int)q("SELECT COUNT(*) FROM applications WHERE project_id=?",[$id])->fetchColumn();
    $u   = user();
    $tal = ($u&&$u['role']==='talent') ? row("SELECT id FROM talents WHERE user_id=?",[$u['id']]) : null;
    $app = $tal ? row("SELECT * FROM applications WHERE project_id=? AND talent_id=?",[$id,$tal['id']]) : null; ?>
    <div class="max-w-4xl mx-auto px-4 py-10">
      <a href="?p=projects" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-pink-500 mb-6"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Retour</a>
      <div class="bg-white rounded-2xl border border-gray-200 p-8 mb-6">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-6">
          <div>
            <div class="flex flex-wrap items-center gap-3 mb-2"><h1 class="text-2xl font-bold"><?=e($p['title'])?></h1><?=status_badge($p['status'])?></div>
            <p class="text-gray-500 text-sm">Par <strong><?=e($p['cl_name'])?></strong> · <?=$apps_count?> candidature(s) · Publié <?=ago($p['created_at'])?></p>
          </div>
          <?php if($tal&&$p['status']==='open'): ?>
            <?php if(!$app): ?>
            <button onclick="document.getElementById('app-mod').classList.remove('hidden')" class="px-6 py-3 bg-pink-500 hover:bg-pink-600 text-white font-bold rounded-xl transition-colors flex-shrink-0">Postuler</button>
            <?php else: ?>
            <div class="text-center px-5 py-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">✓ Candidature envoyée<br><?=status_badge($app['status'])?></div>
            <?php endif ?>
          <?php elseif(!$u&&$p['status']==='open'): ?>
          <a href="?p=login" class="px-6 py-3 bg-pink-500 hover:bg-pink-600 text-white font-bold rounded-xl flex-shrink-0">Postuler</a>
          <?php endif ?>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 bg-gray-50 rounded-2xl p-5 mb-6 text-sm">
          <?php if($p['cat_name']): ?><div><div class="text-xs text-gray-400 mb-1">Catégorie</div><div class="font-semibold"><?=e($p['icon']?:'🎬')?> <?=e($p['cat_name'])?></div></div><?php endif ?>
          <?php if($p['province']): ?><div><div class="text-xs text-gray-400 mb-1">Wilaya</div><div class="font-semibold">📍 <?=e($p['province'])?></div></div><?php endif ?>
          <?php if($p['budget']): ?><div><div class="text-xs text-gray-400 mb-1">Budget</div><div class="font-semibold">💰 <?=e($p['budget'])?></div></div><?php endif ?>
          <?php if($p['deadline']): ?><div><div class="text-xs text-gray-400 mb-1">Deadline</div><div class="font-semibold">📅 <?=date('d/m/Y',strtotime($p['deadline']))?></div></div><?php endif ?>
        </div>
        <h2 class="text-base font-bold mb-3">Description</h2>
        <p class="text-gray-600 leading-relaxed text-sm whitespace-pre-wrap"><?=e($p['description'])?></p>
      </div>
      <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <h3 class="font-bold mb-4">Client</h3>
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-pink-100 rounded-xl flex items-center justify-center text-pink-500 font-black text-lg"><?=strtoupper(substr($p['cl_name'],0,1))?></div>
          <div><div class="font-bold"><?=e($p['cl_name'])?></div><div class="text-sm text-gray-500"><?=e($p['ctype'])?> · <?=e($p['cp']?:$p['province'])?></div></div>
        </div>
      </div>
    </div>
    <?php if($tal&&!$app): ?>
    <div id="app-mod" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-7 relative">
        <button onclick="document.getElementById('app-mod').classList.add('hidden')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        <h3 class="text-xl font-bold mb-4">Postuler: <?=e($p['title'])?></h3>
        <form method="POST">
          <?=csrf_input()?><input type="hidden" name="_act" value="apply"><input type="hidden" name="project_id" value="<?=$p['id']?>">
          <label class="block text-sm font-medium text-gray-700 mb-2">Message de candidature</label>
          <textarea name="message" rows="5" placeholder="Présentez votre expérience et pourquoi vous êtes le bon talent..." class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm resize-none mb-4" required></textarea>
          <button type="submit" class="w-full py-3 bg-pink-500 hover:bg-pink-600 text-white font-bold rounded-xl">Envoyer la Candidature</button>
        </form>
      </div>
    </div>
    <?php endif ?>
<?php }

// ══════════════════════════════════════════════════════
// 14. PAGE: LOGIN
// ══════════════════════════════════════════════════════
function page_login(): void {
    if (user()) redirect('?p=dashboard'); ?>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-pink-50 flex items-center justify-center py-12 px-4">
      <div class="w-full max-w-md">
        <div class="text-center mb-8">
          <a href="?p=home" class="inline-block mb-5"><span class="text-5xl font-black">M<span class="text-pink-500">.</span></span></a>
          <h1 class="text-2xl font-bold">Bon retour</h1>
          <p class="text-gray-500 mt-1 text-sm">Connectez-vous à votre compte <?=APP_NAME?></p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8">
          <form method="POST" class="space-y-4">
            <?=csrf_input()?><input type="hidden" name="_act" value="login">
            <div><label class="block text-sm font-semibold text-gray-700 mb-2">Email</label><input type="email" name="email" required autocomplete="email" class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm" placeholder="votre@email.com"></div>
            <div><label class="block text-sm font-semibold text-gray-700 mb-2">Mot de passe</label><input type="password" name="password" required autocomplete="current-password" class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm" placeholder="••••••••"></div>
            <button type="submit" class="w-full py-3 bg-pink-500 hover:bg-pink-600 text-white font-bold rounded-xl transition-colors shadow-md shadow-pink-500/20">Se Connecter</button>
          </form>
          <div class="mt-6 pt-5 border-t border-gray-100">
            <p class="text-sm text-gray-500 text-center mb-3">Pas encore de compte ?</p>
            <div class="grid grid-cols-2 gap-3">
              <a href="?p=register&type=talent" class="py-2.5 border border-gray-200 hover:border-pink-300 text-gray-700 hover:text-pink-600 rounded-xl text-sm font-medium text-center transition-colors">🎭 Talent</a>
              <a href="?p=register&type=client" class="py-2.5 border border-gray-200 hover:border-pink-300 text-gray-700 hover:text-pink-600 rounded-xl text-sm font-medium text-center transition-colors">🎬 Client</a>
            </div>
          </div>
          <div class="mt-4 p-3 bg-gray-50 rounded-xl text-xs text-gray-500 space-y-1">
            <p class="font-semibold text-gray-700">Comptes de démonstration :</p>
            <p>Admin: admin@mayase.dz / admin123</p>
            <p>Talent: amira.bensalem@mail.dz / talent123</p>
            <p>Client: malik.bennouar@mail.dz / client123</p>
          </div>
        </div>
      </div>
    </div>
<?php }

// ══════════════════════════════════════════════════════
// 15. PAGE: REGISTER
// ══════════════════════════════════════════════════════
function page_register(): void {
    if (user()) redirect('?p=dashboard');
    $type = ($_GET['type']??'talent')==='client'?'client':'talent';
    $cats = rows("SELECT * FROM categories"); ?>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-pink-50 flex items-center justify-center py-12 px-4">
      <div class="w-full max-w-lg">
        <div class="text-center mb-8">
          <a href="?p=home" class="inline-block mb-5"><span class="text-5xl font-black">M<span class="text-pink-500">.</span></span></a>
          <h1 class="text-2xl font-bold">Créer votre compte</h1>
        </div>
        <div class="flex gap-1 mb-6 bg-gray-100 p-1 rounded-2xl">
          <a href="?p=register&type=talent" class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-center transition-all <?=$type==='talent'?'bg-white text-pink-600 shadow-sm':'text-gray-500 hover:text-gray-700'?>">🎭 Je suis un Talent</a>
          <a href="?p=register&type=client" class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-center transition-all <?=$type==='client'?'bg-white text-pink-600 shadow-sm':'text-gray-500 hover:text-gray-700'?>">🎬 Je suis un Client</a>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8">
          <form method="POST" class="space-y-4">
            <?=csrf_input()?><input type="hidden" name="_act" value="register"><input type="hidden" name="type" value="<?=e($type)?>">
            <div class="grid grid-cols-2 gap-4">
              <div class="col-span-2 sm:col-span-1"><label class="block text-sm font-semibold text-gray-700 mb-2">Nom complet *</label><input type="text" name="real_name" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm" placeholder="Prénom Nom"></div>
              <div class="col-span-2 sm:col-span-1"><label class="block text-sm font-semibold text-gray-700 mb-2">Téléphone</label><input type="tel" name="phone" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm" placeholder="+213 ..."></div>
            </div>
            <div><label class="block text-sm font-semibold text-gray-700 mb-2">Email *</label><input type="email" name="email" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm" placeholder="votre@email.com"></div>
            <div><label class="block text-sm font-semibold text-gray-700 mb-2">Mot de passe * <span class="font-normal text-gray-400">(min. 6 caractères)</span></label><input type="password" name="password" required minlength="6" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm"></div>
            <?php if($type==='talent'): ?>
            <div class="grid grid-cols-2 gap-4">
              <div><label class="block text-sm font-semibold text-gray-700 mb-2">Pseudo / Nom de scène</label><input type="text" name="nickname" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm" placeholder="Votre pseudo public"></div>
              <div><label class="block text-sm font-semibold text-gray-700 mb-2">Wilaya *</label>
                <select name="province" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm bg-white">
                  <?php foreach(provinces() as $pp): ?><option value="<?=e($pp)?>"><?=e($pp)?></option><?php endforeach ?>
                </select>
              </div>
            </div>
            <div><label class="block text-sm font-semibold text-gray-700 mb-2">Catégorie *</label>
              <select name="cat_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm bg-white">
                <?php foreach($cats as $c): ?><option value="<?=$c['id']?>"><?=e($c['icon'])?> <?=e($c['name'])?></option><?php endforeach ?>
              </select>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-2 gap-4">
              <div><label class="block text-sm font-semibold text-gray-700 mb-2">Nom affiché *</label><input type="text" name="display_name" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm" placeholder="Nom ou société"></div>
              <div><label class="block text-sm font-semibold text-gray-700 mb-2">Type</label>
                <select name="ctype" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm bg-white">
                  <?php foreach(['Director','Producer','Agency','Content Creator','Other'] as $ct): ?><option><?=$ct?></option><?php endforeach ?>
                </select>
              </div>
            </div>
            <div><label class="block text-sm font-semibold text-gray-700 mb-2">Wilaya</label>
              <select name="province" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm bg-white">
                <?php foreach(provinces() as $pp): ?><option value="<?=e($pp)?>"><?=e($pp)?></option><?php endforeach ?>
              </select>
            </div>
            <?php endif ?>
            <button type="submit" class="w-full py-3 bg-pink-500 hover:bg-pink-600 text-white font-bold rounded-xl transition-colors shadow-md shadow-pink-500/20">Créer mon compte</button>
          </form>
          <p class="text-center text-sm text-gray-500 mt-5">Déjà un compte? <a href="?p=login" class="text-pink-500 font-medium hover:text-pink-600">Se connecter</a></p>
        </div>
      </div>
    </div>
<?php }

// ══════════════════════════════════════════════════════
// HELPER: DASHBOARD SIDEBAR
// ══════════════════════════════════════════════════════
function dash_sidebar(array $links, string $cur): void { ?>
<div class="w-64 flex-shrink-0 hidden md:block">
  <div class="bg-white rounded-2xl border border-gray-200 p-3 sticky top-24">
    <?php foreach($links as [$icon,$label,$href,$key]): ?>
    <a href="<?=$href?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm mb-0.5 transition-all <?=$cur===$key?'sidebar-active':'text-gray-600 hover:bg-gray-50 hover:text-gray-900'?>"><?=$icon?><span><?=$label?></span></a>
    <?php endforeach ?>
  </div>
</div>
<?php }

// ══════════════════════════════════════════════════════
// 16. PAGE: TALENT DASHBOARD
// ══════════════════════════════════════════════════════
function page_t_dash(): void {
    $u = require_auth('talent');
    $t = row("SELECT t.*,c.name cat_name,c.icon FROM talents t JOIN categories c ON t.cat_id=c.id WHERE t.user_id=?",[$u['id']]);
    if (!$t) { echo "<div class='p-8 text-center text-gray-500'>Profil talent introuvable. <a href='?p=t-edit' class='text-pink-500'>Compléter le profil</a></div>"; return; }
    $apps  = rows("SELECT a.*,p.title FROM applications a JOIN projects p ON a.project_id=p.id WHERE a.talent_id=? ORDER BY a.created_at DESC LIMIT 5",[$t['id']]);
    $notifs= rows("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 5",[$u['id']]);
    $portf = (int)q("SELECT COUNT(*) FROM portfolio_items WHERE talent_id=?",[$t['id']])->fetchColumn();
    $pg    = 'dash'; ?>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <div class="flex gap-8">
        <?php dash_sidebar([
          ['🏠','Dashboard','?p=t-dash','dash'],
          ['👤','Mon Profil','?p=t-edit','edit'],
          ['🖼️','Portfolio','?p=t-portfolio','portfolio'],
          ['📋','Candidatures','?p=t-apps','apps'],
          ['🔍','Projets','?p=projects','projects'],
        ],$pg) ?>
        <div class="flex-1 min-w-0">
          <div class="mb-6">
            <h1 class="text-2xl font-bold">Bonjour, <?=e(explode(' ',$u['real_name'])[0])?> 👋</h1>
            <p class="text-gray-500 text-sm mt-1"><?=e($t['icon'])?> <?=e($t['cat_name'])?> · <code class="font-mono bg-gray-100 px-2 py-0.5 rounded text-xs"><?=e($t['code'])?></code></p>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <?php foreach([
              ['Portfolio','🖼️',$portf.'  items','?p=t-portfolio'],
              ['Candidatures','📋',count($apps).' total','?p=t-apps'],
              ['Statut','✅',$t['approved']?'Approuvé':'En attente','?p=t-edit'],
            ] as [$label,$ic,$val,$href]): ?>
            <a href="<?=$href?>" class="card-lift bg-white border border-gray-200 rounded-2xl p-5 block">
              <div class="text-2xl mb-2"><?=$ic?></div>
              <div class="text-xs text-gray-500 font-medium uppercase tracking-wide"><?=$label?></div>
              <div class="font-bold text-gray-900 mt-0.5"><?=$val?></div>
            </a>
            <?php endforeach ?>
          </div>
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl border border-gray-200 p-5">
              <div class="flex items-center justify-between mb-4"><h3 class="font-bold">Mes Candidatures</h3><a href="?p=t-apps" class="text-xs text-pink-500">Voir tout</a></div>
              <?php if(empty($apps)): ?>
              <div class="text-center py-6 text-gray-400 text-sm">Aucune candidature · <a href="?p=projects" class="text-pink-500">Explorer les projets</a></div>
              <?php else: ?>
              <div class="space-y-3">
                <?php foreach($apps as $a): ?>
                <div class="flex items-center justify-between text-sm gap-2">
                  <span class="truncate text-gray-700 font-medium flex-1"><?=e(mb_substr($a['title'],0,35))?></span>
                  <div class="flex items-center gap-2 flex-shrink-0"><?=status_badge($a['status'])?><span class="text-gray-400 text-xs"><?=ago($a['created_at'])?></span></div>
                </div>
                <?php endforeach ?>
              </div>
              <?php endif ?>
            </div>
            <div class="bg-white rounded-2xl border border-gray-200 p-5">
              <div class="flex items-center justify-between mb-4"><h3 class="font-bold">Notifications</h3>
                <?php if(!empty($notifs)&&unread_notifs($u['id'])>0): ?>
                <form method="POST"><input type="hidden" name="_act" value="mark-read"><input type="hidden" name="back" value="?p=t-dash"><?=csrf_input()?><button type="submit" class="text-xs text-pink-500">Tout lire</button></form>
                <?php endif ?>
              </div>
              <?php if(empty($notifs)): ?>
              <div class="text-center py-6 text-gray-400 text-sm">Aucune notification</div>
              <?php else: ?>
              <div class="space-y-3">
                <?php foreach($notifs as $n): ?>
                <div class="flex items-start gap-2 text-sm <?=$n['is_read']?'':'bg-pink-50 -mx-2 px-2 rounded-lg py-1'?>">
                  <span class="w-1.5 h-1.5 bg-pink-400 rounded-full mt-1.5 flex-shrink-0 <?=$n['is_read']?'opacity-0':''?>"></span>
                  <div class="flex-1 min-w-0"><p class="text-gray-700 text-xs leading-relaxed"><?=e($n['message'])?></p><p class="text-gray-400 text-xs mt-0.5"><?=ago($n['created_at'])?></p></div>
                </div>
                <?php endforeach ?>
              </div>
              <?php endif ?>
            </div>
          </div>
          <div class="mt-6 bg-gradient-to-r from-pink-500 to-pink-600 rounded-2xl p-6 text-white">
            <h3 class="font-bold mb-1">Profil public</h3>
            <p class="text-pink-100 text-sm mb-3">Votre profil est <?=$t['approved']?'<strong>visible</strong> par les clients':'<strong>en attente</strong> d\'approbation'?>. <a href="?p=talent&id=<?=$t['id']?>" class="underline hover:text-white" target="_blank">Voir mon profil →</a></p>
          </div>
        </div>
      </div>
    </div>
<?php }

// ══════════════════════════════════════════════════════
// 17. PAGE: TALENT EDIT PROFILE
// ══════════════════════════════════════════════════════
function page_t_edit(): void {
    $u = require_auth('talent');
    $t = row("SELECT t.*,c.name cat_name FROM talents t JOIN categories c ON t.cat_id=c.id WHERE t.user_id=?",[$u['id']]);
    if (!$t) redirect('?p=t-dash');
    $pg = 'edit'; ?>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <div class="flex gap-8">
        <?php dash_sidebar([
          ['🏠','Dashboard','?p=t-dash','dash'],['👤','Mon Profil','?p=t-edit','edit'],
          ['🖼️','Portfolio','?p=t-portfolio','portfolio'],['📋','Candidatures','?p=t-apps','apps'],
        ],$pg) ?>
        <div class="flex-1 min-w-0">
          <h1 class="text-2xl font-bold mb-6">Modifier mon Profil</h1>
          <div class="bg-white rounded-2xl border border-gray-200 p-7">
            <form method="POST" enctype="multipart/form-data" class="space-y-5">
              <?=csrf_input()?><input type="hidden" name="_act" value="update-talent">
              <div class="flex items-center gap-5 p-4 bg-gray-50 rounded-xl">
                <img src="<?=e($t['photo']?:avatar_url($t['nickname']))?>" class="w-20 h-20 rounded-xl object-cover" alt="">
                <div class="flex-1">
                  <label class="block text-sm font-semibold text-gray-700 mb-2">Photo de profil</label>
                  <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp" class="text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-pink-50 file:text-pink-600 hover:file:bg-pink-100">
                  <p class="text-xs text-gray-400 mt-1">JPG, PNG ou WEBP · max 5MB</p>
                </div>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-semibold text-gray-700 mb-2">Pseudo *</label><input type="text" name="nickname" value="<?=e($t['nickname'])?>" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm"></div>
                <div><label class="block text-sm font-semibold text-gray-700 mb-2">Wilaya *</label>
                  <select name="province" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm bg-white">
                    <?php foreach(provinces() as $pp): ?><option value="<?=e($pp)?>" <?=$t['province']===$pp?'selected':''?>><?=e($pp)?></option><?php endforeach ?>
                  </select>
                </div>
              </div>
              <div><label class="block text-sm font-semibold text-gray-700 mb-2">Bio</label><textarea name="bio" rows="4" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm resize-none" placeholder="Parlez de vous, votre style, vos influences..."><?=e($t['bio'])?></textarea></div>
              <div><label class="block text-sm font-semibold text-gray-700 mb-2">Compétences <span class="font-normal text-gray-400">(séparées par des virgules)</span></label><input type="text" name="skills" value="<?=e($t['skills'])?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm" placeholder="ex: Comédie dramatique, Théâtre, Improvisation"></div>
              <div><label class="block text-sm font-semibold text-gray-700 mb-2">Expérience</label><textarea name="experience" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm resize-none"><?=e($t['experience'])?></textarea></div>
              <div><label class="block text-sm font-semibold text-gray-700 mb-2">Disponibilité</label>
                <select name="availability" class="px-4 py-2.5 border border-gray-300 rounded-xl text-sm bg-white">
                  <?php foreach(['available'=>'✅ Disponible','busy'=>'🟡 Occupé','unavailable'=>'🔴 Indisponible'] as $v=>$l): ?><option value="<?=$v?>" <?=$t['availability']===$v?'selected':''?>><?=$l?></option><?php endforeach ?>
                </select>
              </div>
              <div class="flex justify-end gap-3 pt-2">
                <a href="?p=t-dash" class="px-5 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium">Annuler</a>
                <button type="submit" class="px-7 py-2.5 bg-pink-500 hover:bg-pink-600 text-white rounded-xl text-sm font-bold transition-colors">Sauvegarder</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
<?php }

// ══════════════════════════════════════════════════════
// 18. PAGE: TALENT PORTFOLIO
// ══════════════════════════════════════════════════════
function page_t_portfolio(): void {
    $u = require_auth('talent');
    $t = row("SELECT id,nickname FROM talents WHERE user_id=?",[$u['id']]);
    if (!$t) redirect('?p=t-dash');
    $items = rows("SELECT * FROM portfolio_items WHERE talent_id=? ORDER BY created_at DESC",[$t['id']]);
    $pg = 'portfolio'; ?>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <div class="flex gap-8">
        <?php dash_sidebar([
          ['🏠','Dashboard','?p=t-dash','dash'],['👤','Mon Profil','?p=t-edit','edit'],
          ['🖼️','Portfolio','?p=t-portfolio','portfolio'],['📋','Candidatures','?p=t-apps','apps'],
        ],$pg) ?>
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Mon Portfolio <span class="text-gray-400 font-normal text-lg">(<?=count($items)?>)</span></h1>
            <button onclick="document.getElementById('add-mod').classList.remove('hidden')" class="px-4 py-2 bg-pink-500 hover:bg-pink-600 text-white rounded-xl text-sm font-semibold">+ Ajouter</button>
          </div>
          <?php if(empty($items)): ?>
          <div class="bg-white rounded-2xl border border-dashed border-gray-300 p-16 text-center">
            <div class="text-4xl mb-3">🖼️</div>
            <h3 class="text-lg font-semibold text-gray-700 mb-1">Portfolio vide</h3>
            <p class="text-gray-400 text-sm mb-4">Ajoutez des images ou vidéos pour montrer votre travail</p>
            <button onclick="document.getElementById('add-mod').classList.remove('hidden')" class="px-5 py-2.5 bg-pink-500 text-white rounded-xl text-sm font-semibold">Ajouter un élément</button>
          </div>
          <?php else: ?>
          <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <?php foreach($items as $it): ?>
            <div class="group relative bg-white border border-gray-200 rounded-2xl overflow-hidden">
              <?php if($it['ptype']==='image'): ?>
              <div class="aspect-square bg-gray-100 cursor-zoom-in" onclick="openLightbox('<?=e($it['url'])?>')">
                <img src="<?=e($it['url'])?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" alt="">
              </div>
              <?php else: ?>
              <div class="aspect-square bg-gray-900 flex items-center justify-center">
                <div class="text-3xl">🎬</div>
              </div>
              <?php endif ?>
              <div class="p-3">
                <p class="text-xs font-medium text-gray-700 truncate"><?=e($it['title']?:'Sans titre')?></p>
                <p class="text-xs text-gray-400"><?=e($it['ptype'])?></p>
              </div>
              <form method="POST" class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                <?=csrf_input()?><input type="hidden" name="_act" value="del-portfolio"><input type="hidden" name="item_id" value="<?=$it['id']?>">
                <button type="submit" onclick="return confirm('Supprimer?')" class="w-7 h-7 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center text-xs font-bold shadow-md">×</button>
              </form>
            </div>
            <?php endforeach ?>
          </div>
          <?php endif ?>
        </div>
      </div>
    </div>
    <!-- Add Modal -->
    <div id="add-mod" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-7 relative">
        <button onclick="document.getElementById('add-mod').classList.add('hidden')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        <h3 class="text-xl font-bold mb-5">Ajouter au Portfolio</h3>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
          <?=csrf_input()?><input type="hidden" name="_act" value="add-portfolio">
          <div><label class="block text-sm font-semibold text-gray-700 mb-2">Type</label>
            <select name="ptype" id="ptype-sel" onchange="updateFileAccept()" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm bg-white">
              <option value="image">🖼️ Image</option><option value="video">🎬 Vidéo</option>
            </select>
          </div>
          <div><label class="block text-sm font-semibold text-gray-700 mb-2">Titre</label><input type="text" name="title" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm" placeholder="Titre de l'oeuvre"></div>
          <div><label class="block text-sm font-semibold text-gray-700 mb-2">URL externe <span class="font-normal text-gray-400">(Unsplash, YouTube, etc.)</span></label><input type="url" name="url" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm" placeholder="https://..."></div>
          <div class="text-center text-xs text-gray-400">— ou —</div>
          <div><label class="block text-sm font-semibold text-gray-700 mb-2">Uploader un fichier</label><input type="file" id="pfile" name="file" class="text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-pink-50 file:text-pink-600"></div>
          <button type="submit" class="w-full py-3 bg-pink-500 hover:bg-pink-600 text-white font-bold rounded-xl">Ajouter</button>
        </form>
      </div>
    </div>
    <script>function updateFileAccept(){const t=document.getElementById('ptype-sel').value;document.getElementById('pfile').accept=t==='video'?'.mp4,.webm':'.jpg,.jpeg,.png,.webp';}</script>
<?php }

// ══════════════════════════════════════════════════════
// 19. PAGE: TALENT APPLICATIONS
// ══════════════════════════════════════════════════════
function page_t_apps(): void {
    $u = require_auth('talent');
    $t = row("SELECT id FROM talents WHERE user_id=?",[$u['id']]);
    if (!$t) redirect('?p=t-dash');
    $apps = rows("SELECT a.*,p.title,p.province,c.name cat_name,c.icon,cl.display_name cl_name FROM applications a JOIN projects p ON a.project_id=p.id LEFT JOIN categories c ON p.cat_id=c.id JOIN clients cl ON p.client_id=cl.id WHERE a.talent_id=? ORDER BY a.created_at DESC",[$t['id']]);
    $pg = 'apps'; ?>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <div class="flex gap-8">
        <?php dash_sidebar([
          ['🏠','Dashboard','?p=t-dash','dash'],['👤','Mon Profil','?p=t-edit','edit'],
          ['🖼️','Portfolio','?p=t-portfolio','portfolio'],['📋','Candidatures','?p=t-apps','apps'],
        ],$pg) ?>
        <div class="flex-1 min-w-0">
          <h1 class="text-2xl font-bold mb-6">Mes Candidatures <span class="text-gray-400 font-normal text-lg">(<?=count($apps)?>)</span></h1>
          <?php if(empty($apps)): ?>
          <div class="bg-white rounded-2xl border border-gray-200 p-16 text-center text-gray-400">
            <div class="text-4xl mb-3">📋</div><p class="font-medium text-gray-600 mb-1">Aucune candidature</p>
            <a href="?p=projects" class="text-pink-500 text-sm hover:text-pink-600">Parcourir les projets →</a>
          </div>
          <?php else: ?>
          <div class="space-y-3">
            <?php foreach($apps as $a): ?>
            <div class="bg-white border border-gray-200 rounded-2xl p-5">
              <div class="flex items-start justify-between gap-3">
                <div class="flex-1">
                  <div class="flex items-center gap-2 flex-wrap mb-1">
                    <h3 class="font-bold text-gray-900"><?=e($a['title'])?></h3>
                    <?=status_badge($a['status'])?>
                  </div>
                  <div class="flex items-center gap-3 text-xs text-gray-500 mb-2">
                    <span><?=e($a['icon']??'🎬')?> <?=e($a['cat_name']??'')?></span>
                    <span>🏢 <?=e($a['cl_name'])?></span>
                    <?php if($a['province']): ?><span>📍 <?=e($a['province'])?></span><?php endif ?>
                  </div>
                  <?php if($a['message']): ?><p class="text-xs text-gray-400 italic">"<?=e(mb_substr($a['message'],0,100))?>"</p><?php endif ?>
                </div>
                <div class="text-xs text-gray-400 flex-shrink-0"><?=ago($a['created_at'])?></div>
              </div>
            </div>
            <?php endforeach ?>
          </div>
          <?php endif ?>
        </div>
      </div>
    </div>
<?php }

// ══════════════════════════════════════════════════════
// 20. PAGE: CLIENT DASHBOARD
// ══════════════════════════════════════════════════════
function page_c_dash(): void {
    $u  = require_auth('client');
    $c  = row("SELECT * FROM clients WHERE user_id=?",[$u['id']]);
    if (!$c) { echo "<div class='p-8 text-gray-500 text-center'>Profil client introuvable.</div>"; return; }
    $projs = (int)q("SELECT COUNT(*) FROM projects WHERE client_id=?",[$c['id']])->fetchColumn();
    $reqs  = (int)q("SELECT COUNT(*) FROM requests WHERE client_id=? AND status='pending'",[$c['id']])->fetchColumn();
    $apps  = rows("SELECT a.*,p.title,t.nickname,t.code FROM applications a JOIN projects p ON a.project_id=p.id JOIN talents t ON a.talent_id=t.id WHERE p.client_id=? ORDER BY a.created_at DESC LIMIT 5",[$c['id']]);
    $notifs= rows("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 5",[$u['id']]);
    $pg = 'dash'; ?>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <div class="flex gap-8">
        <?php dash_sidebar([
          ['🏠','Dashboard','?p=c-dash','dash'],
          ['📁','Mes Projets','?p=c-projects','projects'],
          ['+ Publier','?p=c-post-project','post'],
          ['📩','Mes Demandes','?p=c-requests','requests'],
          ['👥','Candidatures','?p=c-apps','apps'],
          ['🎭','Talents','?p=talents','talents'],
        ],$pg) ?>
        <div class="flex-1 min-w-0">
          <div class="mb-6"><h1 class="text-2xl font-bold">Bonjour, <?=e($c['display_name'])?> 👋</h1><p class="text-gray-500 text-sm mt-1"><?=e($c['ctype'])?> · <?=e($c['province'])?></p></div>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <?php foreach([
              ['Projets','📁',$projs.' publiés','?p=c-projects'],
              ['Demandes','📩',$reqs.' en attente','?p=c-requests'],
              ['Candidatures','👥',count($apps).' reçues','?p=c-apps'],
            ] as [$l,$ic,$v,$hr]): ?>
            <a href="<?=$hr?>" class="card-lift bg-white border border-gray-200 rounded-2xl p-5 block"><div class="text-2xl mb-2"><?=$ic?></div><div class="text-xs text-gray-500 font-medium uppercase tracking-wide"><?=$l?></div><div class="font-bold text-gray-900 mt-0.5"><?=$v?></div></a>
            <?php endforeach ?>
          </div>
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl border border-gray-200 p-5">
              <div class="flex items-center justify-between mb-4"><h3 class="font-bold">Dernières Candidatures</h3><a href="?p=c-apps" class="text-xs text-pink-500">Voir tout</a></div>
              <?php if(empty($apps)): ?><div class="text-center py-6 text-gray-400 text-sm">Aucune candidature reçue</div>
              <?php else: ?><div class="space-y-3"><?php foreach($apps as $a): ?><div class="flex items-center justify-between text-sm gap-2"><div class="flex-1 min-w-0"><p class="font-medium truncate"><?=e($a['nickname'])?> <code class="text-xs text-gray-400"><?=e($a['code'])?></code></p><p class="text-xs text-gray-400 truncate"><?=e($a['title'])?></p></div><?=status_badge($a['status'])?></div><?php endforeach ?></div><?php endif ?>
            </div>
            <div class="bg-white rounded-2xl border border-gray-200 p-5">
              <div class="flex items-center justify-between mb-4"><h3 class="font-bold">Notifications</h3></div>
              <?php if(empty($notifs)): ?><div class="text-center py-6 text-gray-400 text-sm">Aucune notification</div>
              <?php else: ?><div class="space-y-3"><?php foreach($notifs as $n): ?><div class="text-xs text-gray-600 leading-relaxed"><?=e($n['message'])?><span class="text-gray-400 ml-1"><?=ago($n['created_at'])?></span></div><?php endforeach ?></div><?php endif ?>
            </div>
          </div>
          <div class="mt-6 flex gap-3 flex-wrap">
            <a href="?p=c-post-project" class="px-5 py-2.5 bg-pink-500 hover:bg-pink-600 text-white rounded-xl text-sm font-bold transition-colors">+ Publier un Projet</a>
            <a href="?p=talents" class="px-5 py-2.5 border border-gray-300 text-gray-700 hover:border-pink-300 rounded-xl text-sm font-medium transition-colors">Explorer les Talents</a>
          </div>
        </div>
      </div>
    </div>
<?php }

// CLIENT: MY PROJECTS
function page_c_projects(): void {
    $u = require_auth('client');
    $c = row("SELECT id FROM clients WHERE user_id=?",[$u['id']]);
    if (!$c) redirect('?p=c-dash');
    $projs = rows("SELECT p.*,c.name cat_name,COUNT(a.id) apps_count FROM projects p LEFT JOIN categories c ON p.cat_id=c.id LEFT JOIN applications a ON a.project_id=p.id WHERE p.client_id=? GROUP BY p.id ORDER BY p.created_at DESC",[$c['id']]);
    $pg='projects'; ?>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <div class="flex gap-8">
        <?php dash_sidebar([
          ['🏠','Dashboard','?p=c-dash','dash'],['📁','Mes Projets','?p=c-projects','projects'],
          ['➕','Publier','?p=c-post-project','post'],['📩','Demandes','?p=c-requests','requests'],['👥','Candidatures','?p=c-apps','apps'],
        ],$pg) ?>
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between mb-6"><h1 class="text-2xl font-bold">Mes Projets</h1><a href="?p=c-post-project" class="px-4 py-2 bg-pink-500 hover:bg-pink-600 text-white rounded-xl text-sm font-bold">+ Nouveau</a></div>
          <?php if(empty($projs)): ?>
          <div class="bg-white rounded-2xl border border-dashed border-gray-300 p-16 text-center"><div class="text-4xl mb-3">📁</div><p class="text-gray-600 font-medium mb-3">Aucun projet publié</p><a href="?p=c-post-project" class="px-5 py-2.5 bg-pink-500 text-white rounded-xl text-sm font-bold">Créer mon premier projet</a></div>
          <?php else: ?>
          <div class="space-y-4">
            <?php foreach($projs as $p): ?>
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
              <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div class="flex-1">
                  <div class="flex items-center gap-2 flex-wrap mb-1"><h3 class="font-bold text-gray-900"><?=e($p['title'])?></h3><?=status_badge($p['status'])?></div>
                  <div class="flex items-center gap-3 text-xs text-gray-500">
                    <?php if($p['cat_name']): ?><span>🎬 <?=e($p['cat_name'])?></span><?php endif ?>
                    <?php if($p['province']): ?><span>📍 <?=e($p['province'])?></span><?php endif ?>
                    <span>👥 <?=$p['apps_count']?> candidature(s)</span>
                    <span>🕒 <?=ago($p['created_at'])?></span>
                  </div>
                </div>
                <div class="flex gap-2 flex-shrink-0">
                  <a href="?p=c-post-project&id=<?=$p['id']?>" class="px-3 py-1.5 border border-gray-200 text-gray-700 hover:border-pink-300 rounded-lg text-xs font-medium">Modifier</a>
                  <?php if($p['status']==='open'): ?>
                  <form method="POST"><input type="hidden" name="_act" value="close-project"><input type="hidden" name="project_id" value="<?=$p['id']?>"><?=csrf_input()?><button type="submit" onclick="return confirm('Fermer ce projet?')" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-xs font-medium">Fermer</button></form>
                  <?php endif ?>
                </div>
              </div>
            </div>
            <?php endforeach ?>
          </div>
          <?php endif ?>
        </div>
      </div>
    </div>
<?php }

// CLIENT: POST PROJECT
function page_c_post_project(): void {
    $u = require_auth('client');
    $id = (int)($_GET['id']??0);
    $proj = $id ? row("SELECT p.* FROM projects p JOIN clients c ON p.client_id=c.id WHERE p.id=? AND c.user_id=?",[$id,$u['id']]) : null;
    $cats = rows("SELECT * FROM categories");
    $pg = 'post'; ?>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <div class="flex gap-8">
        <?php dash_sidebar([
          ['🏠','Dashboard','?p=c-dash','dash'],['📁','Projets','?p=c-projects','projects'],
          ['➕','Publier','?p=c-post-project','post'],['📩','Demandes','?p=c-requests','requests'],
        ],$pg) ?>
        <div class="flex-1 min-w-0">
          <h1 class="text-2xl font-bold mb-6"><?=$proj?'Modifier le Projet':'Publier un Nouveau Projet'?></h1>
          <div class="bg-white rounded-2xl border border-gray-200 p-7">
            <form method="POST" class="space-y-5">
              <?=csrf_input()?><input type="hidden" name="_act" value="post-project"><?php if($proj): ?><input type="hidden" name="project_id" value="<?=$proj['id']?>"><?php endif ?>
              <div><label class="block text-sm font-semibold text-gray-700 mb-2">Titre du projet *</label><input type="text" name="title" required value="<?=e($proj['title']??'')?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm" placeholder="ex: Long-métrage dramatique 2025"></div>
              <div><label class="block text-sm font-semibold text-gray-700 mb-2">Description *</label><textarea name="description" rows="5" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm resize-none" placeholder="Décrivez votre projet, le profil recherché, les conditions..."><?=e($proj['description']??'')?></textarea></div>
              <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-semibold text-gray-700 mb-2">Catégorie recherchée</label>
                  <select name="cat_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm bg-white">
                    <option value="">— Sélectionner —</option>
                    <?php foreach($cats as $c): ?><option value="<?=$c['id']?>" <?=($proj['cat_id']??0)==$c['id']?'selected':''?>><?=e($c['icon'])?> <?=e($c['name'])?></option><?php endforeach ?>
                  </select>
                </div>
                <div><label class="block text-sm font-semibold text-gray-700 mb-2">Wilaya</label>
                  <select name="province" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm bg-white">
                    <option value="">— Toutes —</option>
                    <?php foreach(provinces() as $pp): ?><option value="<?=e($pp)?>" <?=($proj['province']??'')===$pp?'selected':''?>><?=e($pp)?></option><?php endforeach ?>
                  </select>
                </div>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-semibold text-gray-700 mb-2">Budget estimé</label><input type="text" name="budget" value="<?=e($proj['budget']??'')?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm" placeholder="ex: 200 000 – 500 000 DZD"></div>
                <div><label class="block text-sm font-semibold text-gray-700 mb-2">Date limite</label><input type="date" name="deadline" value="<?=e($proj['deadline']??'')?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm"></div>
              </div>
              <div><label class="block text-sm font-semibold text-gray-700 mb-2">Statut</label>
                <select name="status" class="px-4 py-2.5 border border-gray-300 rounded-xl text-sm bg-white">
                  <option value="open" <?=($proj['status']??'open')==='open'?'selected':''?>>✅ Ouvert — visible publiquement</option>
                  <option value="draft" <?=($proj['status']??'')==='draft'?'selected':''?>>📝 Brouillon — non visible</option>
                </select>
              </div>
              <div class="flex justify-end gap-3 pt-2">
                <a href="?p=c-projects" class="px-5 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium">Annuler</a>
                <button type="submit" class="px-7 py-2.5 bg-pink-500 hover:bg-pink-600 text-white rounded-xl text-sm font-bold"><?=$proj?'Mettre à jour':'Publier le Projet'?></button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
<?php }

// CLIENT: MY REQUESTS
function page_c_requests(): void {
    $u = require_auth('client');
    $c = row("SELECT id FROM clients WHERE user_id=?",[$u['id']]);
    if (!$c) redirect('?p=c-dash');
    $reqs = rows("SELECT r.*,t.code,t.nickname,t.photo,cat.name cat_name,cat.icon FROM requests r JOIN talents t ON r.talent_id=t.id JOIN categories cat ON t.cat_id=cat.id WHERE r.client_id=? ORDER BY r.created_at DESC",[$c['id']]);
    $pg='requests'; ?>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <div class="flex gap-8">
        <?php dash_sidebar([
          ['🏠','Dashboard','?p=c-dash','dash'],['📁','Projets','?p=c-projects','projects'],
          ['➕','Publier','?p=c-post-project','post'],['📩','Demandes','?p=c-requests','requests'],['👥','Candidatures','?p=c-apps','apps'],
        ],$pg) ?>
        <div class="flex-1 min-w-0">
          <h1 class="text-2xl font-bold mb-6">Mes Demandes de Contact <span class="text-gray-400 font-normal text-lg">(<?=count($reqs)?>)</span></h1>
          <div class="bg-pink-50 border border-pink-100 rounded-2xl p-4 mb-6 text-sm text-pink-700">
            <strong>🔒 Système Mayase:</strong> Vos demandes sont traitées par notre équipe. Les coordonnées des talents vous sont transmises uniquement après accord mutuel.
          </div>
          <?php if(empty($reqs)): ?>
          <div class="bg-white rounded-2xl border border-gray-200 p-16 text-center text-gray-400"><div class="text-4xl mb-3">📩</div><p class="font-medium text-gray-600 mb-3">Aucune demande envoyée</p><a href="?p=talents" class="text-pink-500 text-sm">Explorer les talents →</a></div>
          <?php else: ?>
          <div class="space-y-4">
            <?php foreach($reqs as $r): ?>
            <div class="bg-white border border-gray-200 rounded-2xl p-5">
              <div class="flex items-center gap-4">
                <img src="<?=e($r['photo']?:avatar_url($r['nickname']))?>" class="w-12 h-12 rounded-xl object-cover" alt="">
                <div class="flex-1">
                  <div class="flex items-center gap-2 flex-wrap"><span class="font-bold"><?=e($r['nickname'])?></span><code class="text-xs text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded"><?=e($r['code'])?></code><?=status_badge($r['status'])?></div>
                  <p class="text-xs text-gray-500"><?=e($r['icon'])?> <?=e($r['cat_name'])?> · <?=ago($r['created_at'])?></p>
                  <?php if($r['message']): ?><p class="text-xs text-gray-400 mt-1 italic">"<?=e(mb_substr($r['message'],0,100))?>"</p><?php endif ?>
                  <?php if($r['admin_note']): ?><p class="text-xs text-blue-600 mt-1 bg-blue-50 px-2 py-1 rounded-lg">💬 Équipe Mayase: <?=e($r['admin_note'])?></p><?php endif ?>
                </div>
                <a href="?p=talent&id=<?=$r['talent_id']?>" class="px-3 py-1.5 border border-gray-200 text-gray-600 hover:border-pink-300 rounded-lg text-xs font-medium flex-shrink-0">Voir profil</a>
              </div>
            </div>
            <?php endforeach ?>
          </div>
          <?php endif ?>
        </div>
      </div>
    </div>
<?php }

// CLIENT: APPLICATIONS ON PROJECTS
function page_c_apps(): void {
    $u = require_auth('client');
    $c = row("SELECT id FROM clients WHERE user_id=?",[$u['id']]);
    if (!$c) redirect('?p=c-dash');
    $apps = rows("SELECT a.*,p.title,t.nickname,t.code,t.photo,t.province,cat.name cat_name,cat.icon FROM applications a JOIN projects p ON a.project_id=p.id JOIN talents t ON a.talent_id=t.id JOIN categories cat ON t.cat_id=cat.id WHERE p.client_id=? ORDER BY a.created_at DESC",[$c['id']]);
    $pg='apps'; ?>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <div class="flex gap-8">
        <?php dash_sidebar([
          ['🏠','Dashboard','?p=c-dash','dash'],['📁','Projets','?p=c-projects','projects'],
          ['➕','Publier','?p=c-post-project','post'],['📩','Demandes','?p=c-requests','requests'],['👥','Candidatures','?p=c-apps','apps'],
        ],$pg) ?>
        <div class="flex-1 min-w-0">
          <h1 class="text-2xl font-bold mb-6">Candidatures Reçues <span class="text-gray-400 font-normal text-lg">(<?=count($apps)?>)</span></h1>
          <?php if(empty($apps)): ?>
          <div class="bg-white rounded-2xl border border-gray-200 p-16 text-center text-gray-400"><div class="text-4xl mb-3">👥</div><p class="font-medium text-gray-600">Aucune candidature reçue</p></div>
          <?php else: ?>
          <div class="space-y-3">
            <?php foreach($apps as $a): ?>
            <div class="bg-white border border-gray-200 rounded-2xl p-5">
              <div class="flex items-center gap-4">
                <img src="<?=e($a['photo']?:avatar_url($a['nickname']))?>" class="w-12 h-12 rounded-xl object-cover" alt="">
                <div class="flex-1">
                  <div class="flex items-center gap-2 flex-wrap">
                    <a href="?p=talent&id=<?=$a['talent_id']?>" class="font-bold text-gray-900 hover:text-pink-600"><?=e($a['nickname'])?></a>
                    <code class="text-xs text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded"><?=e($a['code'])?></code>
                    <?=status_badge($a['status'])?>
                  </div>
                  <p class="text-xs text-gray-500"><?=e($a['icon'])?> <?=e($a['cat_name'])?> · 📍 <?=e($a['province'])?> · Projet: <em><?=e($a['title'])?></em></p>
                  <?php if($a['message']): ?><p class="text-xs text-gray-400 mt-1 italic">"<?=e(mb_substr($a['message'],0,120))?>"</p><?php endif ?>
                </div>
                <div class="text-xs text-gray-400 flex-shrink-0"><?=ago($a['created_at'])?></div>
              </div>
            </div>
            <?php endforeach ?>
          </div>
          <?php endif ?>
        </div>
      </div>
    </div>
<?php }

// ══════════════════════════════════════════════════════
// 21. PAGE: ADMIN DASHBOARD
// ══════════════════════════════════════════════════════
function admin_sidebar(string $cur): void {
    dash_sidebar([
        ['📊','Dashboard','?p=admin','admin'],
        ['🎭','Talents','?p=admin-talents','admin-talents'],
        ['👥','Utilisateurs','?p=admin-users','admin-users'],
        ['📁','Projets','?p=admin-projects','admin-projects'],
        ['📩','Demandes','?p=admin-requests','admin-requests'],
        ['📋','Candidatures','?p=admin-apps','admin-apps'],
    ],$cur);
}

function page_admin(): void {
    require_auth('admin');
    $s = [
        'talents'  => (int)q("SELECT COUNT(*) FROM talents")->fetchColumn(),
        'approved' => (int)q("SELECT COUNT(*) FROM talents WHERE approved=1")->fetchColumn(),
        'clients'  => (int)q("SELECT COUNT(*) FROM clients")->fetchColumn(),
        'projects' => (int)q("SELECT COUNT(*) FROM projects")->fetchColumn(),
        'open'     => (int)q("SELECT COUNT(*) FROM projects WHERE status='open'")->fetchColumn(),
        'reqs'     => (int)q("SELECT COUNT(*) FROM requests WHERE status='pending'")->fetchColumn(),
        'apps'     => (int)q("SELECT COUNT(*) FROM applications WHERE status='pending'")->fetchColumn(),
        'users'    => (int)q("SELECT COUNT(*) FROM users")->fetchColumn(),
    ];
    $recent_reqs = rows("SELECT r.*,t.code,t.nickname,cl.display_name cl_name FROM requests r JOIN talents t ON r.talent_id=t.id JOIN clients cl ON r.client_id=cl.id ORDER BY r.created_at DESC LIMIT 5");
    $recent_apps = rows("SELECT a.*,p.title,t.nickname,t.code FROM applications a JOIN projects p ON a.project_id=p.id JOIN talents t ON a.talent_id=t.id ORDER BY a.created_at DESC LIMIT 5"); ?>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <div class="flex gap-8">
        <?php admin_sidebar('admin') ?>
        <div class="flex-1 min-w-0">
          <h1 class="text-2xl font-bold mb-6">Dashboard Admin</h1>
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <?php foreach([
              ['Talents','🎭',$s['talents'],'dont '.$s['approved'].' approuvés'],
              ['Clients','🎬',$s['clients'],'inscrits'],
              ['Projets','📁',$s['projects'],$s['open'].' ouverts'],
              ['Demandes','📩',$s['reqs'],'en attente'],
            ] as [$l,$ic,$n,$sub]): ?>
            <div class="bg-white border border-gray-200 rounded-2xl p-5">
              <div class="text-2xl mb-3"><?=$ic?></div>
              <div class="text-3xl font-black text-gray-900"><?=$n?></div>
              <div class="text-xs text-gray-500 mt-1"><?=$l?> · <?=$sub?></div>
            </div>
            <?php endforeach ?>
          </div>
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl border border-gray-200 p-5">
              <div class="flex items-center justify-between mb-4"><h3 class="font-bold">Dernières Demandes</h3><a href="?p=admin-requests" class="text-xs text-pink-500">Gérer</a></div>
              <?php if(empty($recent_reqs)): ?><p class="text-center text-gray-400 text-sm py-4">Aucune demande</p>
              <?php else: ?><div class="space-y-3"><?php foreach($recent_reqs as $r): ?><div class="flex items-center justify-between text-sm gap-2"><div class="flex-1 min-w-0"><p class="font-medium truncate"><?=e($r['cl_name'])?> → <?=e($r['code'])?></p><p class="text-xs text-gray-400"><?=ago($r['created_at'])?></p></div><?=status_badge($r['status'])?></div><?php endforeach ?></div><?php endif ?>
            </div>
            <div class="bg-white rounded-2xl border border-gray-200 p-5">
              <div class="flex items-center justify-between mb-4"><h3 class="font-bold">Dernières Candidatures</h3><a href="?p=admin-apps" class="text-xs text-pink-500">Gérer</a></div>
              <?php if(empty($recent_apps)): ?><p class="text-center text-gray-400 text-sm py-4">Aucune candidature</p>
              <?php else: ?><div class="space-y-3"><?php foreach($recent_apps as $a): ?><div class="flex items-center justify-between text-sm gap-2"><div class="flex-1 min-w-0"><p class="font-medium truncate"><?=e($a['nickname'])?> → <?=e(mb_substr($a['title'],0,30))?></p></div><?=status_badge($a['status'])?></div><?php endforeach ?></div><?php endif ?>
            </div>
          </div>
        </div>
      </div>
    </div>
<?php }

// ADMIN: MANAGE TALENTS
function page_admin_talents(): void {
    require_auth('admin');
    $talents = rows("SELECT t.*,c.name cat_name,c.icon,u.email,u.phone,u.real_name,u.status ustat FROM talents t JOIN categories c ON t.cat_id=c.id JOIN users u ON t.user_id=u.id ORDER BY t.created_at DESC"); ?>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <div class="flex gap-8">
        <?php admin_sidebar('admin-talents') ?>
        <div class="flex-1 min-w-0">
          <h1 class="text-2xl font-bold mb-6">Gérer les Talents <span class="text-gray-400 font-normal text-lg">(<?=count($talents)?>)</span></h1>
          <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                  <tr class="text-xs text-gray-500 font-semibold uppercase tracking-wide">
                    <th class="text-left px-4 py-3">Talent</th><th class="text-left px-4 py-3">Catégorie</th>
                    <th class="text-left px-4 py-3 hidden lg:table-cell">Contact</th>
                    <th class="text-left px-4 py-3">Statut</th><th class="text-right px-4 py-3">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <?php foreach($talents as $t): ?>
                  <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                      <div class="flex items-center gap-3">
                        <img src="<?=e($t['photo']?:avatar_url($t['nickname']))?>" class="w-9 h-9 rounded-lg object-cover" alt="">
                        <div><p class="font-semibold text-gray-900"><?=e($t['nickname'])?></p><code class="text-xs text-gray-400 font-mono"><?=e($t['code'])?></code></div>
                      </div>
                    </td>
                    <td class="px-4 py-3 text-gray-600 text-xs"><?=e($t['icon'])?> <?=e($t['cat_name'])?><br><span class="text-gray-400">📍 <?=e($t['province'])?></span></td>
                    <td class="px-4 py-3 hidden lg:table-cell text-xs text-gray-500">
                      <p class="font-medium text-gray-700"><?=e($t['real_name'])?></p>
                      <p><?=e($t['email'])?></p><p><?=e($t['phone'])?></p>
                    </td>
                    <td class="px-4 py-3">
                      <?=$t['approved']?'<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Approuvé</span>':'<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">En attente</span>'?>
                      <br><?=avail_badge($t['availability'])?>
                    </td>
                    <td class="px-4 py-3 text-right">
                      <form method="POST" class="inline-flex gap-1">
                        <?=csrf_input()?><input type="hidden" name="_act" value="approve-talent"><input type="hidden" name="talent_id" value="<?=$t['id']?>">
                        <?php if(!$t['approved']): ?>
                        <button name="talent_action" value="approve" class="px-2 py-1 bg-green-100 hover:bg-green-200 text-green-700 rounded-lg text-xs font-semibold">Approuver</button>
                        <?php else: ?>
                        <button name="talent_action" value="suspend" onclick="return confirm('Suspendre?')" class="px-2 py-1 bg-yellow-100 hover:bg-yellow-200 text-yellow-700 rounded-lg text-xs font-semibold">Suspendre</button>
                        <?php endif ?>
                      </form>
                      <a href="?p=talent&id=<?=$t['id']?>" class="px-2 py-1 border border-gray-200 hover:border-pink-300 text-gray-600 rounded-lg text-xs ml-1">Voir</a>
                    </td>
                  </tr>
                  <?php endforeach ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
<?php }

// ADMIN: MANAGE USERS
function page_admin_users(): void {
    require_auth('admin');
    $users = rows("SELECT u.*,CASE u.role WHEN 'talent' THEN t.code WHEN 'client' THEN c.display_name ELSE 'Admin' END extra FROM users u LEFT JOIN talents t ON t.user_id=u.id LEFT JOIN clients c ON c.user_id=u.id ORDER BY u.created_at DESC"); ?>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <div class="flex gap-8">
        <?php admin_sidebar('admin-users') ?>
        <div class="flex-1 min-w-0">
          <h1 class="text-2xl font-bold mb-6">Utilisateurs <span class="text-gray-400 font-normal text-lg">(<?=count($users)?>)</span></h1>
          <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200"><tr class="text-xs text-gray-500 font-semibold uppercase tracking-wide"><th class="text-left px-4 py-3">Utilisateur</th><th class="text-left px-4 py-3">Rôle</th><th class="text-left px-4 py-3 hidden md:table-cell">Téléphone</th><th class="text-left px-4 py-3">Statut</th><th class="text-right px-4 py-3">Actions</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                  <?php foreach($users as $u): ?>
                  <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3"><p class="font-semibold"><?=e($u['real_name'])?></p><p class="text-xs text-gray-400"><?=e($u['email'])?></p><?php if($u['extra']&&$u['extra']!=='Admin'): ?><code class="text-xs text-pink-500"><?=e($u['extra'])?></code><?php endif ?></td>
                    <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium <?=['admin'=>'bg-purple-100 text-purple-700','talent'=>'bg-blue-100 text-blue-700','client'=>'bg-green-100 text-green-700'][$u['role']]??''?>"><?=e($u['role'])?></span></td>
                    <td class="px-4 py-3 text-xs text-gray-500 hidden md:table-cell"><?=e($u['phone'])?></td>
                    <td class="px-4 py-3"><?=status_badge($u['status'])?></td>
                    <td class="px-4 py-3 text-right">
                      <?php if($u['id']>1): ?>
                      <form method="POST" class="inline-flex gap-1">
                        <?=csrf_input()?><input type="hidden" name="_act" value="upd-user-status"><input type="hidden" name="user_id" value="<?=$u['id']?>">
                        <?php if($u['status']==='active'): ?>
                        <button name="user_status" value="suspended" onclick="return confirm('Suspendre?')" class="px-2 py-1 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg text-xs">Suspendre</button>
                        <?php else: ?>
                        <button name="user_status" value="active" class="px-2 py-1 bg-green-50 hover:bg-green-100 text-green-600 rounded-lg text-xs">Activer</button>
                        <?php endif ?>
                      </form>
                      <?php endif ?>
                    </td>
                  </tr>
                  <?php endforeach ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
<?php }

// ADMIN: MANAGE PROJECTS
function page_admin_projects(): void {
    require_auth('admin');
    $projs = rows("SELECT p.*,c.name cat_name,c.icon,cl.display_name cl_name,COUNT(a.id) apps FROM projects p LEFT JOIN categories c ON p.cat_id=c.id JOIN clients cl ON p.client_id=cl.id LEFT JOIN applications a ON a.project_id=p.id GROUP BY p.id ORDER BY p.created_at DESC"); ?>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <div class="flex gap-8">
        <?php admin_sidebar('admin-projects') ?>
        <div class="flex-1 min-w-0">
          <h1 class="text-2xl font-bold mb-6">Projets <span class="text-gray-400 font-normal text-lg">(<?=count($projs)?>)</span></h1>
          <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200"><tr class="text-xs text-gray-500 font-semibold uppercase tracking-wide"><th class="text-left px-4 py-3">Projet</th><th class="text-left px-4 py-3">Client</th><th class="text-left px-4 py-3 hidden lg:table-cell">Budget</th><th class="text-left px-4 py-3">Candidatures</th><th class="text-left px-4 py-3">Statut</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                  <?php foreach($projs as $p): ?>
                  <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3"><p class="font-semibold text-gray-900"><?=e(mb_substr($p['title'],0,50))?></p><p class="text-xs text-gray-400"><?=e($p['icon']??'')?> <?=e($p['cat_name']??'—')?> · <?=e($p['province'])?> · <?=ago($p['created_at'])?></p></td>
                    <td class="px-4 py-3 text-xs text-gray-600"><?=e($p['cl_name'])?></td>
                    <td class="px-4 py-3 text-xs text-gray-500 hidden lg:table-cell"><?=e($p['budget'])?:'-'?></td>
                    <td class="px-4 py-3 text-center"><span class="font-bold text-gray-900"><?=$p['apps']?></span></td>
                    <td class="px-4 py-3"><?=status_badge($p['status'])?></td>
                  </tr>
                  <?php endforeach ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
<?php }

// ADMIN: MANAGE REQUESTS
function page_admin_requests(): void {
    require_auth('admin');
    $reqs = rows("SELECT r.*,t.code,t.nickname,t.photo,cat.name cat_name,cat.icon,cl.display_name cl_name,u.email cl_email,u.phone cl_phone,u2.phone t_phone,u2.email t_email,u2.real_name t_real FROM requests r JOIN talents t ON r.talent_id=t.id JOIN categories cat ON t.cat_id=cat.id JOIN clients cl ON r.client_id=cl.id JOIN users u ON cl.user_id=u.id JOIN users u2 ON t.user_id=u2.id ORDER BY FIELD(r.status,'pending','in_review','responded','closed'),r.created_at DESC"); ?>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <div class="flex gap-8">
        <?php admin_sidebar('admin-requests') ?>
        <div class="flex-1 min-w-0">
          <h1 class="text-2xl font-bold mb-6">Demandes de Contact <span class="text-gray-400 font-normal text-lg">(<?=count($reqs)?>)</span></h1>
          <div class="bg-yellow-50 border border-yellow-100 rounded-2xl p-4 mb-6 text-sm text-yellow-800">
            <strong>⚡ Rôle du médiateur:</strong> Vous seul avez accès aux coordonnées. Traitez chaque demande, puis mettez en relation les parties si approprié.
          </div>
          <div class="space-y-4">
            <?php foreach($reqs as $r): ?>
            <div class="bg-white border border-gray-200 rounded-2xl p-5">
              <div class="flex flex-col lg:flex-row lg:items-start gap-4">
                <div class="flex-1">
                  <div class="flex items-center gap-2 flex-wrap mb-2">
                    <span class="font-bold text-gray-900">Demande #<?=$r['id']?></span>
                    <?=status_badge($r['status'])?>
                    <span class="text-xs text-gray-400"><?=ago($r['created_at'])?></span>
                  </div>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs mb-3">
                    <div class="bg-blue-50 rounded-xl p-3">
                      <p class="font-semibold text-blue-800 mb-1">📤 CLIENT</p>
                      <p class="font-medium"><?=e($r['cl_name'])?></p>
                      <p class="text-blue-600"><?=e($r['cl_email'])?></p>
                      <p class="text-blue-600"><?=e($r['cl_phone'])?></p>
                    </div>
                    <div class="bg-pink-50 rounded-xl p-3">
                      <p class="font-semibold text-pink-800 mb-1">🎭 TALENT</p>
                      <p class="font-medium"><?=e($r['t_real'])?> <code class="text-pink-500"><?=e($r['code'])?></code></p>
                      <p class="text-pink-600"><?=e($r['t_email'])?></p>
                      <p class="text-pink-600"><?=e($r['t_phone'])?></p>
                    </div>
                  </div>
                  <?php if($r['message']): ?><p class="text-xs text-gray-500 italic bg-gray-50 rounded-lg p-2 mb-2">"<?=e($r['message'])?>"</p><?php endif ?>
                </div>
                <div class="lg:w-72 flex-shrink-0">
                  <form method="POST" class="space-y-2">
                    <?=csrf_input()?><input type="hidden" name="_act" value="upd-req"><input type="hidden" name="req_id" value="<?=$r['id']?>">
                    <select name="req_status" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs bg-white">
                      <?php foreach(['pending'=>'En attente','in_review'=>'En traitement','responded'=>'Répondu','closed'=>'Fermé'] as $v=>$l): ?><option value="<?=$v?>" <?=$r['status']===$v?'selected':''?>><?=$l?></option><?php endforeach ?>
                    </select>
                    <textarea name="admin_note" rows="2" placeholder="Note interne ou message pour le client..." class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs resize-none"><?=e($r['admin_note'])?></textarea>
                    <button type="submit" class="w-full py-2 bg-pink-500 hover:bg-pink-600 text-white rounded-xl text-xs font-bold">Mettre à jour</button>
                  </form>
                </div>
              </div>
            </div>
            <?php endforeach ?>
            <?php if(empty($reqs)): ?><div class="text-center py-16 text-gray-400"><div class="text-4xl mb-3">📩</div><p>Aucune demande</p></div><?php endif ?>
          </div>
        </div>
      </div>
    </div>
<?php }

// ADMIN: MANAGE APPLICATIONS
function page_admin_apps(): void {
    require_auth('admin');
    $apps = rows("SELECT a.*,p.title,t.nickname,t.code,t.province,cat.name cat_name,cat.icon,cl.display_name cl_name FROM applications a JOIN projects p ON a.project_id=p.id JOIN talents t ON a.talent_id=t.id JOIN categories cat ON t.cat_id=cat.id JOIN clients cl ON p.client_id=cl.id ORDER BY a.created_at DESC"); ?>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <div class="flex gap-8">
        <?php admin_sidebar('admin-apps') ?>
        <div class="flex-1 min-w-0">
          <h1 class="text-2xl font-bold mb-6">Candidatures <span class="text-gray-400 font-normal text-lg">(<?=count($apps)?>)</span></h1>
          <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200"><tr class="text-xs text-gray-500 font-semibold uppercase tracking-wide"><th class="text-left px-4 py-3">Talent</th><th class="text-left px-4 py-3">Projet</th><th class="text-left px-4 py-3 hidden md:table-cell">Client</th><th class="text-left px-4 py-3">Statut</th><th class="text-right px-4 py-3">Action</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                  <?php foreach($apps as $a): ?>
                  <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3"><p class="font-semibold"><?=e($a['nickname'])?></p><code class="text-xs text-gray-400"><?=e($a['code'])?></code><br><span class="text-xs text-gray-400"><?=e($a['icon']??'')?> <?=e($a['cat_name'])?></span></td>
                    <td class="px-4 py-3 text-xs"><p class="font-medium text-gray-800"><?=e(mb_substr($a['title'],0,40))?></p></td>
                    <td class="px-4 py-3 text-xs text-gray-500 hidden md:table-cell"><?=e($a['cl_name'])?></td>
                    <td class="px-4 py-3"><?=status_badge($a['status'])?></td>
                    <td class="px-4 py-3 text-right">
                      <form method="POST" class="inline-flex gap-1">
                        <?=csrf_input()?><input type="hidden" name="_act" value="upd-app"><input type="hidden" name="app_id" value="<?=$a['id']?>">
                        <select name="app_status" class="px-2 py-1 border border-gray-200 rounded-lg text-xs bg-white">
                          <?php foreach(['pending'=>'En attente','accepted'=>'Accepter','rejected'=>'Refuser'] as $v=>$l): ?><option value="<?=$v?>" <?=$a['status']===$v?'selected':''?>><?=$l?></option><?php endforeach ?>
                        </select>
                        <button type="submit" class="px-2 py-1 bg-pink-500 hover:bg-pink-600 text-white rounded-lg text-xs font-bold">✓</button>
                      </form>
                    </td>
                  </tr>
                  <?php endforeach ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
<?php }

// ══════════════════════════════════════════════════════
// 22. MAIN OUTPUT
// ══════════════════════════════════════════════════════
// Initialize DB (will auto-install if needed)
db();

// Determine if page needs full layout or standalone
$standalone = in_array($p, ['login','register']);

start_html($title);
if (!$standalone) render_nav();
render_flash();

match($p) {
    'home'            => page_home(),
    'talents'         => page_talents(),
    'talent'          => page_talent(),
    'projects'        => page_projects(),
    'project'         => page_project(),
    'login'           => page_login(),
    'register'        => page_register(),
    't-dash'          => page_t_dash(),
    't-edit'          => page_t_edit(),
    't-portfolio'     => page_t_portfolio(),
    't-apps'          => page_t_apps(),
    'c-dash'          => page_c_dash(),
    'c-projects'      => page_c_projects(),
    'c-post-project'  => page_c_post_project(),
    'c-requests'      => page_c_requests(),
    'c-apps'          => page_c_apps(),
    'admin'           => page_admin(),
    'admin-talents'   => page_admin_talents(),
    'admin-users'     => page_admin_users(),
    'admin-projects'  => page_admin_projects(),
    'admin-requests'  => page_admin_requests(),
    'admin-apps'      => page_admin_apps(),
    default           => (function(){ ?>
        <div class="max-w-lg mx-auto text-center py-32 px-4">
          <div class="text-6xl mb-4">404</div>
          <h1 class="text-2xl font-bold mb-2">Page introuvable</h1>
          <a href="?p=home" class="text-pink-500 hover:text-pink-600">← Retour à l'accueil</a>
        </div>
    <?php })(),
};

if (!$standalone) render_footer();