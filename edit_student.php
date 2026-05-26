<?php
session_start();
require_once 'connexion.php';
$pdo = getConnexion();

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM etudiant WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();
if (!$student) header('Location: manage_students.php');

$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $promo_id = (int)$_POST['promo_id'];
    $pass = $_POST['mot_de_passe'];

    $sql = "UPDATE etudiant SET nom = ?, prenom = ?, email = ?, promo_id = ?";
    $params = [$nom, $prenom, $email, $promo_id];

    if (!empty($pass)) {
        $sql .= ", mot_de_passe = ?";
        $params[] = password_hash($pass, PASSWORD_DEFAULT);
    }
    $sql .= " WHERE id = ?";
    $params[] = $id;

    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
        $message = "Mis à jour avec succès.";
        $stmt = $pdo->prepare("SELECT * FROM etudiant WHERE id = ?");
        $stmt->execute([$id]);
        $student = $stmt->fetch();
    }
}

$promos = $pdo->query("SELECT id, libelle FROM promo WHERE actif = 1 ORDER BY libelle")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Étudiant - FormaSat</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { display: block; padding: 20px; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1); }
    </style>
</head>
<body>
    <div class="nav-admin">
        <a href="admin_dashboard.php">📊 Stats Modules</a>
        <a href="manage_students.php" class="active">🎓 Retour Liste</a>
    </div>

    <div class="container">
        <h2>Modifier Étudiant</h2>
        <?php if ($message) echo "<p class='success'>$message</p>"; ?>

        <form method="POST">
            <div class="form-group">
                <label>Nom</label>
                <input type="text" name="nom" value="<?= htmlspecialchars($student['nom']) ?>" required>
            </div>
            <div class="form-group">
                <label>Prénom</label>
                <input type="text" name="prenom" value="<?= htmlspecialchars($student['prenom']) ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($student['email']) ?>" required>
            </div>
            <div class="form-group">
                <label>Promotion</label>
                <select name="promo_id" required>
                    <?php foreach($promos as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $p['id'] == $student['promo_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['libelle']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Changer mot de passe (laisser vide pour garder l'actuel)</label>
                <input type="password" id="pass" name="mot_de_passe">
            </div>
            <button type="button" class="btn-gen" onclick="generate()" style="background:#6c757d; font-size:0.8rem; width:auto">Générer</button>
            <button type="submit">Enregistrer</button>
        </form>
        <p><a href="manage_students.php">Retour</a></p>
    </div>
    <script>
    function generate() {
        const p = Math.random().toString(36).slice(-10);
        document.getElementById('pass').value = p;
        document.getElementById('pass').type = 'text';
    }
    </script>
</body>
</html>