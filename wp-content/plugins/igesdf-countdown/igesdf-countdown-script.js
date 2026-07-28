document.addEventListener("DOMContentLoaded", function () {

    if (!SimpleCountdown.end) return;

    const end = new Date(SimpleCountdown.end).getTime();

    function update() {

        const now = new Date().getTime();

        let distance = end - now;

        if (distance < 0)
            distance = 0;

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));

        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));

        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));

        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        document.getElementById("sc-days").innerHTML = String(days).padStart(2, "0");
        document.getElementById("sc-hours").innerHTML = String(hours).padStart(2, "0");
        document.getElementById("sc-minutes").innerHTML = String(minutes).padStart(2, "0");
        document.getElementById("sc-seconds").innerHTML = String(seconds).padStart(2, "0");

    }

    update();

    setInterval(update, 1000);

});