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
    const dayMs = 1000 * 60 * 60 * 24;
    const hourMs = 1000 * 60 * 60;
    const minuteMs = 1000 * 60;

    let interval = null;

    const dayUnit = document.getElementById("sc-days-wrapper");
    const hourUnit = document.getElementById("sc-hours-wrapper");
    const minuteUnit = document.getElementById("sc-minutes-wrapper");
    const secondUnit = document.getElementById("sc-seconds-wrapper");

    function updateVisibility(distance) {
        if (dayUnit) {
            dayUnit.style.display = "block";
        }

        if (hourUnit) {
            hourUnit.style.display = "block";
        }

        if (minuteUnit) {
            minuteUnit.style.display = "block";
        }

        if (secondUnit) {
            secondUnit.style.display = "block";
        }
    }

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

            updateVisibility(0);

            return;
        }

        const distance = endTime - now;

        const days = Math.floor(distance / dayMs);
        const hours = Math.floor((distance % dayMs) / hourMs);
        const minutes = Math.floor((distance % hourMs) / minuteMs);
        const seconds = Math.floor((distance % minuteMs) / 1000);

        updateVisibility(distance);

        document.getElementById("sc-days").textContent = String(Math.max(days, 0)).padStart(2, "0");
        document.getElementById("sc-hours").textContent = String(Math.max(hours, 0)).padStart(2, "0");
        document.getElementById("sc-minutes").textContent = String(Math.max(minutes, 0)).padStart(2, "0");
        document.getElementById("sc-seconds").textContent = String(Math.max(seconds, 0)).padStart(2, "0");
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