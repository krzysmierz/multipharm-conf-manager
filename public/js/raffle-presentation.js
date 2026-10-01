(function () {
    "use strict";

    var pollInterval = 1000;

    if (window.cmRafflePresentationBound) {
        return;
    }
    window.cmRafflePresentationBound = true;

    function setIcon(component, url) {
        var icon = component.querySelector(".cm-raffle-presentation__icon");
        if (!icon) { return; }
        icon.hidden = !url;
        if (url) { icon.src = url; }
    }

    function setResult(component, winner) {
        var name = component.querySelector(".cm-raffle-presentation__name");
        var status = component.querySelector(".cm-raffle-presentation__status");
        var machine = component.querySelector(".cm-raffle-presentation__machine");
        var button = component.querySelector(".cm-raffle-presentation__draw");

        name.textContent = winner.name;
        setIcon(component, winner.iconUrl);
        machine.classList.add("is-winner");
        status.textContent = "Wylosowano: " + winner.name + ".";
        component.dataset.drawing = "0";
        if (button) {
            button.disabled = false;
            button.textContent = component.dataset.drawAgainMessage;
        }
    }

    function isLiveDraw(draw) {
        return draw && typeof draw.drawId === "string" && draw.drawId.length > 0 &&
            Number.isFinite(Number(draw.startedAt)) && Number.isFinite(Number(draw.duration)) &&
            Array.isArray(draw.participants) && draw.participants.length > 0 &&
            draw.winner && typeof draw.winner.name === "string";
    }

    function animateDraw(component, draw, elapsed) {
        var name = component.querySelector(".cm-raffle-presentation__name");
        var status = component.querySelector(".cm-raffle-presentation__status");
        var machine = component.querySelector(".cm-raffle-presentation__machine");
        var totalElapsed = Math.max(0, elapsed);
        var duration = Number(draw.duration);

        component.dataset.drawing = "1";
        component.dataset.lastDrawId = draw.drawId;
        machine.classList.remove("is-winner");
        status.textContent = component.dataset.drawingMessage;

        function next(delay) {
            // The public candidate pool contains no database identifiers. The
            // separate winner is still the one persisted by the server.
            var candidate = draw.participants[Math.floor(Math.random() * draw.participants.length)];
            name.textContent = candidate.name;
            setIcon(component, candidate.iconUrl);
            totalElapsed += delay;
            if (totalElapsed >= duration) {
                setResult(component, draw.winner);
                return;
            }

            // Quadratic intervals let late-joining screens catch up while
            // preserving the same fast-to-slow presentation.
            var progress = totalElapsed / duration;
            window.setTimeout(function () {
                next(Math.round(45 + 480 * progress * progress));
            }, delay);
        }

        next(Math.max(20, Math.min(45, duration - totalElapsed)));
    }

    function syncLiveDraw(component, draw) {
        if (!isLiveDraw(draw) || String(component.dataset.raffleId) !== String(draw.raffleId)) {
            return;
        }
        if (component.dataset.lastDrawId === draw.drawId) {
            return;
        }

        var elapsed = Date.now() - Number(draw.startedAt);
        component.dataset.lastDrawId = draw.drawId;
        if (elapsed >= Number(draw.duration)) {
            setResult(component, draw.winner);
            return;
        }
        animateDraw(component, draw, elapsed);
    }

    function getErrorMessage(response, fallback) {
        if (response && typeof response.data === "string") {
            return response.data;
        }
        if (response && response.data && typeof response.data.message === "string") {
            return response.data.message;
        }
        return fallback;
    }

    function fetchLiveDraw(raffleId, ajaxUrl) {
        var url = new URL(ajaxUrl, window.location.href);
        url.searchParams.append("action", "cm_get_raffle_live_draw");
        url.searchParams.append("raffle_id", raffleId);

        return window.fetch(url.toString(), {
            method: "GET",
            credentials: "same-origin",
            cache: "no-store"
        })
            .then(function (response) { return response.json(); })
            .then(function (response) {
                return response && response.success && response.data && response.data.active
                    ? response.data.draw
                    : null;
            });
    }

    function pollLiveDraws() {
        var groups = {};
        document.querySelectorAll(".cm-raffle-presentation-component").forEach(function (component) {
            var raffleId = component.dataset.raffleId;
            var ajaxUrl = component.dataset.ajaxUrl;
            if (!raffleId || !ajaxUrl) { return; }
            if (!groups[raffleId]) {
                groups[raffleId] = { ajaxUrl: ajaxUrl, components: [] };
            }
            groups[raffleId].components.push(component);
        });

        Object.keys(groups).forEach(function (raffleId) {
            var group = groups[raffleId];
            fetchLiveDraw(raffleId, group.ajaxUrl)
                .then(function (draw) {
                    if (!draw) { return; }
                    group.components.forEach(function (component) {
                        syncLiveDraw(component, draw);
                    });
                })
                // Passive audience polling stays silent and never changes a draw.
                .catch(function () {});
        });
    }

    document.addEventListener("click", function (event) {
        var button = event.target.closest(".cm-raffle-presentation__draw");
        if (!button) { return; }

        var component = button.closest(".cm-raffle-presentation-component");
        if (!component || component.dataset.canDraw !== "1" || component.dataset.drawing === "1") {
            return;
        }

        var status = component.querySelector(".cm-raffle-presentation__status");
        var machine = component.querySelector(".cm-raffle-presentation__machine");
        var networkError = component.dataset.networkErrorMessage || "Nie udało się połączyć z serwerem. Spróbuj ponownie.";
        button.disabled = true;
        component.dataset.drawing = "1";
        machine.classList.remove("is-winner");
        status.textContent = component.dataset.drawingMessage;

        var request = new URLSearchParams();
        request.append("action", "cm_draw_raffle_presentation");
        request.append("raffle_id", component.dataset.raffleId);
        request.append("nonce", component.dataset.nonce);

        window.fetch(component.dataset.ajaxUrl, {
            method: "POST",
            credentials: "same-origin",
            headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
            body: request.toString()
        })
            .then(function (response) { return response.json(); })
            .then(function (response) {
                var draw = response && response.data ? response.data.liveDraw : null;
                if (!response.success || !isLiveDraw(draw)) {
                    throw new Error(getErrorMessage(response, networkError));
                }
                component.dataset.lastDrawId = "";
                syncLiveDraw(component, draw);
            })
            .catch(function (error) {
                component.dataset.drawing = "0";
                status.textContent = typeof error.message === "string" ? error.message : networkError;
                button.disabled = false;
            });
    });

    // The delegated component handles initial markup and cards inserted by SSE.
    pollLiveDraws();
    window.setInterval(pollLiveDraws, pollInterval);
}());
