#!/usr/bin/env node
/* Regression: a card inserted into the lineup aside after boot must replay
 * each new live draw, even though the active presentation ID does not change. */
const assert = require("assert");
const fs = require("fs");
const vm = require("vm");

let component = null;
let poll = null;
let requestedUrl = "";
const listeners = {};

const name = { textContent: "" };
const status = { textContent: "" };
const icon = { hidden: true, src: "" };
const machine = { classList: { add() {}, remove() {} } };
const button = { disabled: false, textContent: "" };

function makeComponent() {
    return {
        dataset: {
            raffleId: "2",
            ajaxUrl: "https://example.test/wp-admin/admin-ajax.php",
            drawing: "0",
            drawAgainMessage: "Losuj ponownie",
        },
        querySelector(selector) {
            return {
                ".cm-raffle-presentation__name": name,
                ".cm-raffle-presentation__status": status,
                ".cm-raffle-presentation__icon": icon,
                ".cm-raffle-presentation__machine": machine,
                ".cm-raffle-presentation__draw": button,
            }[selector] || null;
        },
    };
}

global.document = {
    addEventListener(type, listener) { listeners[type] = listener; },
    querySelectorAll(selector) {
        return selector === ".cm-raffle-presentation-component" && component ? [component] : [];
    },
};
global.window = {
    location: { href: "https://example.test/" },
    setInterval(callback) { poll = callback; return 1; },
    setTimeout(callback) { callback(); return 1; },
    fetch(url) {
        requestedUrl = url;
        return Promise.resolve({ json: () => Promise.resolve({ success: true, data: { active: true, draw: liveDraw } }) });
    },
};

let liveDraw;
const presentationScript = fs.readFileSync("public/js/raffle-presentation.js", "utf8");
const eventUpdatesScript = fs.readFileSync("public/js/event-live-updates.js", "utf8");
const shortcodes = fs.readFileSync("includes/class-shortcodes.php", "utf8");
const publicCss = fs.readFileSync("public/css/public.css", "utf8");
assert.match(eventUpdatesScript, /cm-lineup-raffle-draw:not\(\[hidden\]\)/, "the draw holder keeps the aside visible");
assert.match(shortcodes, /function display_event_lineup\(.*?CM_Public::enqueue_raffle_presentation_assets\(\)/s, "a lineup page loads the controller before SSE inserts a draw card");
assert.match(publicCss, /cm-event-lineup-layout__grid--raffle-draw\s*\{\s*grid-template-columns:\s*minmax\(0, 1fr\) minmax\(0, 2fr\)/, "a draw keeps the agenda at one third of the layout");
assert.match(publicCss, /cm-event-lineup-layout__agenda\s*\{\s*container-type:\s*inline-size/, "agenda item layout responds to the agenda width");
assert.match(publicCss, /@container \(max-width: 32rem\)[\s\S]*?\.cm-lineup-item\.current \.cm-lineup-content/, "narrow agenda items stack their content");
vm.runInThisContext(presentationScript, {
    filename: "public/js/raffle-presentation.js",
});

assert.ok(poll, "the live-draw poller starts before a card exists");
component = makeComponent();

function state(drawId, winner) {
    return {
        drawId,
        raffleId: 2,
        startedAt: Date.now() - 5000,
        duration: 1300,
        participants: [{ name: winner, iconUrl: "" }],
        winner: { name: winner, iconUrl: "" },
    };
}

async function flushPoll() {
    poll();
    await new Promise((resolve) => setImmediate(resolve));
}

(async () => {
    liveDraw = state("first-draw", "Alicja");
    await flushPoll();
    assert.match(requestedUrl, /action=cm_get_raffle_live_draw/);
    assert.strictEqual(name.textContent, "Alicja", "a newly inserted aside card replays the active draw");

    liveDraw = state("second-draw", "Bartek");
    await flushPoll();
    assert.strictEqual(name.textContent, "Bartek", "a second draw replays without a presentation-ID change");
    assert.strictEqual(component.dataset.lastDrawId, "second-draw");
    assert.strictEqual(status.textContent, "Wylosowano: Bartek.");
    console.log("OK (aside card receives consecutive live draws)");
})().catch((error) => {
    console.error(error.stack || error);
    process.exitCode = 1;
});
