<?php
require_once 'connexion.php';
$pdo = getConnexion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Récupérer et nettoyer les données (trim, htmlspecialchars)
    $nom      = htmlspecialchars(trim($_POST['nom'] ?? ''));
    $prenom   = htmlspecialchars(trim($_POST['prenom'] ?? ''));
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['mot_de_passe'] ?? '';
    $confirm  = $_POST['confirmation'] ?? '';

    $errors = [];

    // 2. Valider : champs vides ? email valide ? mots de passe identiques ?
    if (empty($nom) || empty($prenom) || empty($email) || empty($password) || empty($confirm)) {
        $errors[] = "Tous les champs sont obligatoires.";
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide.";
    }

    if ($password !== $confirm) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    // 3. Vérifier que l'email n'est pas déjà utilisé (requête SELECT)
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM etudiant WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "Cette adresse email est déjà utilisée par un autre compte.";
        }
    }

    // Si aucune erreur, on procède à l'inscription
    if (empty($errors)) {
        // 4. Hacher le mot de passe (password_hash)
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // 5. Insérer avec une requête préparée PDO
        $stmt = $pdo->prepare("INSERT INTO etudiant (nom, prenom, email, mot_de_passe) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nom, $prenom, $email, $hashedPassword]);

        // 6. Rediriger vers la page de connexion avec un message de succès
        header('Location: login.php?registration=success');
        exit;
    } else {
        // Affichage des erreurs (idéalement à gérer avec des variables de session)
        foreach ($errors as $error) echo "<p style='color:red;'>$error</p>";
        echo '<a href="inscription.php">Retour au formulaire</a>';
    }
}