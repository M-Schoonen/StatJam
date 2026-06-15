<?php
include 'db.php';
session_start();

$user_id = $_SESSION['user_id'];

$team_id = $_POST['team_id'];
$opponent = $_POST['opponent'];
$date = $_POST['game_date'];

/* 1. team info ophalen (voor gender + age + logo) */
$teamSql = "SELECT * FROM teams WHERE id = '$team_id' AND user_id = '$user_id'";
$teamResult = $conn->query($teamSql);
$team = $teamResult->fetch_assoc();

if (!$team) {
  http_response_code(403);
  echo json_encode(["error" => "Invalid team"]);
  exit;
}

/* 2. game opslaan */
$sql = "INSERT INTO games (user_id, team_id, opponent, game_date)
        VALUES ('$user_id', '$team_id', '$opponent', '$date')";

$conn->query($sql);

$game_id = $conn->insert_id;

/* 3. response terug naar frontend */
echo json_encode([
  "id"           => $game_id,
  "team_id"      => $team_id,
  "team_name"    => $team['team_name'],  // ← add this
  "opponent"     => $opponent,
  "game_date"    => $date,
  "gender"       => $team['gender'],
  "age_category" => $team['age_category'],
  "logo"         => $team['logo']
]);
exit;