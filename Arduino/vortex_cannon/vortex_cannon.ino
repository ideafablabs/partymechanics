/*
 * IFL Zone +1 Token Registration Code
 * Send the token ID from an NFC or RFID card up to an api endpoint.
 * 
 * @contributors: Jordan Layman, David Van Brink, John Szymanski, Geoff Gnau, Tané Tachyon
 */

#include <SPI.h>

// Onboard Libs
#include <ESPAsyncWebServer.h>
#include <asyncHTTPrequest.h>

#ifdef ESP32
#include <WiFiMulti.h>
#include <ESPmDNS.h>
#include <SPIFFS.h>
#else
#include <ESP8266WiFiMulti.h>
#include <ESP8266mDNS.h>
#include <FS.h>
#endif

// Setup Config.h by duplicating config-sample.h.
#include "config.h"

// Time
long now = 0;

typedef uint32_t nfcid_t; // We treat the NFCs as 4 byte values throughout, for easiest.
long lastRead, successTime = 0;
uint16_t cardreaderPeriod = 10000; // ms
uint16_t successPeriod = 3000; 	// ms

enum requestState {RQ_STANDBY,RQ_PENDING,RQ_SUCCESS,RQ_FAILED};
uint8_t state = RQ_STANDBY;

// Communications
asyncHTTPrequest apiClient;
AsyncWebServer server(80);

#ifdef ESP32
  WiFiMulti wifiMulti;  
#else  
  ESP8266WiFiMulti wifiMulti;
#endif

uint32_t tokenFlavor;

void setup() {

	Serial.begin(115200);
	Serial.println("Henlo!");
	
	pinMode(MOTORPIN, OUTPUT);
	// digitalWrite(MOTORPIN, LOW);
	setupWiFi();
	setupServer();	
	setupClient();

	logAction("Booted Up");

	// printLog();
	// listFiles();
}

void loop() {
	
	// Get time.
	now = millis();
	
	// +-------------------------
	// | Poll the NFC
	static nfcid_t lastID = -1;
	static nfcid_t tokenID = -1;
	// static long holdStartTime,holdTime;

	if (now >= lastRead + cardreaderPeriod) { // Time for next website poll.
  
		if (state == RQ_STANDBY) {
		// continue success notice...
			// getReaderToken(2);
			getVortexActivation();
			// state == RQ_PENDING;			
		}

		lastRead = now;
	}
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
    	String rcode = (String)aReq->responseHTTPcode();
		String rtext = (String)aReq->responseText();
    	logAction(rcode+" "+rtext);

    	// request case here.
    	if (aReq->responseHTTPcode() == 200) {
    		state = RQ_STANDBY;
    		successTime = now;
     		
    		digitalWrite(MOTORPIN, LOW);
    		delay(500);
    		digitalWrite(MOTORPIN, HIGH);

   //   		String r;
			// for(unsigned int i = 0; i<rtext.length(); i++) {
			// 	if (isDigit(rtext[i])) {
			// 		r.concat(rtext[i]);
			// 	}				
			// }
   //  		// if (isDigit(response.charAt(2))) {
   //  			// String r = rtext.replace("\"","dd");
   //  			tokenFlavor = r.toInt();
   //  			// tokenFlavor = aReq->responseText();
   //  			Serial.println(tokenFlavor);
   //  		// }

    	} else {
    		state = RQ_STANDBY;
    		successTime = now;
    	}

    	/// This should probably be json so we can confirm respones types.

      break;
  }
}

void getVortexActivation() {
	
	// String tokenString = String(tokenID);
	String baseURI = API_BASE+API_ENDPOINT + "vortex/";
	String type = "GET";
	String params = "";	

	startAsyncRequest(baseURI,params,type);
}

void getReaderToken(int readerID) {
	
	String baseURI = API_BASE+API_ENDPOINT + "readers/"+readerID;
	String type = "GET";
	String params = "";	

	startAsyncRequest(baseURI,params,type);
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

// Async Setup.
void setupClient() {
	apiClient.setTimeout(5);
	apiClient.setDebug(false);
	apiClient.onReadyStateChange(onClientStateChange);
}

void startAsyncRequest(String request, String params, String type){
    
	logAction(type + " REQUEST: " + request + "?" + params);
    	
	if(apiClient.readyState() == 0 || apiClient.readyState() == 4){ // Only one send at a time here.
		apiClient.open(type.c_str(),request.c_str());		
		if (type == "POST") apiClient.setReqHeader("Content-Type","application/x-www-form-urlencoded");		
		apiClient.send(params);
	} else {
		logAction("Request attempted but busy sending...Try again.");
	}
}

const char* PARAM_MESSAGE = "message";///
void setupServer() {
	
	// Start the file system.
	if(!SPIFFS.begin()){
		Serial.println("An Error has occurred while mounting SPIFFS");
		return;
  	}
	
	// Root / Home
	// server.on("/", HTTP_GET, [](AsyncWebServerRequest *request){
	//      request->send(200, "text/plain", printLog());
	//  });

	server.on("/", HTTP_GET, [](AsyncWebServerRequest *request){
		request->send(200, "text/plain", "fgkldsjklfgjklgfdljk");

		// request->send(SPIFFS, LOG_FILE, "text/plain");
	});

	server.serveStatic("/log/", SPIFFS, LOG_FILE);
   
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

void listFiles() {

	Dir dir = SPIFFS.openDir("/");
	
	Serial.println("Listing SPIFFS Files");
	while (dir.next()) {
		Serial.print("FILE: ");
      Serial.println(dir.fileName());
	   
	   if(dir.fileSize()) {
			File f = dir.openFile("r");
			Serial.println(f.size());
		}
	}
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
