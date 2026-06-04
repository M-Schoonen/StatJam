<?php

include 'db.php';

$team_id = $_POST['team_id'];
$player_name = $_POST['player_name'];
$jersey_number = $_POST['jersey_number'];
$position = $_POST['position'];

$sql = "INSERT INTO players
(team_id, player_name, jersey_number, position)
VALUES
('$team_id', '$player_name', '$jersey_number', '$position')";

if ($conn->query($sql) === TRUE) {

    header("Location: index.php?team=$team_id#page-teams");
    exit();

} else {

    echo "Error: " . $conn->error;

}
?>