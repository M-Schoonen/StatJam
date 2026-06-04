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
