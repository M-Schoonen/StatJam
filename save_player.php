<?php
include 'db.php';

$first_name = $_POST['first_name'];
$last_name = $_POST['last_name'];
$jersey_number = $_POST['jersey_number'];
$position = $_POST['position'];
$team_id = $_POST['team_id'];

$sql = "INSERT INTO players (team_id, first_name, last_name, jersey_number, position)
        VALUES ('$team_id', '$first_name', '$last_name', '$jersey_number', '$position')";

$conn->query($sql);

header("Location: index.php");
exit;