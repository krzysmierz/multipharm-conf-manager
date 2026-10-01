const fs = require("fs");
const http = require("http");
const path = require("path");

const root = path.resolve(__dirname, "..");
const files = {
  "/tests/lineup-layout-fixture.html": path.join(root, "tests/lineup-layout-fixture.html"),
  "/public/css/public.css": path.join(root, "public/css/public.css"),
  "/public/js/event-live-updates.js": path.join(root, "public/js/event-live-updates.js"),
};
const types = { ".css": "text/css", ".html": "text/html", ".js": "application/javascript" };

http.createServer((request, response) => {
  const file = files[new URL(request.url, "http://localhost").pathname];
  if (!file) {
    response.writeHead(404).end();
    return;
  }
  response.writeHead(200, { "Content-Type": `${types[path.extname(file)]}; charset=utf-8` });
  fs.createReadStream(file).pipe(response);
}).listen(4173, "127.0.0.1");
