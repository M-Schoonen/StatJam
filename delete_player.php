<?php
session_start();
if (!isset($_SESSION['user_id'])) { http_response_code(403); exit; }
include 'db.php';

$player_id = (int)$_POST['player_id'];
$user_id   = $_SESSION['user_id'];

// Make sure the player belongs to a team owned by this user
$conn->query("
  DELETE p FROM players p
  JOIN teams t ON p.team_id = t.id
  WHERE p.id = '$player_id' AND t.user_id = '$user_id'
");

echo 'ok';