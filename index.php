<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

include 'db.php';

$user_id = $_SESSION['user_id'];

if (isset($_GET['ajax']) && $_GET['ajax'] === 'players') {
  header('Content-Type: application/json');

  $team_id = $_GET['team_id'];

  $stmt = $conn->prepare("
    SELECT
      p.*,
      ROUND(AVG(gs.pts), 1) AS ppg,
      ROUND(AVG(gs.reb), 1) AS rpg,
      ROUND(AVG(gs.ast), 1) AS apg
    FROM players p
    LEFT JOIN game_stats gs ON gs.player_id = p.id
    LEFT JOIN games g ON gs.game_id = g.id AND g.status = 'finished'
    WHERE p.team_id = ?
    GROUP BY p.id
    ORDER BY p.jersey_number
  ");
  $stmt->bind_param('i', $team_id);
  $stmt->execute();
  $result = $stmt->get_result();

  $players = [];

  while ($row = $result->fetch_assoc()) {
    // No finished games yet -> AVG() returns NULL, normalize to 0.0
    $row['ppg'] = $row['ppg'] !== null ? (float)$row['ppg'] : 0.0;
    $row['rpg'] = $row['rpg'] !== null ? (float)$row['rpg'] : 0.0;
    $row['apg'] = $row['apg'] !== null ? (float)$row['apg'] : 0.0;
    $players[] = $row;
  }

  echo json_encode($players);
  exit;
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'box_score') {
  header('Content-Type: application/json');

  $game_id = $_GET['game_id'];

  $gameResult = $conn->query("
    SELECT g.*, t.team_name, t.gender, t.age_category, t.logo
    FROM games g
    JOIN teams t ON g.team_id = t.id
    WHERE g.id = '$game_id' AND g.user_id = '$user_id' AND g.status = 'finished'
  ");

  if ($gameResult->num_rows === 0) {
    echo json_encode(['error' => 'Game not found']);
    exit;
  }

  $game = $gameResult->fetch_assoc();

  $statsResult = $conn->query("
    SELECT
      p.first_name, p.last_name, p.jersey_number, p.position,
      gs.pts, gs.reb, gs.ast, gs.stl, gs.blk,
      gs.fgm, gs.fga, gs.three_pm, gs.three_pa,
      gs.ftm, gs.fta, gs.tov, gs.fouls
    FROM game_stats gs
    JOIN players p ON gs.player_id = p.id
    WHERE gs.game_id = '$game_id'
    ORDER BY gs.pts DESC
  ");

  $players = [];
  $totals  = ['pts' => 0, 'reb' => 0, 'ast' => 0, 'stl' => 0, 'blk' => 0, 'fgm' => 0, 'fga' => 0, 'three_pm' => 0, 'three_pa' => 0, 'ftm' => 0, 'fta' => 0, 'tov' => 0, 'fouls' => 0];

  while ($row = $statsResult->fetch_assoc()) {
    $players[] = $row;
    foreach ($totals as $k => $_) $totals[$k] += (int)$row[$k];
  }

  $homeScore = $totals['pts'];
  $oppScore  = (int)($game['opp_score'] ?? 0);

  echo json_encode([
    'game'    => $game,
    'players' => $players,
    'totals'  => $totals,
    'home_score' => $homeScore,
    'opp_score'   => $oppScore,
    'finished_at_formatted' => (new DateTime($game['finished_at']))->format('l, F jS Y'),
  ]);
  exit;
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'stat_leaders') {
  header('Content-Type: application/json');

  $team_filter = isset($_GET['team_id']) && $_GET['team_id'] !== '' ? $_GET['team_id'] : null;

  $teamClause = $team_filter ? "AND t.id = '$team_filter'" : "";

  $statDefs = [
    'ppg'      => ['label' => 'Points per game',        'agg' => 'AVG(gs.pts)',       'round' => 1],
    'rpg'      => ['label' => 'Rebounds per game',       'agg' => 'AVG(gs.reb)',       'round' => 1],
    'apg'      => ['label' => 'Assists per game',        'agg' => 'AVG(gs.ast)',       'round' => 1],
    'spg'      => ['label' => 'Steals per game',         'agg' => 'AVG(gs.stl)',       'round' => 1],
    'bpg'      => ['label' => 'Blocks per game',         'agg' => 'AVG(gs.blk)',       'round' => 1],
    'topg'     => ['label' => 'Turnovers per game',      'agg' => 'AVG(gs.tov)',       'round' => 1],
    'fgm'      => ['label' => 'Field goals made',        'agg' => 'SUM(gs.fgm)',       'round' => 0],
    'fgpct'    => ['label' => 'Field goal percentage',   'agg' => 'SUM(gs.fgm)/SUM(gs.fga)*100', 'round' => 1, 'guard' => 'SUM(gs.fga) > 0'],
    'tpm'      => ['label' => 'Three pointers made',     'agg' => 'SUM(gs.three_pm)',  'round' => 0],
    'tppct'    => ['label' => 'Three point percentage',  'agg' => 'SUM(gs.three_pm)/SUM(gs.three_pa)*100', 'round' => 1, 'guard' => 'SUM(gs.three_pa) > 0'],
    'ftm'      => ['label' => 'Free throws made',        'agg' => 'SUM(gs.ftm)',       'round' => 0],
    'ftpct'    => ['label' => 'Free throw percentage',   'agg' => 'SUM(gs.ftm)/SUM(gs.fta)*100', 'round' => 1, 'guard' => 'SUM(gs.fta) > 0'],
    'fouls'    => ['label' => 'Personal fouls',          'agg' => 'SUM(gs.fouls)',     'round' => 0],
  ];

  $leaders = [];

  foreach ($statDefs as $key => $def) {
    $havingClause = isset($def['guard']) ? "HAVING {$def['guard']}" : "";

    $sql = "
      SELECT
        p.id, p.first_name, p.last_name,
        ROUND({$def['agg']}, {$def['round']}) AS value
      FROM players p
      JOIN teams t ON p.team_id = t.id
      JOIN game_stats gs ON gs.player_id = p.id
      JOIN games g ON gs.game_id = g.id
      WHERE t.user_id = '$user_id'
        AND g.status = 'finished'
        $teamClause
      GROUP BY p.id
      $havingClause
      ORDER BY value DESC
      LIMIT 5
    ";

    $result = $conn->query($sql);
    $rows = [];
    while ($row = $result->fetch_assoc()) {
      $rows[] = [
        'name'  => $row['first_name'] . ' ' . $row['last_name'],
        'value' => $row['value'],
      ];
    }

    $leaders[$key] = ['label' => $def['label'], 'rows' => $rows];
  }

  echo json_encode($leaders);
  exit;
}

$sql = "SELECT * FROM teams WHERE user_id = '$user_id' ORDER BY age_category";
$result = $conn->query($sql);

$sqlTeams = "SELECT * FROM teams WHERE user_id = '$user_id' ORDER BY age_category";
$teamsResult = $conn->query($sqlTeams);

$upcomingGamesResult = $conn->query("
    SELECT COUNT(*) AS total
    FROM games
    WHERE user_id = '$user_id'
    AND (status IS NULL OR status != 'finished')
");

$upcomingGames = $upcomingGamesResult->fetch_assoc()['total'];

$gamesPlayedResult = $conn->query("
    SELECT COUNT(*) AS total
    FROM games
    WHERE user_id = '$user_id'
    AND status = 'finished'
");
$gamesPlayed = $gamesPlayedResult->fetch_assoc()['total'];

$topPerformersResult = $conn->query("
    SELECT
        p.id,
        p.first_name,
        p.last_name,
        p.jersey_number,
        p.position,
        COUNT(gs.game_id)                        AS games_played,
        ROUND(AVG(gs.pts),  1)                   AS ppg,
        ROUND(AVG(gs.reb),  1)                   AS rpg,
        ROUND(AVG(gs.ast),  1)                   AS apg
    FROM players p
    JOIN teams t    ON p.team_id  = t.id
    JOIN game_stats gs ON gs.player_id = p.id
    JOIN games g    ON gs.game_id = g.id
    WHERE t.user_id = '$user_id'
    AND g.status    = 'finished'
    GROUP BY p.id
    HAVING games_played > 0
    ORDER BY ppg DESC
    LIMIT 1
");

$recentGamesResult = $conn->query("
    SELECT g.*, t.team_name, t.gender, t.age_category,
           (SELECT SUM(pts) FROM game_stats WHERE game_id = g.id) AS home_score
    FROM games g
    JOIN teams t ON g.team_id = t.id
    WHERE g.user_id = '$user_id'
    AND g.status = 'finished'
    ORDER BY g.finished_at DESC
    LIMIT 1
");

$winRateResult = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN (SELECT SUM(pts) FROM game_stats WHERE game_id = g.id) > g.opp_score THEN 1 ELSE 0 END) AS wins,
        SUM(CASE WHEN (SELECT SUM(pts) FROM game_stats WHERE game_id = g.id) < g.opp_score THEN 1 ELSE 0 END) AS losses
    FROM games g
    WHERE g.user_id = '$user_id'
    AND g.status = 'finished'
");
$winRateRow = $winRateResult->fetch_assoc();
$totalGames = (int)$winRateRow['total'];
$wins       = (int)$winRateRow['wins'];
$losses     = (int)$winRateRow['losses'];
$winRate    = $totalGames > 0 ? round(($wins / $totalGames) * 100) : 0;

if (isset($_GET['ajax']) && $_GET['ajax'] === 'games') {
  header('Content-Type: application/json');

  $sql = "SELECT g.*, t.team_name, t.gender, t.age_category, t.logo 
          FROM games g
          JOIN teams t ON g.team_id = t.id
          WHERE g.user_id = '$user_id' AND (g.status != 'finished' OR g.status IS NULL)
          ORDER BY g.game_date ASC";

  $result = $conn->query($sql);
  $games = [];

  while ($row = $result->fetch_assoc()) {
    $games[] = $row;
  }

  echo json_encode($games);
  exit;
}

$user_id = $_SESSION['user_id'];

$sqlPlayers = "
SELECT COUNT(p.id) AS total_players
FROM players p
JOIN teams t ON p.team_id = t.id
WHERE t.user_id = '$user_id'
";

$resultPlayers = $conn->query($sqlPlayers);
$rowPlayers = $resultPlayers->fetch_assoc();

$totalPlayers = $rowPlayers['total_players'];

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>StatJam</title>
  <link rel="icon" href="./img/StatJam-Ball-Logo.webp" type="image/webp" />
  <link rel="stylesheet" href="./css/style.css" />
  <link rel="stylesheet" href="./css/tracker.css" />
</head>

<body>

  <!-- TOP BAR -->
  <div class="topbar">
    <div class="topbar-inner">
      <div class="logo-area">
        <img src="./img/StatJam-Logo.webp" alt="StatJam" class="logo-img"
          onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
        <span class="logo-name" style="display:none">StatJam</span>
      </div>
      <div class="topbar-right">
        <span class="user-email"><?= htmlspecialchars($_SESSION['user_email']) ?></span>
        <a href="logout.php" class="btn-logout">Log out</a>
      </div>
    </div>
    <div class="tagline">Track player performance and game statistics for your basketball team</div>

    <!-- NAV -->
    <div class="nav-wrapper" style="padding: 0 0 16px;">
      <nav id="statjam-nav">
        <div id="nav-pill"></div>
        <button class="nav-tab active" data-page="page-dashboard" onclick="setTab(this)">Dashboard</button>
        <button class="nav-tab" data-page="page-teams" onclick="setTab(this)">Teams &amp; Players</button>
        <button class="nav-tab" data-page="page-games" onclick="setTab(this)">Your Games</button>
        <button class="nav-tab" data-page="page-history" onclick="setTab(this)">Game History</button>
        <button class="nav-tab" data-page="page-stat-leaders" onclick="setTab(this)">Stat Leaders</button>
      </nav>
    </div>
  </div>

  <!-- PAGE CONTENT -->
  <div class="content">

    <!-- ═══════════════════════════════════════════════════════
         DASHBOARD TAB
    ════════════════════════════════════════════════════════ -->
    <div class="page active" id="page-dashboard">

      <!-- Stat cards -->
      <div class="stat-cards">
        <div class="stat-card">
          <div class="stat-card-top">
            <span class="stat-card-label">Total Players</span>
            <svg class="stat-card-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <circle cx="9" cy="7" r="4" />
              <path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2" />
              <path d="M16 3.13a4 4 0 0 1 0 7.75" />
              <path d="M21 21v-2a4 4 0 0 0-3-3.85" />
            </svg>
          </div>
          <div class="stat-card-value" id="total-players">
            <?= $totalPlayers ?>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-card-top">
            <span class="stat-card-label">Games Played</span>
            <svg class="stat-card-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <rect x="3" y="4" width="18" height="18" rx="2" />
              <path d="M16 2v4M8 2v4M3 10h18" />
            </svg>
          </div>
          <div class="stat-card-value"><?= $gamesPlayed ?></div>
        </div>

        <div class="stat-card">
          <div class="stat-card-top">
            <span class="stat-card-label">Win Rate</span>
            <svg class="stat-card-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M8 4h8v6a4 4 0 0 1-8 0V4Z" />
              <path d="M8 5H5.5a2.5 2.5 0 0 0 0 5H8" />
              <path d="M16 5h2.5a2.5 2.5 0 0 1 0 5H16" />
              <path d="M12 14v3" />
              <path d="M8.5 21h7" />
              <path d="M9 21c0-2 1-3 3-3s3 1 3 3" />
            </svg>
          </div>
          <div class="stat-card-value large"><?= $winRate ?>%</div>
          <div class="stat-card-sub"><?= $wins ?>W–<?= $losses ?>L</div>
        </div>

        <div class="stat-card">
          <div class="stat-card-top">
            <span class="stat-card-label">Upcoming Games</span>
            <svg class="stat-card-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
              <polyline points="16 7 22 7 22 13" />
            </svg>
          </div>
          <div class="stat-card-value" id="upcoming-games-count">
            <?= $upcomingGames ?>
          </div>
        </div>
      </div>

      <!-- Bottom panels -->
      <div class="panels">

        <!-- Top Performers -->
        <div class="panel">
          <div class="panel-title">Top Performers</div>
          <div class="panel-subtitle">Leading scorers based on completed games</div>

          <?php if ($topPerformersResult->num_rows === 0): ?>
            <div class="empty-state" style="margin-top: 20px; font-size: 13px;">No stats recorded yet.</div>
          <?php else: ?>
            <?php while ($tp = $topPerformersResult->fetch_assoc()): ?>
              <div class="performer-row">
                <div class="performer-left">
                  <span class="performer-num">#<?= $tp['jersey_number'] ?></span>
                  <div>
                    <div class="performer-name"><?= htmlspecialchars($tp['first_name'] . ' ' . $tp['last_name']) ?></div>
                    <div class="performer-pos"><?= $tp['position'] ?></div>
                  </div>
                </div>
                <div class="performer-right">
                  <div class="performer-ppg"><?= $tp['ppg'] ?> PPG</div>
                  <div class="performer-extras"><?= $tp['rpg'] ?> RPG, <?= $tp['apg'] ?> APG</div>
                </div>
              </div>
            <?php endwhile; ?>
          <?php endif; ?>

        </div>

        <!-- Recent Games -->
        <div class="panel">
          <div class="panel-title">Recent Games</div>
          <div class="panel-subtitle">Latest game results and upcoming matches</div>

          <?php if ($recentGamesResult->num_rows === 0): ?>
            <div class="empty-state" style="margin-top: 20px; font-size: 13px;">No finished games yet.</div>
          <?php else: ?>
            <?php while ($rg = $recentGamesResult->fetch_assoc()):
              $home = (int)($rg['home_score'] ?? 0);
              $opp  = (int)($rg['opp_score']  ?? 0);
              $win  = $home > $opp;
              $date = (new DateTime($rg['finished_at']))->format('d-m-Y');
            ?>
              <div class="game-row">
                <div>
                  <div class="game-opponent">VS <?= htmlspecialchars($rg['opponent']) ?></div>
                  <div class="game-date"><?= $date ?> • <?= htmlspecialchars($rg['team_name']) ?> <?= $rg['gender'] . $rg['age_category'] ?></div>
                </div>
                <div class="game-badge">
                  <span class="badge-final" style="background: <?= $win ? '#2e7d32' : '#c62828' ?>">
                    <?= $win ? 'W' : 'L' ?>
                  </span>
                  <span class="game-score"><?= $home ?>-<?= $opp ?></span>
                </div>
              </div>
            <?php endwhile; ?>
          <?php endif; ?>

        </div>

      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         TEAMS & PLAYERS TAB — add your content here
    ════════════════════════════════════════════════════════ -->
    <div class="page" id="page-teams">
      <div class="tab-tagline">
        <p class="tagline-txt" id="page-title">Your teams &amp; players</p>

        <button class="add-btn" id="action-btn" onclick="openOverlay()">
          + Add Team
        </button>
      </div>

      <div id="teams-view">
        <div class="teams-container">

          <?php
          if ($result->num_rows > 0) {
            while ($team = $result->fetch_assoc()) {
          ?>

              <div class="team-card"
                onclick="loadPlayers(<?= $team['id'] ?>, '<?= htmlspecialchars($team['team_name']) ?>', '<?= $team['gender'] ?>', '<?= $team['age_category'] ?>')">

                <img class="team-logo" src="<?= $team['logo'] ?>" alt="logo">

                <div class="team-title">
                  <?= $team['team_name'] . " " . $team['gender'] . $team['age_category'] ?>
                </div>

              </div>

          <?php
            }
          } else {
            echo "<div class='empty-state'>No teams added yet.</div>";
          }
          ?>

        </div>
      </div>

      <div id="players-view" style="display:none;"></div>


      <div class="backdrop" id="backdrop" onclick="closeAllOverlays()"></div>

      <div class="overlay" id="overlay">
        <div id="overlay-container">
          <button class="close-btn" onclick="closeAllOverlays()">
            <svg width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd" clip-rule="evenodd" d="M42.8872 1.22016C44.5143 -0.406816 47.1526 -0.406627 48.7798 1.22016C50.407 2.84734 50.4069 5.48552 48.7798 7.11274L30.8921 24.9995L48.7798 42.8872C50.4069 44.5143 50.4068 47.1525 48.7798 48.7797C47.1526 50.4069 44.5144 50.4069 42.8872 48.7797L24.9995 30.892L7.11279 48.7797C5.48558 50.4069 2.84743 50.4069 1.22022 48.7797C-0.40661 47.1525 -0.406867 44.5142 1.22022 42.8872L19.1069 24.9995L1.22022 7.11274C-0.406582 5.48553 -0.40679 2.84725 1.22022 1.22016C2.84731 -0.406742 5.48561 -0.406601 7.11279 1.22016L24.9995 19.1069L42.8872 1.22016Z" fill="white" />
            </svg>
          </button>

          <form id="team-form" enctype="multipart/form-data">
            <div class="logo-upload">
              <strong>Add your logo here</strong>

              <input type="file" name="logo" required>
            </div>

            <div class="form-row">

              <input
                class="team-name"
                type="text"
                name="team_name"
                placeholder="Team Name"
                required>

              <select name="gender" required>
                <option value="">Gender</option>
                <option value="M">Mens</option>
                <option value="V">Womens</option>
              </select>

              <select name="age_category" required>
                <option value="">Age</option>
                <option value="U10">U10</option>
                <option value="U12">U12</option>
                <option value="U14">U14</option>
                <option value="U16">U16</option>
                <option value="U18">U18</option>
                <option value="U19">U19</option>
                <option value="U20">U20</option>
                <option value="U22">U22</option>
                <option value="SE">SE</option>
              </select>

            </div>

            <button class="save-btn" type="submit">
              Add Team
            </button>

          </form>
        </div>
      </div>



      <div class="backdrop" id="backdrop" onclick="closeAllOverlays()"></div>

      <div class="overlay" id="player-overlay">
        <div id="overlay-container">

          <button class="close-btn" onclick="closeAllOverlays()">
            <svg width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd" clip-rule="evenodd" d="M42.8872 1.22016C44.5143 -0.406816 47.1526 -0.406627 48.7798 1.22016C50.407 2.84734 50.4069 5.48552 48.7798 7.11274L30.8921 24.9995L48.7798 42.8872C50.4069 44.5143 50.4068 47.1525 48.7798 48.7797C47.1526 50.4069 44.5144 50.4069 42.8872 48.7797L24.9995 30.892L7.11279 48.7797C5.48558 50.4069 2.84743 50.4069 1.22022 48.7797C-0.40661 47.1525 -0.406867 44.5142 1.22022 42.8872L19.1069 24.9995L1.22022 7.11274C-0.406582 5.48553 -0.40679 2.84725 1.22022 1.22016C2.84731 -0.406742 5.48561 -0.406601 7.11279 1.22016L24.9995 19.1069L42.8872 1.22016Z" fill="white" />
            </svg>
          </button>

          <form id="player-form">
            <!-- hidden team id -->
            <input type="hidden" name="team_id" id="player-team-id">

            <!-- vaste afbeelding -->
            <div class="player-image">
              <svg width="270" height="235" viewBox="0 0 270 235" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M186.429 64.2854C186.429 35.8823 163.403 12.8557 135 12.8557C106.596 12.8557 83.5698 35.8823 83.5698 64.2854C83.5698 92.6885 106.596 115.715 135 115.715V128.571C99.4957 128.571 70.7141 99.7893 70.7141 64.2854C70.7141 28.7816 99.4957 0 135 0C170.503 0 199.285 28.7816 199.285 64.2854C199.285 99.7893 170.503 128.571 135 128.571V115.715C163.403 115.715 186.429 92.6885 186.429 64.2854Z" fill="white" />
                <path d="M135 160.715C190.606 160.715 224.247 176.378 244.109 192.715C253.992 200.843 260.326 209.026 264.216 215.273C266.16 218.394 267.493 221.031 268.356 222.936C268.788 223.888 269.104 224.659 269.319 225.216C269.426 225.495 269.51 225.721 269.57 225.89L269.667 226.169L269.678 226.21C269.659 226.222 269.319 226.337 263.571 228.212L269.681 226.221C270.782 229.596 268.941 233.225 265.566 234.326C262.193 235.427 258.568 233.585 257.464 230.214L257.46 230.218C257.458 230.21 257.457 230.201 257.453 230.19C257.431 230.128 257.386 230.01 257.321 229.841C257.191 229.504 256.973 228.961 256.648 228.244C255.997 226.808 254.925 224.674 253.303 222.071C250.062 216.867 244.621 209.783 235.941 202.644C218.678 188.445 188.036 173.571 135 173.571C81.9636 173.571 51.3216 188.445 34.0588 202.644C25.3792 209.783 19.9381 216.867 16.697 222.071C15.0755 224.674 14.0031 226.808 13.3523 228.244C13.0271 228.961 12.8093 229.504 12.6792 229.841C12.6141 230.01 12.5688 230.128 12.5466 230.19C12.5427 230.201 12.5388 230.21 12.5362 230.218C12.5363 230.217 12.5378 230.216 12.5327 230.214C11.4282 233.584 7.80678 235.427 4.43423 234.326C1.05952 233.225 -0.781952 229.596 0.318736 226.221L6.4292 228.212C0.680487 226.337 0.340817 226.222 0.322223 226.21L0.332687 226.169L0.430342 225.89C0.490125 225.721 0.574234 225.495 0.681457 225.216C0.896354 224.659 1.21226 223.888 1.64406 222.936C2.50748 221.031 3.84002 218.394 5.78397 215.273C9.67449 209.026 16.008 200.843 25.8906 192.715C45.7527 176.378 79.3944 160.715 135 160.715ZM12.5362 230.218V230.225C12.5348 230.229 12.5336 230.233 12.5327 230.235C12.5313 230.24 12.5292 230.242 12.5292 230.242L12.5362 230.218Z" fill="white" />
              </svg>

            </div>

            <div class="form-row">

              <input
                type="text"
                name="first_name"
                placeholder="First Name"
                required>

              <input
                type="text"
                name="last_name"
                placeholder="Last Name"
                required>

              <input
                type="number"
                name="jersey_number"
                placeholder="Jersey Number"
                min="0"
                max="99"
                required>

              <select name="position" required>
                <option value="">Position</option>
                <option value="PG">Point Guard</option>
                <option value="SG">Shooting Guard</option>
                <option value="SF">Small Forward</option>
                <option value="PF">Power Forward</option>
                <option value="C">Center</option>
              </select>

            </div>

            <button class="save-btn" type="submit">
              Add Player
            </button>

          </form>

        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         YOUR GAMES TAB — add your content here
    ════════════════════════════════════════════════════ -->
    <div class="page" id="page-games">

      <div class="tab-tagline">
        <p class="tagline-txt" id="games-title">Your upcoming games</p>

        <button class="add-btn" id="games-action-btn" onclick="openGameOverlay()">
          <!-- calendar svg -->
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M7 2V5" stroke="currentColor" stroke-width="1.5" />
            <path d="M17 2V5" stroke="currentColor" stroke-width="1.5" />
            <path d="M3.5 9H20.5" stroke="currentColor" stroke-width="1.5" />
            <path d="M4 5.5C4 4.12 5.12 3 6.5 3H17.5C18.88 3 20 4.12 20 5.5V18.5C20 19.88 18.88 21 17.5 21H6.5C5.12 21 4 19.88 4 18.5V5.5Z" stroke="currentColor" stroke-width="1.5" />
          </svg>

          New Game
        </button>
      </div>

      <div class="games-container" id="games-container">
      </div>

    </div>

    <div class="backdrop" id="game-backdrop" onclick="closeGameOverlay()"></div>

    <div class="overlay" id="game-overlay">

      <div id="overlay-container">

        <button class="close-btn" onclick="closeGameOverlay()">
          <svg width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M42.8873 1.22032C44.5145 -0.406756 47.1527 -0.406689 48.7799 1.22032C50.4068 2.84753 50.407 5.48578 48.7799 7.1129L30.8922 24.9996L48.7799 42.8873C50.407 44.5145 50.4069 47.1527 48.7799 48.7799C47.1527 50.407 44.5145 50.4069 42.8873 48.7799L24.9997 30.8922L7.11293 48.7799C5.48582 50.407 2.84759 50.4068 1.22036 48.7799C-0.406679 47.1527 -0.406796 44.5145 1.22036 42.8873L19.1071 24.9996L1.22036 7.1129C-0.406819 5.48572 -0.406752 2.84754 1.22036 1.22032C2.84758 -0.406715 5.48578 -0.406832 7.11293 1.22032L24.9997 19.107L42.8873 1.22032Z" fill="white" />
          </svg>

        </button>

        <form id="game-form">

          <div class="player-image">
            <!-- zelfde image placeholder als players -->
            <svg width="226" height="226" viewBox="0 0 226 226" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd" clip-rule="evenodd" d="M140.422 103.752C143.501 103.72 146.736 103.788 150.075 103.937L125.811 133.595L102.017 128.308L99.2764 127.698L97.0312 129.383L48.2109 165.998C45.5147 168.02 44.9682 171.846 46.9902 174.542C49.0125 177.238 52.837 177.784 55.5332 175.762L102.108 140.83L126.831 146.324L130.498 147.14L132.878 144.231L155.541 116.531C156.279 136.197 153.946 157.818 150.826 176.722C148.615 190.119 146.05 201.941 144.038 210.41C143.033 214.642 142.166 218.032 141.554 220.355C141.386 220.99 141.234 221.544 141.107 222.015C140.858 222.079 140.609 222.144 140.359 222.206V160.273C140.359 159.262 139.54 158.443 138.529 158.442H123.883C122.872 158.442 122.052 159.262 122.052 160.273V225.203C119.033 225.445 115.98 225.571 112.898 225.571C111.878 225.571 110.861 225.556 109.847 225.529V172.479C109.847 171.467 109.027 170.647 108.016 170.647H93.3701C92.3591 170.647 91.5391 171.467 91.5391 172.479V223.554C87.3853 222.758 83.3125 221.735 79.334 220.498V184.684C79.3338 183.673 78.5139 182.854 77.5029 182.854H62.8564C61.8457 182.854 61.0266 183.673 61.0264 184.684V212.974C31.8118 197.834 10.0521 170.307 2.7041 137.346C4.86959 136.509 7.53003 135.495 10.6143 134.351C19.662 130.994 32.3361 126.511 46.8301 122.012C75.9828 112.962 111.885 104.044 140.422 103.752ZM166.923 146.394C167.954 146.409 169.099 146.443 170.31 146.509C175.203 146.773 180.339 147.506 183.625 148.984C186.647 150.344 189.594 153.063 192.361 156.956C195.1 160.808 197.395 165.431 199.246 170.019C200.84 173.968 202.055 177.766 202.921 180.81C190.626 197.029 174.049 209.823 154.872 217.508C155.197 216.196 155.547 214.767 155.912 213.231C157.973 204.558 160.6 192.451 162.868 178.709C164.524 168.678 166.006 157.657 166.923 146.394ZM167.008 105.232C177.802 106.354 188.814 107.976 198.639 109.632C207.744 111.167 215.737 112.713 221.45 113.876C223.09 114.21 224.541 114.512 225.775 114.772C225.425 133.992 220.27 152.038 211.464 167.761C211.177 167 210.878 166.229 210.564 165.451C208.504 160.345 205.79 154.781 202.309 149.884C198.857 145.028 194.372 140.436 188.634 137.854C183.159 135.39 176.088 134.597 170.967 134.321C169.801 134.258 168.693 134.221 167.673 134.2C168.07 124.365 167.936 114.547 167.008 105.232ZM25.1338 43.5459C37.3585 42.5591 52.2171 41.8321 63.6094 42.708C78.6814 43.8671 89.9828 50.7878 103.034 57.2852C114.661 63.0731 127.359 68.2228 143.229 65.0684C147.263 74.2171 150.559 83.3242 152.526 91.8359C148.302 91.6148 144.196 91.507 140.298 91.5469C109.881 91.8578 72.5516 101.248 43.2119 110.355C28.4597 114.935 15.5715 119.493 6.36914 122.907C4.27241 123.685 2.36639 124.405 0.671875 125.052C0.228542 120.987 1.79299e-05 116.857 0 112.674C0 86.6906 8.77841 62.7581 23.5303 43.6787C24.0585 43.6343 24.5934 43.5895 25.1338 43.5459ZM191.725 31.8525C210.403 50.0737 222.789 74.7156 225.315 102.212C224.855 102.117 224.378 102.017 223.884 101.916C218.069 100.733 209.938 99.1593 200.667 97.5967C189.878 95.7783 177.416 93.9518 165.207 92.7871C165.023 91.8466 164.83 90.9143 164.625 89.9912C162.567 80.7312 159.102 70.9664 154.892 61.2793C165.81 56.254 176.186 47.4702 183.963 39.8818C186.865 37.0495 189.483 34.3029 191.725 31.8525ZM105.933 0.993164C110.252 7.14706 116.113 15.8133 122.276 25.8535C127.541 34.4291 132.953 43.8946 137.805 53.5771C127.364 54.7917 118.471 51.3363 108.474 46.3594C96.58 40.4384 82.6124 31.9285 64.5459 30.5391C55.6817 29.8575 45.1529 30.0731 35.3613 30.6143C53.855 13.1341 78.2543 1.83674 105.253 0.03125C105.474 0.344919 105.702 0.664929 105.933 0.993164ZM120.077 0C143.571 1.47432 165.116 10.1349 182.539 23.8086C180.473 26.0619 178.079 28.5701 175.438 31.1465C167.749 38.65 158.563 46.1706 149.759 50.209C144.385 39.2702 138.363 28.7285 132.678 19.4678C128.163 12.1135 123.808 5.48558 120.077 0Z" fill="white" />
            </svg>

          </div>

          <div class="form-row">

            <select name="team_id" required>
              <option value="">Select Team</option>

              <?php while ($team = $teamsResult->fetch_assoc()) { ?>
                <option value="<?= $team['id'] ?>">
                  <?= $team['team_name'] . " " . $team['gender'] . $team['age_category'] ?>
                </option>
              <?php } ?>

            </select>

            <input type="text" name="opponent" placeholder="Opponent name" required>

            <input type="date" name="game_date" required>

          </div>

          <button class="save-btn" type="submit">
            Add Game
          </button>

        </form>

      </div>

    </div>

    <!-- ═══════════════════════════════════════════════════════
     GAME HISTORY TAB
════════════════════════════════════════════════════════ -->
    <div class="page" id="page-history">

      <div class="tab-tagline">
        <p class="tagline-txt" id="history-title">Your game history</p>
      </div>

      <div id="history-view">
        <?php
        $historyResult = $conn->query("
      SELECT g.*, t.team_name, t.gender, t.age_category, t.logo
      FROM games g
      JOIN teams t ON g.team_id = t.id
      WHERE g.user_id = '$user_id'
        AND g.status = 'finished'
      ORDER BY g.finished_at DESC
    ");
        ?>

        <?php if ($historyResult->num_rows === 0): ?>
          <div class="empty-state" style="margin-top: 60px;">No finished games yet. Complete a game to see it here.</div>
        <?php else: ?>
          <div class="history-grid">
            <?php while ($hg = $historyResult->fetch_assoc()):
              $ptsResult = $conn->query("SELECT SUM(pts) AS total FROM game_stats WHERE game_id = '{$hg['id']}'");
              $ptsRow    = $ptsResult->fetch_assoc();
              $homeScore = (int)($ptsRow['total'] ?? 0);
              $oppScore  = (int)($hg['opp_score'] ?? 0);
              $win       = $homeScore > $oppScore;
              $resultLabel = $win ? 'W' : ($homeScore === $oppScore ? 'D' : 'L');
              $resultClass = $win ? 'result-w' : ($homeScore === $oppScore ? 'result-d' : 'result-l');

              $dateObj = new DateTime($hg['finished_at'] ?? $hg['game_date']);
              $dateStr = $dateObj->format('l, F jS Y');

              $teamLabel = htmlspecialchars($hg['team_name'] . ' ' . $hg['gender'] . $hg['age_category']);
              $opponentLabel = htmlspecialchars($hg['opponent']);
            ?>
              <div class="history-card"
                onclick="loadBoxScore(<?= $hg['id'] ?>, '<?= $teamLabel ?>', '<?= $opponentLabel ?>')">
                <div class="hc-top">
                  <img class="hc-logo" src="<?= htmlspecialchars($hg['logo']) ?>" alt="logo"
                    onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 40 40%22><circle cx=%2220%22 cy=%2220%22 r=%2220%22 fill=%22%23f57c00%22/></svg>'">
                  <div class="hc-info">
                    <div class="hc-title"><?= $teamLabel ?> VS <?= $opponentLabel ?></div>
                    <div class="hc-date"><?= $dateStr ?></div>
                  </div>
                  <span class="box-score-link">Box Score</span>
                </div>
                <div class="hc-score <?= $resultClass ?>">
                  <span class="hc-result"><?= $resultLabel ?></span>
                  <?= $homeScore ?>–<?= $oppScore ?>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php endif; ?>
      </div>

      <div id="box-score-view" class="box-score-view" style="display:none;"></div>

    </div>

    <!-- ═══════════════════════════════════════════════════════
     STAT LEADERS TAB
════════════════════════════════════════════════════════ -->
    <div class="page" id="page-stat-leaders">

      <div class="tab-tagline">
        <p class="tagline-txt">Your stat leaders</p>

        <div class="custom-select" id="team-filter-select">
          <span type="button" class="custom-select-trigger" onclick="toggleTeamDropdown()" role="button" tabindex="0">
            <span id="team-filter-label">Select Team</span>
            <svg width="10" height="6" viewBox="0 0 10 6" fill="none">
              <path d="M1 1L5 5L9 1" stroke="#f57c00" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </span>

          <div class="custom-select-menu" id="team-filter-menu">
            <div class="custom-select-option" data-value="" onclick="selectTeam('', 'Select Team')">All teams</div>
            <?php
            $sqlAllTeams = "SELECT * FROM teams WHERE user_id = '$user_id' ORDER BY team_name";
            $allTeamsResult = $conn->query($sqlAllTeams);
            while ($t = $allTeamsResult->fetch_assoc()):
              $label = htmlspecialchars($t['team_name'] . ' ' . $t['gender'] . $t['age_category']);
            ?>
              <div class="custom-select-option" data-value="<?= $t['id'] ?>" onclick="selectTeam('<?= $t['id'] ?>', '<?= $label ?>')">
                <?= $label ?>
              </div>
            <?php endwhile; ?>
          </div>
        </div>
      </div>

      <div id="stat-leaders-grid" class="stat-leaders-grid">
        <div class="empty-state" style="margin-top: 60px;">Loading stat leaders...</div>
      </div>

    </div>

  </div><!-- /content -->

  <script src="./script/script.js"></script>

</body>

</html>