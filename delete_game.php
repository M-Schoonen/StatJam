<?php

include 'db.php';
session_start();

$user_id = $_SESSION['user_id'];

$game_id = $_POST['game_id'];

$sql = "
DELETE FROM games
WHERE id = '$game_id'
AND user_id = '$user_id'
";

$conn->query($sql);

echo "success";