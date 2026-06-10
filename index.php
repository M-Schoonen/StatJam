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

  $sql = "SELECT * FROM players WHERE team_id = '$team_id' ORDER BY jersey_number";
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