<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['ok' => false]);
  exit;
}

include 'db.php';

$data    = json_decode(file_get_contents('php://input'), true);
$game_id = (int)($data['game_id'] ?? 0);
$opp     = (int)($data['opp_score'] ?? 0);
$finish  = !empty($data['finish']);
$players = $data['players'] ?? [];

if (!$game_id) {
  echo json_encode(['ok' => false, 'msg' => 'Missing game id']);
  exit;
}

$user_id = $_SESSION['user_id'];

$check = $conn->query("SELECT id FROM games WHERE id = '$game_id' AND user_id = '$user_id'");
if ($check->num_rows === 0) {
  echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
  exit;
}

// Update game: opp score + optional status + finished_at
if ($finish) {
  $conn->query("
    UPDATE games
    SET opp_score = '$opp',
        status = 'finished',
        finished_at = NOW()
    WHERE id = '$game_id'
  ");
} else {
  $conn->query("UPDATE games SET opp_score = '$opp', status = 'in_progress' WHERE id = '$game_id'");
}

// Upsert player stats
foreach ($players as $player_id => $s) {
  $pid  = (int)$player_id;
  $pts  = (int)($s['pts']  ?? 0);
  $fgm  = (int)($s['fgm']  ?? 0);
  $fga  = (int)($s['fga']  ?? 0);
  $tpm  = (int)($s['3pm']  ?? 0);
  $tpa  = (int)($s['3pa']  ?? 0);
  $ftm  = (int)($s['ftm']  ?? 0);
  $fta  = (int)($s['fta']  ?? 0);
  $reb  = (int)($s['reb']  ?? 0);
  $ast  = (int)($s['ast']  ?? 0);
  $stl  = (int)($s['stl']  ?? 0);
  $blk  = (int)($s['blk']  ?? 0);
  $tov  = (int)($s['tov']  ?? 0);
  $foul = (int)($s['foul'] ?? 0);

  $conn->query("
    INSERT INTO game_stats
      (game_id, player_id, pts, fgm, fga, three_pm, three_pa, ftm, fta, reb, ast, stl, blk, tov, fouls)
    VALUES
      ('$game_id','$pid','$pts','$fgm','$fga','$tpm','$tpa','$ftm','$fta','$reb','$ast','$stl','$blk','$tov','$foul')
    ON DUPLICATE KEY UPDATE
      pts='$pts', fgm='$fgm', fga='$fga', three_pm='$tpm', three_pa='$tpa',
      ftm='$ftm', fta='$fta', reb='$reb', ast='$ast', stl='$stl',
      blk='$blk', tov='$tov', fouls='$foul'
  ");
}

echo json_encode(['ok' => true]);