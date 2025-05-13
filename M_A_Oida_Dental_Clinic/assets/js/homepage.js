document.addEventListener("DOMContentLoaded", () => {
  // 1) ACTIVE LINK HIGHLIGHT
  const current = window.location.pathname.split("/").pop();
  document.querySelectorAll(".nav-links a").forEach(a => {
    if (a.getAttribute("href") === current) a.classList.add("active");
  });

  // 2) NOTIFICATION TOGGLE
  const bell = document.querySelector(".notification-toggle");
  const notif = document.querySelector(".notification-wrapper");
  if (bell && notif) {
    bell.addEventListener("click", e => {
      e.stopPropagation(); notif.classList.toggle("show");
    });
    document.addEventListener("click", e => {
      if (!notif.contains(e.target)) notif.classList.remove("show");
    });
  }

  // 3) HAMBURGER → X
  const navToggle = document.getElementById("nav-toggle");
  const bar       = document.querySelector(".nav-toggle-label span");
  navToggle.addEventListener("change", () => {
    bar.classList.toggle("open");
  });

  // 4) SMOOTH SCROLL (anchors)
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener("click", e => {
      e.preventDefault();
      document
        .getElementById(anchor.getAttribute("href").slice(1))
        .scrollIntoView({ behavior: "smooth" });
    });
  });
});
