<?php
session_start();
require_once 'connexion.php';
$pdo = getConnexion();

// 1. Sécurité : Vérifier si l'utilisateur est connecté en tant qu'administrateur
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php?error=admin_required');
    exit();
}

$message = '';
$errors = [];

// Handle POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("Token CSRF invalide.");
    }

    // Add Professor
    if (isset($_POST['action']) && $_POST['action'] === 'add_prof') {
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['mot_de_passe'] ?? '';
        $confirm_password = $_POST['confirm_mot_de_passe'] ?? '';
        $specialite = trim($_POST['specialite'] ?? '');

        if (empty($nom) || empty($prenom) || empty($email) || empty($password) || empty($confirm_password)) {
            $errors[] = "Tous les champs obligatoires doivent être remplis.";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'adresse email n'est pas valide.";
        }
        if ($password !== $confirm_password) {
            $errors[] = "Les mots de passe ne correspondent pas.";
        }

        if (empty($errors)) {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM prof WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Cet email est déjà utilisé par un autre professeur.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO prof (nom, prenom, email, mot_de_passe, specialite) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$nom, $prenom, $email, $hashed_password, $specialite])) {
                    $message = "Professeur ajouté avec succès !";
                    // Clear form data after successful submission
                    $_POST = []; 
                } else {
                    $errors[] = "Erreur lors de l'ajout du professeur.";
                }
            }
        }
    }

    // Delete Professor
    if (isset($_POST['action']) && $_POST['action'] === 'delete_prof') {
        $prof_id = (int)($_POST['prof_id'] ?? 0);
        if ($prof_id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM prof WHERE id = ?");
                if ($stmt->execute([$prof_id])) {
                    $message = "Professeur supprimé avec succès.";
                } else {
                    $errors[] = "Erreur lors de la suppression du professeur.";
                }
            } catch (PDOException $e) {
                // Check for foreign key constraint violation
                if ($e->getCode() == '23000') { // SQLSTATE for Integrity Constraint Violation
                    $errors[] = "Impossible de supprimer ce professeur car il est associé à des modules existants. Veuillez d'abord réaffecter ou supprimer ses modules.";
                } else {
                    $errors[] = "Erreur de base de données lors de la suppression: " . $e->getMessage();
                }
            }
        } else {
            $errors[] = "ID de professeur invalide pour la suppression.";
        }
    }
}

// Fetch all professors for display
$stmtProfs = $pdo->query("SELECT id, nom, prenom, email, specialite, actif FROM prof ORDER BY nom, prenom");
$professors = $stmtProfs->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Gérer les Professeurs - FormaSat</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Specific styles for this page, overriding or extending styles.css */
        body {
            display: block; /* Override flex from styles.css for full page content */
            padding: 20px;
        }
        .container {
            max-width: 1200px;
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
        .form-add-prof {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .form-add-prof .form-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }
        .form-add-prof .form-group > div {
            flex: 1;
            min-width: 200px;
        }
        .form-add-prof button {
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f0f2f5;
            font-weight: 600;
            color: #4b4f56;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .action-buttons a, .action-buttons button {
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            cursor: pointer;
            border: none;
            transition: background-color 0.2s;
            width: auto; /* Override global button width */
            margin-top: 0; /* Override global button margin */
        }
        .action-buttons a.edit {
            background-color: #ffc107;
            color: #333;
        }
        .action-buttons a.edit:hover {
            background-color: #e0a800;
        }
        .action-buttons button.delete {
            background-color: #dc3545;
            color: white;
        }
        .action-buttons button.delete:hover {
            background-color: #c82333;
        }
        .message-container {
            margin-bottom: 20px;
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
        <h2 class="section-title">Ajouter un nouveau Professeur</h2>

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

        <form method="POST" class="form-add-prof">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="action" value="add_prof">
            <div class="form-group">
                <div>
                    <label for="nom">Nom :</label>
                    <input type="text" id="nom" name="nom" required value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
                </div>
                <div>
                    <label for="prenom">Prénom :</label>
                    <input type="text" id="prenom" name="prenom" required value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <div>
                    <label for="email">Email :</label>
                    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div>
                    <label for="specialite">Spécialité (optionnel) :</label>
                    <input type="text" id="specialite" name="specialite" value="<?= htmlspecialchars($_POST['specialite'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <div>
                    <label for="mot_de_passe">Mot de passe :</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" required>
                </div>
                <div>
                    <label for="confirm_mot_de_passe">Confirmer le mot de passe :</label>
                    <input type="password" id="confirm_mot_de_passe" name="confirm_mot_de_passe" required>
                </div>
            </div>
            <div style="margin-bottom: 15px;">
                <button type="button" onclick="generatePassword()" style="background-color: #6c757d; width: auto; margin-top: 0; font-size: 0.9rem; padding: 0.5rem 1rem;">Générer un mot de passe</button>
            </div>
            <button type="submit">Ajouter le Professeur</button>
        </form>

        <h2 class="section-title">Liste des Professeurs</h2>
        <?php if (empty($professors)): ?>
            <p>Aucun professeur n'est enregistré pour le moment.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Email</th>
                        <th>Spécialité</th>
                        <th>Actif</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($professors as $prof): ?>
                        <tr>
                            <td><?= htmlspecialchars($prof['id']) ?></td>
                            <td><?= htmlspecialchars($prof['nom']) ?></td>
                            <td><?= htmlspecialchars($prof['prenom']) ?></td>
                            <td><?= htmlspecialchars($prof['email']) ?></td>
                            <td><?= htmlspecialchars($prof['specialite'] ?? 'N/A') ?></td>
                            <td><?= $prof['actif'] ? 'Oui' : 'Non' ?></td>
                            <td class="action-buttons">
                                <a href="edit_prof.php?id=<?= $prof['id'] ?>" class="edit">Modifier</a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                    <input type="hidden" name="action" value="delete_prof">
                                    <input type="hidden" name="prof_id" value="<?= $prof['id'] ?>">
                                    <button type="submit" class="delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce professeur ? Cette action est irréversible et peut échouer si le professeur est associé à des modules.');">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
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