class LPdiaDasMaes {
  constructor() {
    this.lastScroll = 0;
    this.init();
  }

  init() {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", () => this.setup());
    } else {
      this.setup();
    }
  }

  setup() {
    if (document.body.classList.contains("lp-mothers-day-body")) {
      this.headerEffects();
    }
  }

  headerEffects() {
    let drawerContent = document.querySelector(".drawerContent");
    let drawerBackground = document.querySelector(".drawerBackground");
    window.addEventListener("scroll", () => {
      const currentScroll = window.scrollY;

      if (currentScroll > this.lastScroll) {
        document.querySelector("header.lp-mothers-day-header").style =
          "box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);background-color: var(--transparent-primary);";
      } else {
        if (currentScroll < 50) {
          document
            .querySelector("header.lp-mothers-day-header")
            .removeAttribute("style");
        }
      }

      this.lastScroll = currentScroll <= 0 ? 0 : currentScroll;
    });

    document
      .querySelector(".btnOpenDrawer")
      .addEventListener("click", function () {
        if (drawerContent.getAttribute("style")) {
          drawerContent.removeAttribute("style");
          drawerBackground.removeAttribute("style");
        } else {
          drawerContent.style = "display:block;width:100%";
          drawerBackground.style = "display:block";
        }
      });
    document.querySelector("#drawerClose").addEventListener("click", () => {
      drawerContent.removeAttribute("style");
      drawerBackground.removeAttribute("style");
    });
  }
}

new LPdiaDasMaes();
