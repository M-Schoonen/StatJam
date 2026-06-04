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
  document.getElementById('backdrop').classList.add('visible');
  document.getElementById('overlay').classList.add('open');
}
function closeOverlay() {
  document.getElementById('backdrop').classList.remove('visible');
  document.getElementById('overlay').classList.remove('open');
}




function loadPlayers(teamId, teamName) {
  const teamsView = document.getElementById('teams-view');
  const playersView = document.getElementById('players-view');

  // fade out teams
  teamsView.style.opacity = 0;

  setTimeout(() => {
    teamsView.style.display = 'none';

    fetch(`?ajax=players&team_id=${teamId}`)
      .then(res => res.json())
      .then(players => {

        let html = `
          <div class="players-header">
            <button onclick="backToTeams()">← Back</button>
            <h2>${teamName} Players</h2>
          </div>
          <div class="players-container">
        `;

        if (players.length === 0) {
          html += `<div class="empty-state">No players found</div>`;
        }

        players.forEach(p => {
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
        playersView.style.display = 'block';

        setTimeout(() => {
          playersView.style.opacity = 1;
        }, 50);

      });

  }, 200);
}

function backToTeams() {
  const teamsView = document.getElementById('teams-view');
  const playersView = document.getElementById('players-view');

  playersView.style.opacity = 0;

  setTimeout(() => {
    playersView.style.display = 'none';
    teamsView.style.display = 'block';

    setTimeout(() => {
      teamsView.style.opacity = 1;
    }, 50);

  }, 200);
}