document.addEventListener("DOMContentLoaded", function () {

    if (!SimpleCountdown.start || !SimpleCountdown.end) return;

    const start = new Date(SimpleCountdown.start).getTime();
    const end = new Date(SimpleCountdown.end).getTime();

    let interval = null;

    function update() {
        const now = Date.now();

        // Antes da data de início
        if (now < start) {
            return;
        }

        // Após a data final
        if (now >= end) {
            clearInterval(interval);

            document.getElementById("sc-days").innerHTML = "00";
            document.getElementById("sc-hours").innerHTML = "00";
            document.getElementById("sc-minutes").innerHTML = "00";
            document.getElementById("sc-seconds").innerHTML = "00";

            return;
        }

        const distance = end - now;

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        document.getElementById("sc-days").textContent = String(days).padStart(2, "0");
        document.getElementById("sc-hours").textContent = String(hours).padStart(2, "0");
        document.getElementById("sc-minutes").textContent = String(minutes).padStart(2, "0");
        document.getElementById("sc-seconds").textContent = String(seconds).padStart(2, "0");
    }

    update();

    const now = Date.now();

    if (now >= start && now < end) {
        interval = setInterval(update, 1000);
    } else if (now < start) {
        // Aguarda chegar a data de início
        setTimeout(() => {
            update();
            interval = setInterval(update, 1000);
        }, start - now);
    }

});