(function () {
    let logoutTimer;
    const TIMEOUT_MS = 120 * 60 * 1000;

    let lastPingTime = 0;
    const PING_INTERVAL = 30 * 1000; // 30 seconds

    function resetTimer() {
        clearTimeout(logoutTimer);
        logoutTimer = setTimeout(() => {
            window.location.href = "/dashboards/logout.php?reason=timeout";
        }, TIMEOUT_MS);

        const now = Date.now();
        if (now - lastPingTime < PING_INTERVAL) {
            return; // throttle ping
        }

        lastPingTime = now;

        if (!window.config || !window.config.csrf_token) {
            console.warn("CSRF token not set yet � skipping ping");
            return;
        }

        fetch("/dashboards/ping.php", {
            method: "POST",
            credentials: "include",
            headers: {
                "X-CSRF-Token": window.config.csrf_token
            }
        }).catch(err => {
            console.warn("Session ping failed:", err);
        });
    }

    document.addEventListener("DOMContentLoaded", () => {
        ['click', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(evt =>
            document.addEventListener(evt, resetTimer)
        );

        resetTimer(); // Start timer
    });
})();