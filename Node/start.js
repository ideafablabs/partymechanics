const request = require('request');
const { Server } = require('ws');

// JSON response
// current WIFI network 
// IP address
// Clock time
// mDNS address
// data array - last error
// ID - ESP 32 chip id

// Click actions for more info 
// Get log from actions.log() chip serves action.log() 
// Click causes it to reply with data and send log
 
const sockserver = new Server({ port: 6969 });
//Print out local websocket Ip address and port
sockserver.on('listening', () => { console.log(`Listening on ${sockserver.address().address}:${sockserver.address().port}`); });    



sockserver.on('connection', (ws) => {
    // print out unique socket IP address and connection id 
    console.log('New Connection: ' + ws._socket.remoteAddress + ' ' + ws._socket.remotePort);
    
    console.log('New client connected!'); 
    ws.on('close', () => console.log('Client has disconnected!'));
});

// Get Recent Users From Database
const users = (searchState) => {
    request('https://mint.ideafablabs.com/index.php/wp-json/mint/v1/users', { json: true }, (err, res, body) => {
        if (err) { return console.log(err); }
        let users = body
        console.log(users);
    })
};

users()

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
