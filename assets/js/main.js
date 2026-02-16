document.addEventListener("DOMContentLoaded", function () {
  // Init AOS
  if (window.AOS)
    AOS.init({ duration: 700, once: true, easing: "ease-out-cubic" });

  // Current year in footer
  var y = document.getElementById("year");
  if (y) y.textContent = new Date().getFullYear();

  // Smooth scroll for in-page links
  document.querySelectorAll('a[href^="#"]').forEach(function (link) {
    link.addEventListener("click", function (e) {
      var targetId = this.getAttribute("href");
      if (targetId.length > 1) {
        var el = document.querySelector(targetId);
        if (el) {
          e.preventDefault();
          el.scrollIntoView({ behavior: "smooth", block: "start" });
        }
      }
    });
  });
  // Password toggle (elements with .pw-toggle and data-target)
  document.querySelectorAll(".pw-toggle").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var sel = this.getAttribute("data-target");
      var input = document.querySelector(sel);
      if (!input) return;
      if (input.type === "password") {
        input.type = "text";
        this.textContent = "🙈";
      } else {
        input.type = "password";
        this.textContent = "👁️";
      }
    });
  });
  var root = document.documentElement;
  var toggle = document.getElementById("theme-toggle");
  var saved = localStorage.getItem("theme");
  var theme = saved === "light" ? "light" : "dark";
  root.setAttribute("data-theme", theme);
  if (toggle) {
    var icon = toggle.querySelector("i");
    if (icon) icon.className = theme === "light" ? "bi bi-sun" : "bi bi-moon";
    toggle.addEventListener("click", function () {
      theme = theme === "light" ? "dark" : "light";
      root.setAttribute("data-theme", theme);
      localStorage.setItem("theme", theme);
      var ic = toggle.querySelector("i");
      if (ic) ic.className = theme === "light" ? "bi bi-sun" : "bi bi-moon";
    });
  }
});

