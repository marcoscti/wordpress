
const isStandalone =
    window.matchMedia("(display-mode: standalone)").matches;

if (!isStandalone) {

    window.addEventListener("beforeinstallprompt", (e) => {

        e.preventDefault();

        deferredPrompt = e;

        const ultimaExibicao =
            localStorage.getItem("pwa-last-show");

        if (ultimaExibicao) {

            const dias =
                (Date.now() - Number(ultimaExibicao)) / 86400000;

            if (dias < 15)
                return;

        }

        setTimeout(() => {

            const modal = new bootstrap.Modal(
                document.getElementById("modalPWA")
            );

            modal.show();

        }, 3000);

    });

}

document
.getElementById("btnInstalarApp")
?.addEventListener("click", async () => {

    if (!deferredPrompt)
        return;

    deferredPrompt.prompt();

    const choice = await deferredPrompt.userChoice;

    console.log(choice.outcome);

    deferredPrompt = null;

});
document
.getElementById("modalPWA")
?.addEventListener("hidden.bs.modal", () => {

    localStorage.setItem(
        "pwa-last-show",
        Date.now()
    );

});