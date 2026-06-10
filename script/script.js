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
}

let currentTeam = null;

function movePill(el) {
  const pill = document.getElementById("nav-pill");
  const nav = el.closest("nav");
  const navRect = nav.getBoundingClientRect();
  const elRect = el.getBoundingClientRect();
  pill.style.left = elRect.left - navRect.left + "px";
  pill.style.width = elRect.width + "px";
}

window.addEventListener("load", () => {
  const active = document.querySelector(".nav-tab.active");
  const pill = document.getElementById("nav-pill");
  pill.style.transition = "none";
  movePill(active);
  requestAnimationFrame(() => {
    pill.style.transition =
      "left 0.35s cubic-bezier(0.4,0,0.2,1), width 0.35s cubic-bezier(0.4,0,0.2,1)";
  });
});

function openOverlay() {
  document.getElementById("backdrop").classList.add("visible");
  document.getElementById("overlay").classList.add("open");
}

function loadPlayers(teamId, teamName, gender, ageCategory) {
  currentTeam = {
    teamId,
    teamName,
    gender,
    ageCategory,
  };

  const teamsView = document.getElementById("teams-view");
  const playersView = document.getElementById("players-view");

  setPlayersHeader(teamId, teamName, gender, ageCategory);

  teamsView.style.opacity = 0;

  setTimeout(() => {
    teamsView.style.display = "none";

    fetch(`?ajax=players&team_id=${teamId}`)
      .then((res) => res.json())
      .then((players) => {
        let html = `
          <div class="players-container">
        `;

        if (players.length === 0) {
          html += `<div class="empty-state">No players found</div>`;
        }

        players.forEach((p) => {
          html += `
            <div class="player-card">
              <div class="player-number">#${p.number}</div>
              <div class="player-name">${p.name}</div>
              <div class="player-position">${p.position}</div>
            </div>
          `;
        });

        html += `</div>`;

        playersView.innerHTML = html;
        playersView.style.display = "block";

        setTimeout(() => {
          playersView.style.opacity = 1;
        }, 50);
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

    setTimeout(() => {
      teamsView.style.opacity = 1;
    }, 50);
  }, 200);
}

function setPlayersHeader(teamId, teamName, gender, ageCategory) {
  document.getElementById("page-title").innerHTML = `
    <span class="back-title" onclick="backToTeams()">
      <svg class="back-icon" width="10" height="16" viewBox="0 0 10 16" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M0 8L8.10811 0L10 1.86667L3.78378 8L10 14.1333L8.10811 16L0 8Z" fill="#F57C00"/>
      </svg>

      <span class="back-text">
        ${teamName} ${gender}${ageCategory}
      </span>
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

function openAddPlayer(teamId) {
  console.log("Add player for team:", teamId);
}

function openAddPlayer(teamId) {
  document.getElementById("player-team-id").value = teamId;

  document.getElementById("backdrop").classList.add("visible");
  document.getElementById("player-overlay").classList.add("open");
}

function closeAllOverlays() {
  document.querySelectorAll(".overlay").forEach((el) => {
    el.classList.remove("open");
  });

  document.getElementById("backdrop").classList.remove("visible");
}

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("player-form");

  if (!form) return;

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(form);

    fetch("save_player.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.text())
      .then(() => {
        closeAllOverlays();

        loadPlayers(
          currentTeam.teamId,
          currentTeam.teamName,
          currentTeam.gender,
          currentTeam.ageCategory,
        );

        form.reset();
      });
  });
});
