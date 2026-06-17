// ─── Navigation ──────────────────────────────────────────────────────────────

function setTab(el) {
  document
    .querySelectorAll(".nav-tab")
    .forEach((t) => t.classList.remove("active"));
  el.classList.add("active");
  movePill(el);

  const pageId = el.dataset.page;
  document
    .querySelectorAll(".page")
    .forEach((p) => p.classList.remove("active"));
  document.getElementById(pageId).classList.add("active");

  sessionStorage.setItem("activeTab", pageId);
}

function movePill(el) {
  const pill = document.getElementById("nav-pill");
  const navRect = el.closest("nav").getBoundingClientRect();
  const elRect = el.getBoundingClientRect();
  pill.style.left = elRect.left - navRect.left + "px";
  pill.style.width = elRect.width + "px";
}

window.addEventListener("load", () => {
  const savedTab = sessionStorage.getItem("activeTab");

  if (savedTab) {
    const tabEl = document.querySelector(`[data-page="${savedTab}"]`);
    if (tabEl) {
      document
        .querySelectorAll(".nav-tab")
        .forEach((t) => t.classList.remove("active"));
      tabEl.classList.add("active");
      document
        .querySelectorAll(".page")
        .forEach((p) => p.classList.remove("active"));
      document.getElementById(savedTab).classList.add("active");
    }
  }

  const active = document.querySelector(".nav-tab.active");
  if (!active) return;

  const pill = document.getElementById("nav-pill");
  pill.style.transition = "none";
  movePill(active);
  requestAnimationFrame(() => {
    pill.style.transition =
      "left 0.35s cubic-bezier(0.4,0,0.2,1), width 0.35s cubic-bezier(0.4,0,0.2,1)";
  });
});

// ─── Overlays ─────────────────────────────────────────────────────────────────

function openOverlay() {
  document.getElementById("backdrop").classList.add("visible");
  document.getElementById("overlay").classList.add("open");
}

function closeAllOverlays() {
  document
    .querySelectorAll(".overlay")
    .forEach((el) => el.classList.remove("open"));
  document.getElementById("backdrop").classList.remove("visible");
}

function openGameOverlay() {
  document.getElementById("game-backdrop").classList.add("visible");
  document.getElementById("game-overlay").classList.add("open");
}

function closeGameOverlay() {
  document.getElementById("game-backdrop").classList.remove("visible");
  document.getElementById("game-overlay").classList.remove("open");
}

function openAddPlayer(teamId) {
  document.getElementById("player-team-id").value = teamId;
  document.getElementById("backdrop").classList.add("visible");
  document.getElementById("player-overlay").classList.add("open");
}

// ─── Teams & Players ──────────────────────────────────────────────────────────

let currentTeam = null;

function loadPlayers(teamId, teamName, gender, ageCategory) {
  currentTeam = { teamId, teamName, gender, ageCategory };

  const teamsView = document.getElementById("teams-view");
  const playersView = document.getElementById("players-view");

  setPlayersHeader(teamId, teamName, gender, ageCategory);
  teamsView.style.opacity = 0;

  setTimeout(() => {
    teamsView.style.display = "none";

    fetch(`?ajax=players&team_id=${teamId}`)
      .then((res) => res.json())
      .then((players) => {
        let html = `<div class="players-container">`;

        if (players.length === 0) {
          html += `<div class="empty-state">No players found</div>`;
        }

        players.forEach((p) => {
          html += `
            <div class="player-card">
              <div class="player-left">
                <div class="player-avatar">
                  <svg width="75" height="66" viewBox="0 0 75 66" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M51.786 17.8571C51.786 9.96731 45.3897 3.57102 37.5 3.57102C29.6103 3.57102 23.214 9.96731 23.214 17.8571C23.214 25.7468 29.6103 32.1431 37.5 32.1431V35.7141C27.6378 35.7141 19.6429 27.7192 19.6429 17.8571C19.6429 7.99488 27.6378 0 37.5 0C47.3622 0 55.3571 7.99488 55.3571 17.8571C55.3571 27.7192 47.3622 35.7141 37.5 35.7141V32.1431C45.3897 32.1431 51.786 25.7468 51.786 17.8571Z" fill="#F57C00"/>
                    <path d="M37.5 44.6431C52.946 44.6431 62.2909 48.9939 67.8082 53.5319C70.5533 55.7898 72.3126 58.0629 73.3933 59.7981C73.9333 60.6651 74.3035 61.3976 74.5433 61.9266C74.6633 62.1911 74.751 62.4054 74.8107 62.5602C74.8405 62.6374 74.8639 62.7004 74.8805 62.7472L74.9076 62.8247L74.9105 62.8363C74.9053 62.8395 74.8107 62.8714 73.2141 63.3924L74.9115 62.8392C75.2172 63.7767 74.7057 64.7848 73.7683 65.0907C72.8314 65.3964 71.8244 64.8848 71.5177 63.9485L71.5168 63.9495C71.5161 63.9474 71.5159 63.9447 71.5148 63.9417C71.5087 63.9244 71.4961 63.8917 71.478 63.8448C71.4419 63.7511 71.3814 63.6004 71.291 63.4011C71.1103 63.0024 70.8124 62.4096 70.3619 61.6863C69.4616 60.2408 67.9502 58.2732 65.5392 56.2901C60.744 52.346 52.2323 48.2141 37.5 48.2141C22.7677 48.2141 14.256 52.346 9.46078 56.2901C7.04977 58.2732 5.53836 60.2408 4.63806 61.6863C4.18763 62.4096 3.88974 63.0024 3.70897 63.4011C3.61863 63.6004 3.55813 63.7511 3.52199 63.8448C3.50393 63.8917 3.49133 63.9244 3.48518 63.9417C3.48408 63.9448 3.483 63.9474 3.48227 63.9495C3.48231 63.9493 3.48272 63.9491 3.4813 63.9485C3.17449 64.8846 2.16855 65.3964 1.23173 65.0907C0.294311 64.7848 -0.217209 63.7767 0.0885377 62.8392L1.78589 63.3924C0.189024 62.8713 0.0946714 62.8395 0.0895065 62.8363L0.0924129 62.8247L0.11954 62.7472C0.136146 62.7004 0.15951 62.6374 0.189294 62.5602C0.248987 62.4054 0.336738 62.1911 0.456684 61.9266C0.696523 61.3976 1.06667 60.6651 1.60666 59.7981C2.68736 58.0629 4.44666 55.7898 7.19183 53.5319C12.7091 48.9939 22.054 44.6431 37.5 44.6431Z" fill="#F57C20"/>
                  </svg>
                </div>
              </div>
              <div class="player-right">
                <div class="player-top">
                  <span class="player-number">#${p.jersey_number}</span>
                  <span class="player-name">${p.first_name} ${p.last_name}</span>
                  <span class="player-position">${p.position}</span>
                </div>
                <div class="player-stats">
                  <div class="stat">
                    <span class="stat-label">PPG</span>
                    <span class="stat-value">0.0</span>
                  </div>
                  <div class="stat">
                    <span class="stat-label">RPG</span>
                    <span class="stat-value">0.0</span>
                  </div>
                  <div class="stat">
                    <span class="stat-label">APG</span>
                    <span class="stat-value">0.0</span>
                  </div>
                </div>
              </div>
            </div>
          `;
        });

        html += `</div>`;
        playersView.innerHTML = html;
        playersView.style.display = "block";
        setTimeout(() => (playersView.style.opacity = 1), 50);
      });
  }, 200);
}

function backToTeams() {
  const teamsView = document.getElementById("teams-view");
  const playersView = document.getElementById("players-view");

  resetTeamsHeader();
  playersView.style.opacity = 0;

  setTimeout(() => {
    playersView.style.display = "none";
    teamsView.style.display = "block";
    setTimeout(() => (teamsView.style.opacity = 1), 50);
  }, 200);
}

function setPlayersHeader(teamId, teamName, gender, ageCategory) {
  document.getElementById("page-title").innerHTML = `
    <span class="back-title" onclick="backToTeams()">
      <svg class="back-icon" width="10" height="16" viewBox="0 0 10 16" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M0 8L8.10811 0L10 1.86667L3.78378 8L10 14.1333L8.10811 16L0 8Z" fill="#F57C00"/>
      </svg>
      <span class="back-text">${teamName} ${gender}${ageCategory}</span>
    </span>
  `;

  const btn = document.getElementById("action-btn");
  btn.innerHTML = "+ Add Player";
  btn.onclick = () => openAddPlayer(teamId);
}

function resetTeamsHeader() {
  document.getElementById("page-title").innerText = "Your teams & players";

  const btn = document.getElementById("action-btn");
  btn.innerHTML = "+ Add Team";
  btn.onclick = openOverlay;
}

function refreshStats() {
  fetch("get_stats.php")
    .then((res) => res.json())
    .then((data) => {
      document.getElementById("total-players").textContent = data.total_players;
    });
}

// ─── Games ────────────────────────────────────────────────────────────────────

let games = [];

function renderGames() {
  const container = document.getElementById("games-container");
  container.innerHTML = "";

  if (games.length === 0) {
    container.innerHTML = `<div class="empty-state">No upcoming games yet.</div>`;
    return;
  }

  games.forEach((g) => {
    const isInProgress = g.status === "in_progress";
    const btnLabel = isInProgress ? "Continue game" : "Start game";
    const badgeHtml = isInProgress
      ? `<span class="badge-in-progress">In Progress</span>`
      : "";

    container.innerHTML += `
      <div class="game-card${isInProgress ? " in-progress" : ""}">
        <img class="game-logo" src="${g.logo}" />
        <div class="game-info">
          <div class="game-title">${g.team_name} ${g.gender}${g.age_category} VS ${g.opponent}</div>
          <div class="game-date">${formatGameDate(g.game_date)} ${badgeHtml}</div>
        </div>
        <button class="start-btn" onclick="window.location.href='game_tracker.php?game_id=${g.id}'">${btnLabel}</button>
        <button class="delete-game-btn" onclick="deleteGame(${g.id})">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
            <path d="M3 6H21" stroke="currentColor" stroke-width="1.5"/>
            <path d="M8 6V4C8 2.9 8.9 2 10 2H14C15.1 2 16 2.9 16 4V6" stroke="currentColor" stroke-width="1.5"/>
            <path d="M19 6L18.1 19C18 20.1 17.1 21 16 21H8C6.9 21 6 20.1 5.9 19L5 6" stroke="currentColor" stroke-width="1.5"/>
            <path d="M10 11V17" stroke="currentColor" stroke-width="1.5"/>
            <path d="M14 11V17" stroke="currentColor" stroke-width="1.5"/>
          </svg>
        </button>
      </div>
    `;
  });
}

function formatGameDate(dateStr) {
  return new Date(dateStr).toLocaleDateString("en-US", {
    weekday: "long",
    month: "long",
    day: "numeric",
    year: "numeric",
  });
}

// ─── Form handlers ────────────────────────────────────────────────────────────

document.addEventListener("DOMContentLoaded", () => {
  // Load games on page start
  fetch("?ajax=games")
    .then((res) => res.json())
    .then((data) => {
      games = data;
      renderGames();
    });

  // Player form
  const playerForm = document.getElementById("player-form");
  if (playerForm) {
    playerForm.addEventListener("submit", (e) => {
      e.preventDefault();

      fetch("save_player.php", {
        method: "POST",
        body: new FormData(playerForm),
      })
        .then((res) => res.text())
        .then(() => {
          closeAllOverlays();
          loadPlayers(
            currentTeam.teamId,
            currentTeam.teamName,
            currentTeam.gender,
            currentTeam.ageCategory,
          );
          refreshStats();
          playerForm.reset();
        });
    });
  }

  // Team form
  const teamForm = document.getElementById("team-form");
  if (teamForm) {
    teamForm.addEventListener("submit", (e) => {
      e.preventDefault();

      fetch("save_team.php", { method: "POST", body: new FormData(teamForm) })
        .then((res) => res.text())
        .then(() => {
          closeAllOverlays();
          teamForm.reset();
          location.reload();
        })
        .catch((err) => console.error(err));
    });
  }

  // Game form
  const gameForm = document.getElementById("game-form");
  if (gameForm) {
    gameForm.addEventListener("submit", (e) => {
      e.preventDefault();

      fetch("save_games.php", { method: "POST", body: new FormData(gameForm) })
        .then((res) => res.json())
        .then((game) => {
          closeGameOverlay();
          // Add the new game to the array and re-render
          games.push(game);

          games.sort((a, b) => {
            return new Date(a.game_date) - new Date(b.game_date);
          });

          renderGames();
          updateUpcomingGamesCount();

          gameForm.reset();
        })
        .catch((err) => console.error(err));
    });
  }
});

// Call this from your existing team card rendering logic to keep it DRY.
// Make sure save_team.php returns the saved team as JSON so we can use it here.
function appendTeamCard(team) {
  const container = document.getElementById("teams-container"); // adjust selector if needed
  if (!container) return;

  const card = document.createElement("div");
  card.className = "team-card";
  card.innerHTML = `
    <span class="team-name">${team.name} ${team.gender}${team.age_category}</span>
  `;
  card.onclick = () =>
    loadPlayers(team.id, team.name, team.gender, team.age_category);
  container.appendChild(card);
}

function updateUpcomingGamesCount() {
  const counter = document.getElementById("upcoming-games-count");

  if (counter) {
    counter.innerText = games.length;
  }
}

function deleteGame(gameId) {
  if (!confirm("Delete this game?")) {
    return;
  }

  fetch("delete_game.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `game_id=${gameId}`,
  })
    .then((res) => res.text())
    .then(() => {
      games = games.filter((g) => g.id != gameId);

      renderGames();
      updateUpcomingGamesCount();
    })
    .catch((err) => console.error(err));
}
