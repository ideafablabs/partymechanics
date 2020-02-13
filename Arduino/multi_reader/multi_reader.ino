/*
 * IFL Zone +1 Token Registration Code
 * Send the token ID from an NFC or RFID card up to an api endpoint.
 * 
 * @contributors: Jordan Layman, David Van Brink, John Szymanski, Geoff Gnau, Tané Tachyon
 */

#include <SPI.h>
#include <Adafruit_PN532.h>
#include <Adafruit_NeoPixel.h>

// Onboard Libs
#include "libraries/ESPAsyncWebServer/ESPAsyncWebServer.h"
#include "libraries/asyncHTTPrequest/asyncHTTPrequest.h"
#include "libraries/Adafruit_PN532/Adafruit_PN532.h"

#include <ESP8266WiFiMulti.h>
#include <ESP8266mDNS.h>
#include <FS.h>

// Setup Config.h by duplicating config-sample.h.
#include "config.h"

// Time
long now = 0;

typedef uint32_t nfcid_t; // We treat the NFCs as 4 byte values throughout, for easiest.

class NFCReader {

	public:
	
	Adafruit_PN532* nfc;
	long holdTime,holdStartTime = 0;

	nfcid_t tokenID,lastID = -1;

	NFCReader(uint8_t SCK, uint8_t MISO, uint8_t MOSI, uint8_t SS) {
		this->nfc = new Adafruit_PN532(SCK, MISO, MOSI, SS);
		// this->nfc = new Adafruit_PN532(SS);
	}

	bool begin() {
		this->nfc->begin();

		uint32_t versiondata = this->nfc->getFirmwareVersion();
		if (! versiondata) {
			return false;	    	
		}

		// Set the max number of retry attempts to read from a card		
		this->nfc->setPassiveActivationRetries(0x01);

		// Configure board to read RFID tags
		this->nfc->SAMConfig();		

		return true;
	}

	nfcid_t run() {

		this->tokenID = pollNfc();
		return this->tokenID;
	 	
	 // 	if (this->tokenID != this->lastID) { // Detect change in card.
	 			 		
	 // 		if (tokenID1 != 0){ // Card found.
		// 		logAction("Reader detected tokenID: " + (String)tokenID1);

		// 		// Reader state becomes active.
		// 		this->holdStartTime = now;
		// 		Serial.printf("Hold start time: %d", this->holdStartTime);

		// 		// Do initial hold action.
		// 		// if (READER_ID) {									
		// 		// 	registerToken(tokenID, READER_ID);
		// 		// } else {
		// 		// 	plusOneZone(tokenID, ZONE_ID);
		// 		// }

		// 	} else { // Card was removed.
		// 		Serial.println("Card Removed.");
				
		// 		// Reader state becomes inactive.
		// 		holdTime1 = 0;				
		// 	}
		// 	lastID1 = tokenID1;
		// } else {
			
		// 	if (tokenID1 != 0) {
				
		// 		// Increase hold timer.
		// 		holdTime1 = now - holdStartTime1;
				
		// 		// Do longer hold actions.
		// 		if (holdTime1 > 5000) {
		// 		    Serial.println("Held for 5s");
		// 		}
		// 	}
		// }
	}

	// Return the 64 bit uid, with ZERO meaning nothing presently detected.
	nfcid_t pollNfc() {
		uint8_t uidBytes[8] = {0};
		uint8_t uidLength;
		nfcid_t uid = 0;

		// Check for card
		int foundCard = this->nfc->readPassiveTargetID(PN532_MIFARE_ISO14443A, &uidBytes[0], &uidLength, 100);

		if (foundCard) {		
			uidLength = 4; // it's little-endian endian, the lower four are enough, and all we can use on this itty bitty cpu. ///magic numbers
		 	
		 	// Unspool the bins right here.
		 	for (int ix = 0; ix < uidLength; ix++)
				uid = (uid << 8) | uidBytes[ix];
		}

		return uid;
	}
};

/// Make Vector
NFCReader readers[] = {
	NFCReader(PN532_SCK, PN532_MISO, PN532_MOSI, PN532_SS1)
	// , NFCReader(PN532_SCK, PN532_MISO, PN532_MOSI, PN532_SS2)
};

long lastRead = 0;
uint16_t cardreaderPeriod = 500; // ms

// LED
Adafruit_NeoPixel strip = Adafruit_NeoPixel(NUM_LEDS, LEDPIN, NEO_GRB + NEO_KHZ800);
int step = 0 ;
long lastBlink = 0;
const uint16_t ledPeriod = 40; // ms 24fps
uint32_t tokenColors[] = { 0x00FF00, 0xFFFF00, 0xFF0000, 0x0000FF, 0xFF00FF, 0xFFFFFF,  }; // Should be G Y R B (& secret purple)

// Communications
ESP8266WiFiMulti wifiMulti;
asyncHTTPrequest apiClient;
AsyncWebServer server(80);

void setup() {

	Serial.begin(115200);
	Serial.println("Hello!");

	// LED Launch.
	strip.setBrightness(BRIGHTNESS);
	strip.begin();

	showAll(0xFF0000);

	setupNFC();	

	showAll(0x0000FF);

	setupWiFi();

	showAll(0x00FF00);

	setupServer();
	
	setupClient();

	logAction("Booted Up");

}

nfcid_t tokenIDs[READER_COUNT];

void loop() {
	
	// Get time.
	now = millis();
	
	// +-------------------------
	// | Poll the NFC
	// static nfcid_t lastID = -1;
	// static nfcid_t tokenID = -1;
	// static long holdStartTime,holdTime;

	static int pollCount = 0; // just for printing the poll dots.
	char pollChar = '.'; // dots for no read, + for active.

	if (now >= lastRead + cardreaderPeriod) { // Time for next card poll.
  	
  		for(int i=0; i<READER_COUNT; i++){
  		   tokenIDs[i] = readers[i].run();

  		   if (tokenIDs[i]) {
				Serial.print("Reader: "+(String)i);
				Serial.println((String)tokenIDs[i]);
  		   }
  		}

  		if (pollCount % 20 == 0)  // so the dots dont scroll right forever.
		Serial.printf("\n%4d ", pollCount);
		pollCount++;
		Serial.print(pollChar);

		lastRead = now;
	}

	// +-------------------------
	// | Do LEDs.	
	// if (now >= lastBlink + ledPeriod) {
		
	// 	// Clear pixels.
	// 	for(int i=0; i<NUM_LEDS; i++){
	// 		strip.setPixelColor(i, 0);
	// 	}

	// 	// Default color.
	// 	uint32_t c = 0x00FFFF;	
		
	// 	// if reader is being held.
	// 	if (holdTime1) {
	// 		c = tokenColors[getTokenColor(tokenID1)];
	// 	} 

	// 	uint8_t colormin = 10;
	// 	uint8_t colormax = 100-colormin;
	// 	// Color wave
	// 	uint8_t a = step % colormax;
	// 	if (a > colormax/2) a = colormax - a;
			
	// 	for(int i=0; i<NUM_LEDS; i++){				
	// 		strip.setPixelColor(i, alpha(c,colormin+a));
	// 	}

	// 	// Let the magic happen.
	// 	strip.show();
 
	// 	// Update step.
	// 	step++;

	// 	// Update timer.
	// 	lastBlink = now;
	// }
}

// we think idcode is always even...
// this is mostly because we read the little-endian id as if it were
// big-endian and are getting kinda lucky. But this /2 mod5 thing works so ok for now. dvb 2019.
uint8_t getTokenColor(nfcid_t uid)
{
	uid /= 2;
	int flavor = uid % 5;
	return flavor;
}

// Helpers to extract RGB from 32bit color.
uint8_t extractRed(uint32_t c) { return (( c >> 16 ) & 0xFF); } 
uint8_t extractGreen(uint32_t c) { return ( (c >> 8) & 0xFF ); } 
uint8_t extractBlue(uint32_t c) { return ( c & 0xFF ); }

uint32_t rgba(byte r, byte g, byte b, int a) {
  
  int rr = (r*a)/100;
  int gg = (g*a)/100;
  int bb = (b*a)/100;

  return strip.Color(rr,gg,bb);
}

uint32_t alpha(uint32_t c, int a) {
  
  uint8_t r = extractRed(c);
  uint8_t g = extractGreen(c);
  uint8_t b = extractBlue(c);
    
  return rgba(r,g,b,a);
}

// https://github.com/boblemaire/asyncHTTPrequest
// https://stackoverflow.com/questions/54820798/how-to-receive-json-response-from-rest-api-using-esp8266-arduino-framework
void onClientStateChange(void * arguments, asyncHTTPrequest * aReq, int readyState) {
  
  switch (readyState) {
    case 0: // readyStateUnsent: Client created, open not yet called.
      break;

    case 1: // readyStateOpened: open() has been called, connected    	
      break;

    case 2: // readyStateHdrsRecvd: send() called, response headers available
      break;

    case 3:	// readyStateLoading: receiving, partial data available
      break;

    case 4: // readyStateDone: Request complete, all data available.

    	// Log Response.
    	/// We might want to store the response and check syncronously so log doesn't get chunked.
    	logAction(aReq->responseHTTPcode()+" "+aReq->responseText());

    	/// This should probably be json so we can confirm respones types.

      break;
  }
}

void plusOneZone(long tokenID, int zoneID) {
 
	String tokenString = String(tokenID);		
	String baseURI = API_BASE+API_ENDPOINT + "zones/"+zoneID;
	String params = "token_id=" + tokenString;	
	
	startAsyncRequest(baseURI,params,"POST");
}

void registerToken(long tokenID, int readerID) {
	
	String tokenString = String(tokenID);
	String baseURI = API_BASE+API_ENDPOINT + "reader/";
	String params = "token_id=" + tokenString;	

	startAsyncRequest(baseURI,params,"POST");
}

// Wifi Setup.
void setupWiFi() {
	WiFi.mode(WIFI_STA);
	
	wifiMulti.addAP(SSID1, PASSWORD1);
	wifiMulti.addAP(SSID2, PASSWORD2);

	Serial.print("Wifi Connecting.");
	while (wifiMulti.run() != WL_CONNECTED) {
		Serial.print(".");
		delay(1000);
	} 
	
	logAction("WiFi connected to SSID: '"+WiFi.SSID()+"' @ "+WiFi.localIP().toString());
}

void setupNFC() {

	for(int i=0; i<READER_COUNT; i++){
		
		if (!readers[i].begin()) {
			ESP.restart();
		}

		// Serial.println("NFC " + i + " ready.");
		
	}
}

// Async Setup.
void setupClient() {
	apiClient.setTimeout(5);
	apiClient.setDebug(false);
	apiClient.onReadyStateChange(onClientStateChange);
}

void startAsyncRequest(String request, String params, String type){
    
	logAction(type + " REQUEST: " + request + "?" + params);
    
	if(apiClient.readyState() == 0 || apiClient.readyState() == 4){		
		apiClient.open(type.c_str(),request.c_str());
		if (type == "POST") apiClient.setReqHeader("Content-Type","application/x-www-form-urlencoded");
		apiClient.send(params);	
	}
}

const char* PARAM_MESSAGE = "message";///
void setupServer() {
	
	// Start the file system.
	SPIFFS.begin();
	
	// Root / Home
	server.on("/", HTTP_GET, [](AsyncWebServerRequest *request){
	     request->send(200, "text/plain", printLog());
	 });

	// server.on("/log/", HTTP_GET, [](AsyncWebServerRequest *request){
	// 	request->send(200, "text/plain", printLog());
	// 	// request->send(SPIFFS, "/"+LOG_FILE, "text/plain");
	// });	

	// server.on("/log/flush", HTTP_GET, [](AsyncWebServerRequest *request){
	// 	flushLog();
	// 	request->send(200, "text/plain", "Log flushed.");
	// 	// request->send(SPIFFS, "/"+LOG_FILE, "text/plain");
	// });	

	// // DEMO
	// // Send a GET request to <IP>/get?message=<message>
	// server.on("/get", HTTP_GET, [] (AsyncWebServerRequest *request) {
	//     String message;
	//     if (request->hasParam(PARAM_MESSAGE)) {
	//         message = request->getParam(PARAM_MESSAGE)->value();
	//     } else {
	//         message = "No message sent";
	//     }
	//     request->send(200, "text/plain", "Hello, GET: " + message);
	// });

	// // DEMO
 //   // Send a POST request to <IP>/post with a form field message set to <message>
 //   server.on("/post", HTTP_POST, [](AsyncWebServerRequest *request){
 //       String message;
 //       if (request->hasParam(PARAM_MESSAGE, true)) {
 //           message = request->getParam(PARAM_MESSAGE, true)->value();
 //       } else {
 //           message = "No message sent";
 //       }
 //       request->send(200, "text/plain", "Hello, POST: " + message);
 //   });
   
   server.onNotFound(notFound);
  
   server.begin();

   // Start the mDNS responder
   if (!MDNS.begin(DNS_NAME)) { 
		Serial.println("Error setting up MDNS responder!");
	} else {
		Serial.println("DNS Name: " + DNS_NAME);
	}
}
void notFound(AsyncWebServerRequest *request) {
    request->send(404, "text/plain", "Connected but not found");
}


// Count Macro.
#define count(x) (sizeof(x) / sizeof(x[0]))

//Show real number for tokenID.
long id(uint8_t bins[]) {
	uint32_t c;
	c = bins[0];
	for (int i=1;i<count(bins);i++){
		c <<= 8;
		c |= bins[i];
	}
	return c;
}
// === LED Functions ===
void showAll(uint32_t color) {
	for(int i=0; i<NUM_LEDS; i++){
	    strip.setPixelColor(i,color);
	}
	strip.show();
}

// === Log Functions ===
String printLog() {
	
	int xCnt = 0;
	String output;
	
	File f = SPIFFS.open(LOG_FILE, "r");
	
	if (!f) {
		output = "file open failed";
		return output;
	}

	output = "====== Reading from LOG_FILE =======";

	while(f.available()) {
	   //Lets read line by line from the file
	   String line = f.readStringUntil('\n');
	   // Serial.print(xCnt);
	   // Serial.print("  ");
	   // Serial.println(line);
	   output += xCnt + "  " + line + "\n";

	   xCnt ++;
	}
	f.close();    

	return output;
}

void flushLog() {
	File f = SPIFFS.open(LOG_FILE, "w");	
		f.printf("%s %s: ", __DATE__, __TIME__);
		f.println("Begin Log");
	f.close();
}

void logAction(String actionString) {
    
	Serial.printf("\n%s %s: ", __DATE__, __TIME__);
	Serial.println(actionString);

	char* mode = "a";

	// if not exists, create using W, other wise append with A
	if (!SPIFFS.exists(LOG_FILE)) mode = "w";

	File f = SPIFFS.open(LOG_FILE, mode);			
		f.printf("%s %s: ", __DATE__, __TIME__);
		f.println(actionString);
	f.close();
    
}
