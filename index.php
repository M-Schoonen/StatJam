<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

include 'db.php';

$user_id = $_SESSION['user_id'];

$sql = "SELECT * FROM teams WHERE user_id = '$user_id' ORDER BY age_category";
$result = $conn->query($sql);

if (isset($_GET['ajax']) && $_GET['ajax'] === 'players') {

  header('Content-Type: application/json');

  $team_id = $_GET['team_id'];

  $sql = "SELECT * FROM players WHERE team_id = '$team_id'";
  $result = $conn->query($sql);

  $players = [];

  while ($row = $result->fetch_assoc()) {
    $players[] = $row;
  }

  echo json_encode($players);
  exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>StatJam</title>
  <link rel="icon" href="./img/StatJam-Ball-Logo.webp" type="image/webp" />
  <link rel="stylesheet" href="./css/style.css" />
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
          <div class="stat-card-value">42</div>
        </div>

        <div class="stat-card">
          <div class="stat-card-top">
            <span class="stat-card-label">Games Played</span>
            <svg class="stat-card-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <rect x="3" y="4" width="18" height="18" rx="2" />
              <path d="M16 2v4M8 2v4M3 10h18" />
            </svg>
          </div>
          <div class="stat-card-value">24</div>
        </div>

        <div class="stat-card">
          <div class="stat-card-top">
            <span class="stat-card-label">Win Rate</span>
            <svg class="stat-card-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M6 9H4.5a2.5 2.5 0 0 0 0 5H6" />
              <path d="M18 9h1.5a2.5 2.5 0 0 1 0 5H18" />
              <path d="M4 22V12a8 8 0 0 1 16 0v10" />
            </svg>
          </div>
          <div class="stat-card-value large">60%</div>
          <div class="stat-card-sub">16W–8L</div>
        </div>

        <div class="stat-card">
          <div class="stat-card-top">
            <span class="stat-card-label">Upcoming Games</span>
            <svg class="stat-card-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
              <polyline points="16 7 22 7 22 13" />
            </svg>
          </div>
          <div class="stat-card-value">2</div>
        </div>
      </div>

      <!-- Bottom panels -->
      <div class="panels">

        <!-- Top Performers -->
        <div class="panel">
          <div class="panel-title">Top Performers</div>
          <div class="panel-subtitle">Leading scorers based on completed games</div>

          <!-- ADD MORE PERFORMER ROWS HERE -->
          <div class="performer-row">
            <div class="performer-left">
              <span class="performer-num">#15</span>
              <div>
                <div class="performer-name">Melvin Schoonen</div>
                <div class="performer-pos">SG</div>
              </div>
            </div>
            <div class="performer-right">
              <div class="performer-ppg">17.4 PPG</div>
              <div class="performer-extras">4.7 RPG, 5.1 APG</div>
            </div>
          </div>
          <!-- /performer-row -->

        </div>

        <!-- Recent Games -->
        <div class="panel">
          <div class="panel-title">Recent Games</div>
          <div class="panel-subtitle">Latest game results and upcoming matches</div>

          <!-- ADD MORE GAME ROWS HERE -->
          <div class="game-row">
            <div>
              <div class="game-opponent">VS Barons</div>
              <div class="game-date">31-7-2025 • Home</div>
            </div>
            <div class="game-badge">
              <span class="badge-final">Final</span>
              <span class="game-score">120-36</span>
            </div>
          </div>
          <!-- /game-row -->

        </div>

      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         TEAMS & PLAYERS TAB — add your content here
    ════════════════════════════════════════════════════════ -->
    <div class="page" id="page-teams">
      <div class="tab-tagline">
        <p class="tagline-txt">Your teams &amp; players</p>
        <button class="add-btn" onclick="openOverlay()">
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
                onclick="loadPlayers(<?= $team['id'] ?>, '<?= htmlspecialchars($team['team_name']) ?>')">

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


      <div class="backdrop" id="backdrop" onclick="closeOverlay()"></div>

      <div class="overlay" id="overlay">
        <div id="overlay-container">
          <button class="close-btn" onclick="closeOverlay()">
            <svg width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd" clip-rule="evenodd" d="M42.8872 1.22016C44.5143 -0.406816 47.1526 -0.406627 48.7798 1.22016C50.407 2.84734 50.4069 5.48552 48.7798 7.11274L30.8921 24.9995L48.7798 42.8872C50.4069 44.5143 50.4068 47.1525 48.7798 48.7797C47.1526 50.4069 44.5144 50.4069 42.8872 48.7797L24.9995 30.892L7.11279 48.7797C5.48558 50.4069 2.84743 50.4069 1.22022 48.7797C-0.40661 47.1525 -0.406867 44.5142 1.22022 42.8872L19.1069 24.9995L1.22022 7.11274C-0.406582 5.48553 -0.40679 2.84725 1.22022 1.22016C2.84731 -0.406742 5.48561 -0.406601 7.11279 1.22016L24.9995 19.1069L42.8872 1.22016Z" fill="white" />
            </svg>
          </button>

          <form action="save_team.php" method="POST" enctype="multipart/form-data">
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
    </div>

    <!-- ═══════════════════════════════════════════════════════
         YOUR GAMES TAB — add your content here
    ════════════════════════════════════════════════════ -->
    <div class="page" id="page-games">
      <div class="placeholder">
        <h2>Your Games</h2>
        <p>Your games content goes here.</p>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         GAME HISTORY TAB — add your content here
    ════════════════════════════════════════════════════════ -->
    <div class="page" id="page-history">
      <div class="placeholder">
        <h2>Game History</h2>
        <p>Your game history content goes here.</p>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         STAT LEADERS TAB — add your content here
    ════════════════════════════════════════════════════════ -->
    <div class="page" id="page-stat-leaders">
      <div class="placeholder">
        <h2>Stat Leaders</h2>
        <p>Your stat leaders content goes here.</p>
      </div>
    </div>

  </div><!-- /content -->

  <script src="./script/script.js"></script>

</body>

</html>