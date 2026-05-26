<?php
session_start();
require_once 'connexion.php';
$pdo = getConnexion();

// 1. Sécurité : Vérifier si l'utilisateur est connecté en tant qu'administrateur
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php?error=admin_required');
    exit();
}

$prof_id = (int)($_GET['id'] ?? 0);
if ($prof_id === 0) {
    header('Location: manage_profs.php');
    exit();
}

$message = '';
$errors = [];
$prof = null;

// Fetch professor data
$stmt = $pdo->prepare("SELECT id, nom, prenom, email, specialite, actif FROM prof WHERE id = ?");
$stmt->execute([$prof_id]);
$prof = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$prof) {
    header('Location: manage_profs.php');
    exit();
}

// Handle POST request for updating professor
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("Token CSRF invalide.");
    }

    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $specialite = trim($_POST['specialite'] ?? '');
    $actif = isset($_POST['actif']) ? 1 : 0;
    $password = $_POST['mot_de_passe'] ?? '';
    $confirm_password = $_POST['confirm_mot_de_passe'] ?? '';

    if (empty($nom) || empty($prenom) || empty($email)) {
        $errors[] = "Les champs Nom, Prénom et Email sont obligatoires.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide.";
    }
    if (!empty($password) && $password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    if (empty($errors)) {
        // Check if email already exists for another professor
        $stmt = $pdo->prepare("SELECT id FROM prof WHERE email = ? AND id != ?");
        $stmt->execute([$email, $prof_id]);
        if ($stmt->fetch()) {
            $errors[] = "Cet email est déjà utilisé par un autre professeur.";
        } else {
            $sql = "UPDATE prof SET nom = ?, prenom = ?, email = ?, specialite = ?, actif = ?";
            $params = [$nom, $prenom, $email, $specialite, $actif];

            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql .= ", mot_de_passe = ?";
                $params[] = $hashed_password;
            }
            $sql .= " WHERE id = ?";
            $params[] = $prof_id;

            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($params)) {
                $message = "Professeur mis à jour avec succès !";
                // Refresh prof data after update
                $stmt = $pdo->prepare("SELECT id, nom, prenom, email, specialite, actif FROM prof WHERE id = ?");
                $stmt->execute([$prof_id]);
                $prof = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $errors[] = "Erreur lors de la mise à jour du professeur.";
            }
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Professeur - FormaSat</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            display: block;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        }
        .section-title {
            font-size: 1.8rem;
            color: #1c1e21;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            text-align: left;
        }
        .form-group.checkbox {
            display: flex;
            align-items: center;
            margin-top: 15px;
        }
        .form-group.checkbox label {
            margin-bottom: 0;
            margin-left: 10px;
        }
        .form-group.checkbox input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
        .form-group.password-fields {
            margin-top: 20px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .form-group.password-fields p {
            text-align: left;
            font-size: 0.85rem;
            color: #606770;
            margin-bottom: 15px;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
        }
        .nav-admin {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="nav-admin">
        <a href="admin_dashboard.php">📊 Stats Modules</a>
        <a href="add_evaluation.php">📝 Ajouter Évaluation</a>
        <a href="add_admin.php">🔑 Ajouter Admin</a>
        <a href="manage_promos.php">🏫 Gérer Promos</a>
        <a href="manage_profs.php" class="active">👨‍🏫 Gérer Profs</a>
        <a href="manage_students.php">🎓 Gérer Étudiants</a>
        <a href="manage_modules.php">📚 Gérer Modules</a>
        <a href="logout.php" class="btn-logout">Déconnexion</a>
    </div>

    <div class="container">
        <h2 class="section-title">Modifier le Professeur : <?= htmlspecialchars($prof['prenom'] . ' ' . $prof['nom']) ?></h2>

        <?php if (!empty($message)): ?>
            <div class="message-container success">
                <p><?= htmlspecialchars($message) ?></p>
            </div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="message-container error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            
            <div class="form-group">
                <label for="nom">Nom :</label>
                <input type="text" id="nom" name="nom" required value="<?= htmlspecialchars($prof['nom']) ?>">
            </div>
            <div class="form-group">
                <label for="prenom">Prénom :</label>
                <input type="text" id="prenom" name="prenom" required value="<?= htmlspecialchars($prof['prenom']) ?>">
            </div>
            <div class="form-group">
                <label for="email">Email :</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($prof['email']) ?>">
            </div>
            <div class="form-group">
                <label for="specialite">Spécialité :</label>
                <input type="text" id="specialite" name="specialite" value="<?= htmlspecialchars($prof['specialite'] ?? '') ?>">
            </div>
            <div class="form-group checkbox">
                <input type="checkbox" id="actif" name="actif" <?= $prof['actif'] ? 'checked' : '' ?>>
                <label for="actif">Actif</label>
            </div>

            <div class="form-group password-fields">
                <p>Laisser les champs de mot de passe vides si vous ne souhaitez pas le modifier.</p>
                <label for="mot_de_passe">Nouveau mot de passe :</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe">
            </div>
            <div class="form-group">
                <label for="confirm_mot_de_passe">Confirmer le nouveau mot de passe :</label>
                <input type="password" id="confirm_mot_de_passe" name="confirm_mot_de_passe">
            </div>
            
            <button type="submit">Enregistrer les modifications</button>
            <div style="margin-top: 15px;">
                <button type="button" onclick="generatePassword()" style="background-color: #6c757d; width: auto; margin-top: 0; font-size: 0.9rem; padding: 0.5rem 1rem;">Générer un mot de passe</button>
            </div>
        </form>
        <p class="back-link"><a href="manage_profs.php">Retour à la gestion des professeurs</a></p>
    </div>

    <script>
        function generatePassword() {
            const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*-_";
            let password = "";
            for (let i = 0; i < 12; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('mot_de_passe').type = 'text';
            document.getElementById('confirm_mot_de_passe').type = 'text';
            document.getElementById('mot_de_passe').value = password;
            document.getElementById('confirm_mot_de_passe').value = password;
        }
    </script>
</body>
</html>