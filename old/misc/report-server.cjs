const http = require('http');
const fs = require('fs');
const path = require('path');

const PORT = process.env.PORT || 9323;
const REPORT_DIR = './playwright-report';

const server = http.createServer((req, res) => {
    let filePath = path.join(REPORT_DIR, req.url === '/' ? 'index.html' : req.url);
    const extname = path.extname(filePath);
    
    let contentType = 'text/html';
    if (extname === '.js') contentType = 'text/javascript';
    if (extname === '.css') contentType = 'text/css';
    if (extname === '.json') contentType = 'application/json';
    if (extname === '.png') contentType = 'image/png';
    if (extname === '.jpg' || extname === '.jpeg') contentType = 'image/jpeg';
    if (extname === '.gif') contentType = 'image/gif';
    if (extname === '.webm') contentType = 'video/webm';
    if (extname === '.mp4') contentType = 'video/mp4';
    
    fs.readFile(filePath, (err, content) => {
        if (err) {
            res.writeHead(404, { 'Content-Type': 'text/html' });
            res.end('<h1>404 Not Found</h1>', 'utf-8');
        } else {
            res.writeHead(200, { 'Content-Type': contentType });
            res.end(content, 'utf-8');
        }
    });
});

server.listen(PORT, '0.0.0.0', () => {
    console.log(`📊 Playwright レポート サーバー起動: http://localhost:${PORT}`);
});
