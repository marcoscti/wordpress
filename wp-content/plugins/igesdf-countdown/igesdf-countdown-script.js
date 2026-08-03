document.addEventListener("DOMContentLoaded", function () {

    if (!SimpleCountdown.start || !SimpleCountdown.end) return;

    function parseLocalDateTime(value) {
        if (!value) return null;

        const [datePart, timePart = "00:00"] = value.split("T");
        const [year, month, day] = datePart.split("-").map(Number);
        const [hour, minute] = timePart.split(":").map(Number);

        return new Date(year, month - 1, day, hour, minute);
    }

    const start = parseLocalDateTime(SimpleCountdown.start);
    const end = parseLocalDateTime(SimpleCountdown.end);

    if (!start || !end || end <= start) return;

    const startTime = start.getTime();
    const endTime = end.getTime();

    let interval = null;

    function update() {
        const now = Date.now();

        // Antes da data de início
        if (now < startTime) {
            return;
        }

        // Após a data final
        if (now >= endTime) {
            clearInterval(interval);

            document.getElementById("sc-days").innerHTML = "00";
            document.getElementById("sc-hours").innerHTML = "00";
            document.getElementById("sc-minutes").innerHTML = "00";
            document.getElementById("sc-seconds").innerHTML = "00";

            return;
        }

        const distance = endTime - now;

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

    if (now >= startTime && now < endTime) {
        interval = setInterval(update, 1000);
    } else if (now < startTime) {
        // Aguarda chegar a data de início
        setTimeout(() => {
            update();
            interval = setInterval(update, 1000);
        }, startTime - now);
    }

});