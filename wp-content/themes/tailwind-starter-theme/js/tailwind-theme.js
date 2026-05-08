class tailwindTheme {
  constructor() {
    this.initMenuToggle();
  }
  initMenuToggle() {
    const toggleButton = document.querySelector(".menu-toggle");
    const menu = document.getElementById("mobile-menu");

    if (!toggleButton || !menu) {
      return;
    }
    
    toggleButton.addEventListener("click", () => {
      const expanded = toggleButton.getAttribute("aria-expanded") === "true";
      menu.classList.toggle("hidden");
      toggleButton.setAttribute("aria-expanded", String(!expanded));
    });
  }
}
document.addEventListener("DOMContentLoaded", () => {
  new tailwindTheme();
});
