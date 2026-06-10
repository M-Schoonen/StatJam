<?php
include 'db.php';
session_start();

$user_id = $_SESSION['user_id'];

$sql = "
SELECT COUNT(p.id) AS total_players
FROM players p
JOIN teams t ON p.team_id = t.id
WHERE t.user_id = '$user_id'
";

$result = $conn->query($sql);
$row = $result->fetch_assoc();

echo json_encode([
  "total_players" => $row['total_players']
]);