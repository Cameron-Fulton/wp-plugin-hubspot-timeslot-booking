// Local dev server with HubSpot API proxy
// Usage: node server.js
const http = require('http');
const https = require('https');
const fs = require('fs');
const path = require('path');

const PORT = 8080;
const HUBSPOT_ORIGIN = 'https://connect.crhormonehealth.com';

const MIME = {
  '.html': 'text/html',
  '.css': 'text/css',
  '.js': 'application/javascript',
  '.json': 'application/json',
  '.svg': 'image/svg+xml',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.ico': 'image/x-icon',
};

const server = http.createServer((req, res) => {
  // Proxy /api/* to api.hubapi.com
  if (req.url.startsWith('/api/')) {
    const target = 'https://api.hubapi.com' + req.url.slice(4);
    const parsed = new URL(target);

    const proxyReq = https.request({
      hostname: parsed.hostname,
      path: parsed.pathname + parsed.search,
      method: req.method,
      headers: {
        'Origin': HUBSPOT_ORIGIN,
        'Referer': HUBSPOT_ORIGIN + '/',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    }, (proxyRes) => {
      res.writeHead(proxyRes.statusCode, {
        'Content-Type': proxyRes.headers['content-type'] || 'application/json',
        'Access-Control-Allow-Origin': '*',
      });
      proxyRes.pipe(res);
    });

    proxyReq.on('error', (err) => {
      res.writeHead(502, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ error: err.message }));
    });

    if (req.method === 'POST' || req.method === 'PUT') {
      req.pipe(proxyReq);
    } else {
      proxyReq.end();
    }
    return;
  }

  // Static file serving
  let filePath = req.url === '/' ? '/index.html' : req.url;
  filePath = path.join(__dirname, filePath);

  const ext = path.extname(filePath);
  const contentType = MIME[ext] || 'application/octet-stream';

  fs.readFile(filePath, (err, data) => {
    if (err) {
      res.writeHead(404);
      res.end('Not found');
      return;
    }
    res.writeHead(200, { 'Content-Type': contentType });
    res.end(data);
  });
});

server.listen(PORT, () => {
  console.log(`Dev server running at http://localhost:${PORT}`);
  console.log('HubSpot API proxied through /api/*');
});
