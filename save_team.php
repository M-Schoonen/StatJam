<?php

include 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id = $_SESSION['user_id'];

    $team_name = $_POST['team_name'];
    $gender = $_POST['gender'];
    $age_category = $_POST['age_category'];

    // Logo upload
    $logo_name = $_FILES['logo']['name'];
    $tmp_name = $_FILES['logo']['tmp_name'];

    $new_logo_name = time() . "_" . $logo_name;
    $upload_path = "uploads/" . $new_logo_name;

    move_uploaded_file($tmp_name, $upload_path);

    $sql = "INSERT INTO teams 
    (user_id, team_name, gender, age_category, logo)
    VALUES 
    ('$user_id', '$team_name', '$gender', '$age_category', '$upload_path')";

    if ($conn->query($sql) === TRUE) {
        echo "success";
        exit();
    } else {
        echo "Error: " . $conn->error;
        exit();
    }
}