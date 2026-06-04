<?php

include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    session_start();

    $user_id = $_SESSION['user_id'];

    $team_name = $_POST['team_name'];
    $gender = $_POST['gender'];
    $age_category = $_POST['age_category'];

    // LOGO UPLOAD

    $logo_name = $_FILES['logo']['name'];
    $tmp_name = $_FILES['logo']['tmp_name'];

    // unieke bestandsnaam maken
    $new_logo_name = time() . "_" . $logo_name;

    // map waar logo opgeslagen wordt
    $upload_path = "uploads/" . $new_logo_name;

    // upload verplaatsen
    move_uploaded_file($tmp_name, $upload_path);

    // OPSLAAN IN DATABASE

    $sql = "INSERT INTO teams 
    (team_name, gender, age_category, logo)
    VALUES
    ('$team_name', '$gender', '$age_category', '$upload_path')";

    if ($conn->query($sql) === TRUE) {

        header("Location: index.php");
        exit();
    } else {

        echo "Error: " . $conn->error;
    }
}
