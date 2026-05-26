<?php
session_start();
require_once 'connexion.php';
$pdo = getConnexion();

if (!isset($_SESSION['etudiant_id'])) {
    die("Accès non autorisé.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $etudiant_id = $_SESSION['etudiant_id'];
    $module_id = (int)$_POST['module_id'];
    $note_contenu = (int)$_POST['note_contenu'];
    $note_pedagogie = (int)$_POST['note_pedagogie'];
    $note_support = (int)$_POST['note_support'];
    $note_rythme = (int)$_POST['note_rythme'];
    $note_applicabilite = (int)$_POST['note_applicabilite'];
    $commentaire = htmlspecialchars(trim($_POST['commentaire'] ?? ''));

    // 1. Vérification métier : l'étudiant a-t-il déjà noté ce module ?
    $stmt = $pdo->prepare("SELECT id FROM evaluation WHERE etudiant_id = ? AND module_id = ?");
    $stmt->execute([$etudiant_id, $module_id]);
    
    if ($stmt->fetch()) {
        die("Erreur : Vous avez déjà évalué ce module.");
    }

    // 2. Insertion des données
    try {
        $sql = "INSERT INTO evaluation (
                    etudiant_id, module_id, note_contenu, note_pedagogie, 
                    note_support, note_rythme, note_applicabilite, commentaire
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $etudiant_id, $module_id, $note_contenu, $note_pedagogie,
            $note_support, $note_rythme, $note_applicabilite, $commentaire
        ]);

        // Redirection avec message de succès
        header('Location: dashboard.php?noted=success'); // Redirection vers le dashboard étudiant
        exit();

    } catch (PDOException $e) {
        // 1. Log the true error message safely on the server side
        error_log("Database Insertion Error in evaluation: " . $e->getMessage());
        
        // 2. Display a safe, sanitized message to the user
        die("Une erreur est survenue lors de l'enregistrement. Veuillez réessayer ultérieurement.");
    }
} else {
    header('Location: evaluation.php');
}