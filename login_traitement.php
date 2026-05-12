<?php
session_start();
include 'db_connect.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Requête pour vérifier les informations d'identification de l'utilisateur
    $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        // L'utilisateur est authentifié avec succès
        $_SESSION['username'] = $username; // Stocker le nom d'utilisateur dans la session
        header("Location: dashboard.php"); // Rediriger vers le tableau de bord ou une page protégée
        exit();
    } else {
        // Échec de l'authentification
        echo "Nom d'utilisateur ou mot de passe incorrect.";
    }
}