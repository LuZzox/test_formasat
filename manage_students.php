<?php
session_start();
require_once 'connexion.php';
$pdo = getConnexion();

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php?error=admin_required');
    exit();
}

$message = '';
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("Token CSRF invalide.");
    }

    if (isset($_POST['action']) && $_POST['action'] === 'add_student') {
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['mot_de_passe'] ?? '';
        $confirm_password = $_POST['confirm_mot_de_passe'] ?? '';
        $promo_id = (int)($_POST['promo_id'] ?? 0);

        if (empty($nom) || empty($prenom) || empty($email) || empty($password) || $promo_id <= 0) {
            $errors[] = "Tous les champs sont obligatoires.";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email invalide.";
        }
        if ($password !== $confirm_password) {
            $errors[] = "Les mots de passe ne correspondent pas.";
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM etudiant WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Cet email est déjà utilisé.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO etudiant (nom, prenom, email, mot_de_passe, promo_id) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$nom, $prenom, $email, $hashed, $promo_id])) {
                    $message = "Étudiant ajouté avec succès !";
                    $_POST = [];
                } else {
                    $errors[] = "Erreur lors de l'ajout.";
                }
            }
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete_student') {
        $student_id = (int)($_POST['student_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM etudiant WHERE id = ?");
        if ($stmt->execute([$student_id])) {
            $message = "Étudiant supprimé.";
        }
    }
}

$promos = $pdo->query("SELECT id, libelle FROM promo WHERE actif = 1 ORDER BY libelle")->fetchAll();
$students = $pdo->query("SELECT e.*, p.libelle as promo_libelle FROM etudiant e JOIN promo p ON e.promo_id = p.id ORDER BY e.nom, e.prenom")->fetchAll();

if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gérer les Étudiants - FormaSat</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { display: block; padding: 20px; }
        .container { max-width: 1200px; margin: 20px auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
        .form-add { background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 30px; border: 1px solid #eee; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f0f2f5; }
        .btn-gen { background: #6c757d; font-size: 0.8rem; width: auto; margin: 0; padding: 5px 10px; }
        .btn-del { background: #dc3545; width: auto; margin: 0; padding: 5px 10px; font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="nav-admin">
        <a href="admin_dashboard.php">📊 Stats Modules</a>
        <a href="add_evaluation.php">📝 Ajouter Évaluation</a>
        <a href="add_admin.php">🔑 Ajouter Admin</a>
        <a href="manage_promos.php">🏫 Gérer Promos</a>
        <a href="manage_profs.php">👨‍🏫 Gérer Profs</a>
        <a href="manage_students.php" class="active">🎓 Gérer Étudiants</a>
        <a href="manage_modules.php">📚 Gérer Modules</a>
        <a href="logout.php" class="btn-logout">Déconnexion</a>
    </div>

    <div class="container">
        <h2>Ajouter un Étudiant</h2>
        <?php if ($message) echo "<p class='success'>$message</p>"; ?>
        <?php if ($errors) foreach($errors as $e) echo "<p class='error'>$e</p>"; ?>

        <form method="POST" class="form-add">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" value="add_student">
            <div class="form-grid">
                <div class="form-group"><label>Nom</label><input type="text" name="nom" required></div>
                <div class="form-group"><label>Prénom</label><input type="text" name="prenom" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
                <div class="form-group">
                    <label>Promotion</label>
                    <select name="promo_id" required>
                        <option value="">-- Choisir --</option>
                        <?php foreach($promos as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['libelle']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Mdp</label><input type="password" id="pass" name="mot_de_passe" required></div>
                <div class="form-group"><label>Confirmer</label><input type="password" id="conf" name="confirm_mot_de_passe" required></div>
            </div>
            <button type="button" class="btn-gen" onclick="generate()">Générer mot de passe</button>
            <button type="submit">Créer l'étudiant</button>
        </form>

        <h2>Liste des Étudiants</h2>
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Promotion</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($students as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['nom']) ?></td>
                    <td><?= htmlspecialchars($s['prenom']) ?></td>
                    <td><?= htmlspecialchars($s['email']) ?></td>
                    <td><?= htmlspecialchars($s['promo_libelle']) ?></td>
                    <td>
                        <a href="edit_student.php?id=<?= $s['id'] ?>" class="btn-gen" style="background:#ffc107; color:black; display:inline-block">Modifier</a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer ?')">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="delete_student">
                            <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn-del">Supprimer</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>
    function generate() {
        const p = Math.random().toString(36).slice(-10);
        document.getElementById('pass').value = p;
        document.getElementById('conf').value = p;
        document.getElementById('pass').type = 'text';
        document.getElementById('conf').type = 'text';
    }
    </script>
</body>
</html>