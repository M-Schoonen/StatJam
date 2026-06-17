<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'db.php';

$game_id = $_GET['game_id'] ?? null;
if (!$game_id) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$gameResult = $conn->query("
  SELECT g.*, t.team_name, t.gender, t.age_category, t.logo
  FROM games g
  JOIN teams t ON g.team_id = t.id
  WHERE g.id = '$game_id' AND g.user_id = '$user_id'
");

if ($gameResult->num_rows === 0) {
    header('Location: index.php');
    exit;
}

$game = $gameResult->fetch_assoc();

// Block access if game is already finished
if (($game['status'] ?? '') === 'finished') {
    header('Location: index.php');
    exit;
}

$playersResult = $conn->query("
  SELECT * FROM players WHERE team_id = '{$game['team_id']}' ORDER BY jersey_number
");
$players = [];
while ($row = $playersResult->fetch_assoc()) {
    $players[] = $row;
}

// Load existing saved stats so Pause & Resume works
$existingStats = [];
$existingOpp   = (int)($game['opp_score'] ?? 0);
$statsResult   = $conn->query("SELECT * FROM game_stats WHERE game_id = '$game_id'");
while ($row = $statsResult->fetch_assoc()) {
    $existingStats[$row['player_id']] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>StatJam – Live Game</title>
    <link rel="icon" href="./img/StatJam-Ball-Logo.webp" type="image/webp" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/tracker.css" />
</head>

<body>

    <!-- ── Scoreboard ──────────────────────────────────────────── -->
    <header class="scoreboard">
        <a href="index.php" class="sb-back">
            <svg width="8" height="14" viewBox="0 0 10 16" fill="none">
                <path d="M0 8L8.108 0 10 1.867 3.784 8 10 14.133 8.108 16z" fill="currentColor" />
            </svg>
            Back
        </a>
        <div class="sb-team">
            <img class="sb-logo" src="<?= htmlspecialchars($game['logo']) ?>" alt="logo"
                onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 40 40%22><circle cx=%2220%22 cy=%2220%22 r=%2220%22 fill=%22%23f57c00%22/></svg>'">
            <div>
                <div class="sb-team-name"><?= htmlspecialchars($game['team_name']) ?> <?= $game['gender'] . $game['age_category'] ?></div>
                <div class="sb-team-sub">Your team</div>
            </div>
        </div>
        <div class="sb-scores">
            <div class="sb-score" id="home-score">0</div>
            <div class="sb-dash">–</div>
            <div class="sb-score opp" id="opp-score">0</div>
        </div>
        <div class="sb-opp">
            <div class="sb-opp-name"><?= htmlspecialchars($game['opponent']) ?></div>
            <div class="sb-opp-sub">Opponent</div>
            <div class="sb-opp-btns">
                <button class="opp-pt-btn" onclick="addOppPoints(1)">+1</button>
                <button class="opp-pt-btn" onclick="addOppPoints(2)">+2</button>
                <button class="opp-pt-btn" onclick="addOppPoints(3)">+3</button>
                <button class="opp-pt-btn" onclick="addOppPoints(-1)" style="color:#e53935">−1</button>
            </div>
        </div>
    </header>

    <!-- ── Body ───────────────────────────────────────────────── -->
    <div class="tracker-body">
        <aside class="sidebar">
            <div class="sidebar-header">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="9" cy="7" r="4" />
                    <path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    <path d="M21 21v-2a4 4 0 0 0-3-3.85" />
                </svg>
                Your players
            </div>
            <?php foreach ($players as $p): ?>
                <div class="player-item" id="pi-<?= $p['id'] ?>"
                    onclick="selectPlayer(<?= $p['id'] ?>, '<?= addslashes($p['first_name']) ?>', '<?= addslashes($p['last_name']) ?>', '<?= $p['jersey_number'] ?>', '<?= $p['position'] ?>')">
                    <div class="pi-num">#<?= $p['jersey_number'] ?></div>
                    <div class="pi-info">
                        <div class="pi-name"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></div>
                        <div class="pi-pts" id="pi-pts-<?= $p['id'] ?>">0 pts</div>
                    </div>
                </div>
            <?php endforeach; ?>
            <button class="all-stats-btn" onclick="openAllStats()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <rect x="3" y="3" width="7" height="7" rx="1" />
                    <rect x="14" y="3" width="7" height="7" rx="1" />
                    <rect x="3" y="14" width="7" height="7" rx="1" />
                    <rect x="14" y="14" width="7" height="7" rx="1" />
                </svg>
                All Stats
            </button>
        </aside>

        <main class="stat-panel" id="stat-panel">
            <div class="no-player-msg" id="no-player-msg">
                <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                    <circle cx="9" cy="7" r="4" />
                    <path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2" />
                </svg>
                <p>Select a player from the left<br>to log their stats</p>
            </div>
            <div id="player-stats-ui" style="display:none;">
                <div class="player-header">
                    <div class="ph-avatar">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <circle cx="12" cy="8" r="4" />
                            <path d="M4 20v-1a8 8 0 0 1 16 0v1" />
                        </svg>
                    </div>
                    <div>
                        <div style="display:flex;align-items:baseline;gap:8px;">
                            <span class="ph-num" id="ph-num">#0</span>
                            <span class="ph-name" id="ph-name">Player Name</span>
                        </div>
                        <div class="ph-pos" id="ph-pos">Position</div>
                    </div>
                </div>
                <div class="stat-section">
                    <div class="stat-section-title">Scoring</div>
                    <div class="stat-grid">
                        <div class="stat-tile">
                            <div class="st-label">Points</div>
                            <div class="st-value" id="v-pts">0</div>
                            <div class="st-btns">
                                <button class="st-btn" onclick="addStat('pts',1)">+1</button>
                                <button class="st-btn" onclick="addStat('pts',2)">+2</button>
                                <button class="st-btn" onclick="addStat('pts',3)">+3</button>
                                <button class="st-btn minus" onclick="addStat('pts',-1)">−</button>
                            </div>
                        </div>
                        <div class="stat-tile">
                            <div class="st-label">FG Made / Att</div>
                            <div style="display:flex;align-items:baseline;gap:6px;">
                                <div class="st-value" id="v-fgm">0</div>
                                <div style="color:#bbb;font-size:18px;font-weight:700;">/</div>
                                <div class="st-value" id="v-fga">0</div>
                            </div>
                            <div class="st-pct" id="pct-fg">0.0%</div>
                            <div class="st-btns">
                                <button class="st-btn" onclick="addStat('fgm',1)">+M</button>
                                <button class="st-btn" onclick="addStat('fga',1)">+A</button>
                                <button class="st-btn minus" onclick="addStat('fgm',-1)">−M</button>
                                <button class="st-btn minus" onclick="addStat('fga',-1)">−A</button>
                            </div>
                        </div>
                        <div class="stat-tile">
                            <div class="st-label">3PT Made / Att</div>
                            <div style="display:flex;align-items:baseline;gap:6px;">
                                <div class="st-value" id="v-3pm">0</div>
                                <div style="color:#bbb;font-size:18px;font-weight:700;">/</div>
                                <div class="st-value" id="v-3pa">0</div>
                            </div>
                            <div class="st-pct" id="pct-3p">0.0%</div>
                            <div class="st-btns">
                                <button class="st-btn" onclick="addStat('3pm',1)">+M</button>
                                <button class="st-btn" onclick="addStat('3pa',1)">+A</button>
                                <button class="st-btn minus" onclick="addStat('3pm',-1)">−M</button>
                                <button class="st-btn minus" onclick="addStat('3pa',-1)">−A</button>
                            </div>
                        </div>
                        <div class="stat-tile">
                            <div class="st-label">FT Made / Att</div>
                            <div style="display:flex;align-items:baseline;gap:6px;">
                                <div class="st-value" id="v-ftm">0</div>
                                <div style="color:#bbb;font-size:18px;font-weight:700;">/</div>
                                <div class="st-value" id="v-fta">0</div>
                            </div>
                            <div class="st-pct" id="pct-ft">0.0%</div>
                            <div class="st-btns">
                                <button class="st-btn" onclick="addStat('ftm',1)">+M</button>
                                <button class="st-btn" onclick="addStat('fta',1)">+A</button>
                                <button class="st-btn minus" onclick="addStat('ftm',-1)">−M</button>
                                <button class="st-btn minus" onclick="addStat('fta',-1)">−A</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="stat-section">
                    <div class="stat-section-title">Defense & Other</div>
                    <div class="stat-grid">
                        <?php
                        $defStats = [
                            ['key' => 'reb', 'label' => 'Rebounds'],
                            ['key' => 'ast', 'label' => 'Assists'],
                            ['key' => 'stl', 'label' => 'Steals'],
                            ['key' => 'blk', 'label' => 'Blocks'],
                            ['key' => 'tov', 'label' => 'Turnovers'],
                            ['key' => 'foul', 'label' => 'Fouls'],
                        ];
                        foreach ($defStats as $ds): ?>
                            <div class="stat-tile">
                                <div class="st-label"><?= $ds['label'] ?></div>
                                <div class="st-value" id="v-<?= $ds['key'] ?>">0</div>
                                <div class="st-btns">
                                    <button class="st-btn" onclick="addStat('<?= $ds['key'] ?>',1)">+1</button>
                                    <button class="st-btn minus" onclick="addStat('<?= $ds['key'] ?>',-1)">−</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- ── All Stats Modal ───────────────────────────────────── -->
    <div class="modal-backdrop" id="modal-backdrop" onclick="closeModal(event)">
        <div class="modal">
            <div class="modal-head">
                <div class="modal-title">All Stats – <?= htmlspecialchars($game['team_name']) ?> vs <?= htmlspecialchars($game['opponent']) ?></div>
                <button class="modal-close" onclick="closeAllStats()">×</button>
            </div>
            <div class="modal-body">
                <table class="stats-table" id="all-stats-table">
                    <thead>
                        <tr>
                            <th>Player</th>
                            <th>PTS</th>
                            <th>REB</th>
                            <th>AST</th>
                            <th>STL</th>
                            <th>BLK</th>
                            <th>TOV</th>
                            <th>FOULS</th>
                            <th>FGM</th>
                            <th>FGA</th>
                            <th>FG%</th>
                            <th>3PM</th>
                            <th>3PA</th>
                            <th>3P%</th>
                            <th>FTM</th>
                            <th>FTA</th>
                            <th>FT%</th>
                        </tr>
                    </thead>
                    <tbody id="stats-tbody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ── Finish confirm modal ──────────────────────────────── -->
    <div class="confirm-backdrop" id="confirm-backdrop">
        <div class="confirm-box">
            <div class="confirm-icon">🏁</div>
            <div class="confirm-title">End this game?</div>
            <div class="confirm-sub">Stats will be saved and the game will move to Game History. This cannot be undone.</div>
            <div class="confirm-btns">
                <button class="confirm-cancel" onclick="closeConfirm()">Cancel</button>
                <button class="confirm-ok" onclick="finishGame()">Save & Finish</button>
            </div>
        </div>
    </div>

    <!-- ── Save bar ──────────────────────────────────────────── -->
    <div class="save-bar">
        <span class="save-status" id="save-status"></span>
        <button class="pause-btn" onclick="pauseGame()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <rect x="6" y="4" width="4" height="16" rx="1" />
                <rect x="14" y="4" width="4" height="16" rx="1" />
            </svg>
            Pause & Save
        </button>
        <button class="finish-btn" onclick="openConfirm()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <polyline points="20 6 9 17 4 12" />
            </svg>
            Save & Finish
        </button>
    </div>

    <script>
        const GAME_ID = <?= json_encode($game_id) ?>;
        const STAT_KEYS = ['pts', 'fgm', 'fga', '3pm', '3pa', 'ftm', 'fta', 'reb', 'ast', 'stl', 'blk', 'tov', 'foul'];

        const playerStats = {};
        const playerMeta = {};

        <?php foreach ($players as $p):
            $es = $existingStats[$p['id']] ?? [];
        ?>
            playerStats[<?= $p['id'] ?>] = {
                pts: <?= (int)($es['pts']      ?? 0) ?>,
                fgm: <?= (int)($es['fgm']      ?? 0) ?>,
                fga: <?= (int)($es['fga']      ?? 0) ?>,
                '3pm': <?= (int)($es['three_pm'] ?? 0) ?>,
                '3pa': <?= (int)($es['three_pa'] ?? 0) ?>,
                ftm: <?= (int)($es['ftm']      ?? 0) ?>,
                fta: <?= (int)($es['fta']      ?? 0) ?>,
                reb: <?= (int)($es['reb']      ?? 0) ?>,
                ast: <?= (int)($es['ast']      ?? 0) ?>,
                stl: <?= (int)($es['stl']      ?? 0) ?>,
                blk: <?= (int)($es['blk']      ?? 0) ?>,
                tov: <?= (int)($es['tov']      ?? 0) ?>,
                foul: <?= (int)($es['fouls']    ?? 0) ?>
            };
            playerMeta[<?= $p['id'] ?>] = {
                name: <?= json_encode($p['first_name'] . ' ' . $p['last_name']) ?>,
                num: <?= $p['jersey_number'] ?>,
                pos: <?= json_encode($p['position']) ?>
            };
        <?php endforeach; ?>

        let oppScore = <?= $existingOpp ?>;
        let selectedId = null;

        // Init scoreboard & sidebar from loaded data
        (function init() {
            recalcHomeScore();
            document.getElementById('opp-score').textContent = oppScore;
            for (const id in playerStats) updateSidebarPts(id);
        })();

        function addOppPoints(n) {
            oppScore = Math.max(0, oppScore + n);
            document.getElementById('opp-score').textContent = oppScore;
        }

        function selectPlayer(id, fn, ln, num, pos) {
            document.querySelectorAll('.player-item').forEach(el => el.classList.remove('active'));
            document.getElementById('pi-' + id).classList.add('active');
            selectedId = id;
            document.getElementById('no-player-msg').style.display = 'none';
            document.getElementById('player-stats-ui').style.display = 'block';
            document.getElementById('ph-num').textContent = '#' + num;
            document.getElementById('ph-name').textContent = fn + ' ' + ln;
            document.getElementById('ph-pos').textContent = pos;
            refreshStatUI();
        }

        function addStat(key, delta) {
            if (selectedId === null) return;
            const s = playerStats[selectedId];
            s[key] = Math.max(0, (s[key] || 0) + delta);
            recalcHomeScore();
            refreshStatUI();
            updateSidebarPts(selectedId);
        }

        function recalcHomeScore() {
            let total = 0;
            for (const id in playerStats) total += playerStats[id].pts || 0;
            document.getElementById('home-score').textContent = total;
        }

        function pct(made, att) {
            if (!att) return '0.0%';
            return (made / att * 100).toFixed(1) + '%';
        }

        function refreshStatUI() {
            if (selectedId === null) return;
            const s = playerStats[selectedId];
            for (const k of STAT_KEYS) {
                const el = document.getElementById('v-' + k);
                if (el) el.textContent = s[k] || 0;
            }
            document.getElementById('pct-fg').textContent = pct(s.fgm, s.fga);
            document.getElementById('pct-3p').textContent = pct(s['3pm'], s['3pa']);
            document.getElementById('pct-ft').textContent = pct(s.ftm, s.fta);
        }

        function updateSidebarPts(id) {
            const el = document.getElementById('pi-pts-' + id);
            if (el) el.textContent = (playerStats[id].pts || 0) + ' pts';
        }

        // ── All stats modal ────────────────────────────────────────
        function openAllStats() {
            const tbody = document.getElementById('stats-tbody');
            tbody.innerHTML = '';
            for (const id in playerStats) {
                const s = playerStats[id];
                const m = playerMeta[id];
                const tr = document.createElement('tr');
                tr.innerHTML = `
      <td><span class="player-num">#${m.num}</span>${m.name}</td>
      <td>${s.pts}</td><td>${s.reb}</td><td>${s.ast}</td>
      <td>${s.stl}</td><td>${s.blk}</td><td>${s.tov}</td><td>${s.foul}</td>
      <td>${s.fgm}</td><td>${s.fga}</td><td class="pct">${pct(s.fgm,s.fga)}</td>
      <td>${s['3pm']}</td><td>${s['3pa']}</td><td class="pct">${pct(s['3pm'],s['3pa'])}</td>
      <td>${s.ftm}</td><td>${s.fta}</td><td class="pct">${pct(s.ftm,s.fta)}</td>
    `;
                tbody.appendChild(tr);
            }
            document.getElementById('modal-backdrop').classList.add('open');
        }

        function closeAllStats() {
            document.getElementById('modal-backdrop').classList.remove('open');
        }

        function closeModal(e) {
            if (e.target === document.getElementById('modal-backdrop')) closeAllStats();
        }

        // ── Confirm finish modal ───────────────────────────────────
        function openConfirm() {
            document.getElementById('confirm-backdrop').classList.add('open');
        }

        function closeConfirm() {
            document.getElementById('confirm-backdrop').classList.remove('open');
        }

        // ── Shared save helper ─────────────────────────────────────
        function buildPayload(finish) {
            return {
                game_id: GAME_ID,
                opp_score: oppScore,
                finish: finish,
                players: playerStats
            };
        }

        function setStatus(msg, duration = 3000) {
            const el = document.getElementById('save-status');
            el.textContent = msg;
            if (duration) setTimeout(() => el.textContent = '', duration);
        }

        // ── Pause & Save — keeps game in upcoming ─────────────────
        function pauseGame() {
            setStatus('Saving…', 0);
            fetch('save_game_stats.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(buildPayload(false))
                })
                .then(r => r.json())
                .then(d => {
                    if (d.ok) {
                        setStatus('✓ Progress saved');
                        setTimeout(() => window.location.href = 'index.php', 1000);
                    } else {
                        setStatus('⚠ Error saving');
                    }
                })
                .catch(() => setStatus('⚠ Could not connect'));
        }

        // ── Save & Finish — marks game as finished ─────────────────
        function finishGame() {
            closeConfirm();
            setStatus('Finishing game…', 0);
            fetch('save_game_stats.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(buildPayload(true))
                })
                .then(r => r.json())
                .then(d => {
                    if (d.ok) {
                        setStatus('✓ Game finished!');
                        setTimeout(() => window.location.href = 'index.php', 1000);
                    } else {
                        setStatus('⚠ Error saving');
                    }
                })
                .catch(() => setStatus('⚠ Could not connect'));
        }
    </script>
</body>

</html>