/**
 * server.js
 *
 * Local server helper & development entry module.
 * Executes local PHP built-in web server or Node environment runner.
 */

const { spawn } = require('child_process');
const path = require('path');

const PORT = process.env.PORT || 8000;
const HOST = process.env.HOST || '0.0.0.0';

console.log('========================================================');
console.log(` Starting nodexGosolutions Platform Server on http://${HOST}:${PORT}`);
console.log('========================================================');

const phpServer = spawn('php', ['-S', `${HOST}:${PORT}`, '-t', __dirname, 'index.php']);

phpServer.stdout.on('data', (data) => {
    console.log(`[PHP] ${data}`);
});

phpServer.stderr.on('data', (data) => {
    console.error(`[PHP LOG] ${data}`);
});

phpServer.on('close', (code) => {
    console.log(`PHP Server process exited with code ${code}`);
});
