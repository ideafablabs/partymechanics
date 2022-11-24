const request = require('request');
const { Server } = require('ws');
 
const sockserver = new Server({ port: 443 });
sockserver.on('connection', (ws) => {
   console.log('New client connected!'); 
   ws.on('close', () => console.log('Client has disconnected!'));
});

// Get Recent Users From Database
request('https://mint.ideafablabs.com/index.php/wp-json/mint/v1/users', { json: true }, (err, res, body) => {
  if (err) { return console.log(err); }
  console.log(body);
});
 
setInterval(() => {
   sockserver.clients.forEach((client) => {
       const data = JSON.stringify({'type': 'time', 'time': new Date().toTimeString()});
       client.send(data);
   });
}, 1000);
 
setInterval(() => {
   sockserver.clients.forEach((client) => {
       const messages = ['ESP1', 'ESP2', 'ESP3', 'ESP4', 'ESP5'];
       const random = Math.floor(Math.random() * messages.length);
       let position = {x: Math.floor(Math.random() * 200), y: Math.floor(Math.random() * 150)}
       const data = JSON.stringify({'type': 'message', 'message': messages[random], 'position': position});
       client.send(data);
   });
}, 8000);