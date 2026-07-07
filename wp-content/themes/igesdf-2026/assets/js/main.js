let deferredPrompt;

window.addEventListener("beforeinstallprompt", (e) => {

    e.preventDefault();

    deferredPrompt = e;

    $("#modalPWA").modal("show");

});

$("#btnInstalarApp").on("click", async function () {

    if (!deferredPrompt)
        return;

    deferredPrompt.prompt();

    await deferredPrompt.userChoice;

    deferredPrompt = null;

});

self.addEventListener('fetch', event => {});
//Função para corrigir o problema do menu do WordPress sobrepondo o menu lateral do tema
document.addEventListener('DOMContentLoaded', function () {
    if (document.querySelector('#wpadminbar')) {
        const navbar = document.querySelector('.offcanvas.offcanvas-start');

        if (navbar) {
            navbar.style.top = '30px';
        }
    }
});
// Função para abrir o submenu
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.menu-item-has-children > a')
        .forEach(function (item) {

            item.addEventListener('click', function (e) {

                e.preventDefault();

                const parent = this.parentElement;

                document.querySelectorAll('.menu-item-has-children.active')
                    .forEach(function (menu) {

                        if (menu !== parent) {
                            menu.classList.remove('active');
                        }

                    });

                parent.classList.toggle('active');

            });

        });

});