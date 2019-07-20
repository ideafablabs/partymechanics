
/*
 *  Johnszy 
 *  7/20/2019
 *  
 *  This version combines the previous dual Wemos version of the  
 *  movie fortune machine into a single Wemos version
 * 
 * 00 - prove NFC reading works
 * 01 - add WiFi, add more NFC card reading debug messages
 * 02 - add post UID to mint server, caution: bug in readers present
 * 03 - add fortune machine states, add some state change logic,
 *      add animation for reader 1, fix reader logic
 * 04 - start plumbing, minor formatting, add animations for ring2 
 * fortune machine states
 * 0 - no cards - no fortune, show idle loop
 * 1 - one card - request other card
 * 2 - two cards - request fortune
 * 3 - two cards - fortune complete, show fortune
 */

#include <Adafruit_NeoPixel.h>
#include <Wire.h>
#include <SPI.h>
#include <Adafruit_PN532.h>

#include <ESP8266WiFi.h>
#include <ESP8266WiFiMulti.h>
#include <WiFiClient.h>
#include <ESP8266WebServer.h>
#include <ESP8266HTTPClient.h>
#include <ESP8266mDNS.h>

#define count(x)   (sizeof(x) / sizeof(x[0]))

#define PIN 5

#define PN532_SCK  14
#define PN532_MOSI 13
#define PN532_SS   15
#define PN532_MISO 12
#define PN532_SS2   16

Adafruit_PN532 nfc(PN532_SCK, PN532_MISO, PN532_MOSI, PN532_SS);
Adafruit_PN532 nfc2(PN532_SCK, PN532_MISO, PN532_MOSI, PN532_SS2);

#define NUM_LEDS 48
#define BRIGHTNESS 50

Adafruit_NeoPixel strip = Adafruit_NeoPixel(NUM_LEDS, PIN, NEO_GRB + NEO_KHZ800);

int ringSize = 24;
int ring1First = 0;
int ring1Last = (NUM_LEDS / 2) - 1;
int ring2First = (NUM_LEDS / 2);
int ring2Last = NUM_LEDS - 1;

WiFiClient client;
HTTPClient http;

// Replace with your network credentials
const char* ssid = "Idea Fab Labs";
const char* password = "vortexrings";

//const char* ssid = "";
//const char* password = "";

long now,lastBlink,lastRead =0;
uint16_t ledPeriod = 300; // ms
uint16_t cardreaderPeriod = 500; // ms

//int LEDPin = LED_BUILTIN;

byte neopix_gamma[] = {
    0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,
    0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  1,  1,  1,  1,
    1,  1,  1,  1,  1,  1,  1,  1,  1,  2,  2,  2,  2,  2,  2,  2,
    2,  3,  3,  3,  3,  3,  3,  3,  4,  4,  4,  4,  4,  5,  5,  5,
    5,  6,  6,  6,  6,  7,  7,  7,  7,  8,  8,  8,  9,  9,  9, 10,
   10, 10, 11, 11, 11, 12, 12, 13, 13, 13, 14, 14, 15, 15, 16, 16,
   17, 17, 18, 18, 19, 19, 20, 20, 21, 21, 22, 22, 23, 24, 24, 25,
   25, 26, 27, 27, 28, 29, 29, 30, 31, 32, 32, 33, 34, 35, 35, 36,
   37, 38, 39, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 50,
   51, 52, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 66, 67, 68,
   69, 70, 72, 73, 74, 75, 77, 78, 79, 81, 82, 83, 85, 86, 87, 89,
   90, 92, 93, 95, 96, 98, 99,101,102,104,105,107,109,110,112,114,
  115,117,119,120,122,124,126,127,129,131,133,135,137,138,140,142,
  144,146,148,150,152,154,156,158,160,162,164,167,169,171,173,175,
  177,180,182,184,186,189,191,193,196,198,200,203,205,208,210,213,
  215,218,220,223,225,228,231,233,236,239,241,244,247,249,252,255 };

  uint8_t machineState, lastMachineState;
  boolean stateChanged;
  const uint8_t NO_FORTUNE = 0;
  const uint8_t REQUEST_CARD = 1;
  const uint8_t REQUEST_FORTUNE = 2;
  const uint8_t FORTUNE_COMPLETE = 3;
  
  boolean success;
  uint8_t uid[] = { 0, 0, 0, 0, 0, 0, 0 };  // Buffer to store the returned UID
  uint8_t uidLength;        // Length of the UID (4 or 7 bytes depending on ISO14443A card type)
  uint8_t uid2[] = { 0, 0, 0, 0, 0, 0, 0 };  // Buffer to store the returned UID
  uint8_t uid2Length;        // Length of the second UID (4 or 7 bytes depending on ISO14443A card type)
  boolean foundCard1, foundCard2 = false;

  long id1, id2;

//  uint32_t colors[] = { 0xFF0000, 0xFFFF00, 0x00FF00, 0x0000FF };
  uint32_t colors[] = { 0x00FF00, 0xFFFF00, 0xFF0000, 0x0000FF, 0xFF00FF, 0xFFFFFF,  }; // Should be G Y R B

  uint8_t color= 100;  // number between 1-255
  uint8_t colorCase= 0;



void setupWiFi() {
  //DO NOT TOUCH
    //  This is here to force the ESP32 to reset the WiFi and initialise correctly.
    Serial.print("WIFI status = ");
    Serial.println(WiFi.getMode());
    WiFi.disconnect(true);
    delay(1000);
    WiFi.mode(WIFI_STA);
    delay(1000);
    Serial.print("WIFI status = ");
    Serial.println(WiFi.getMode());
    // End silly stuff !!!

   // Connect to provided SSID and PSWD
    WiFi.begin(ssid, password);

 // WiFi.mode(WIFI_STA);
 // WiFiMulti.addAP(ssid, password);
 
  // Wait for connection
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("");
  Serial.print("Connected to ");
  Serial.println(ssid);
  Serial.print("IP address: ");
  Serial.println(WiFi.localIP());
}


void setup() {
  // put your setup code here, to run once:
  Serial.begin(115200);
  Serial.println("Hello!");
  nfc.begin();
  
  uint32_t versiondata = nfc.getFirmwareVersion();
  if (! versiondata) {
    Serial.print("Didn't find PN53x board");
    while (1); // halt
  }
  // Got ok data, print it out!
  Serial.print("Found chip PN5"); Serial.println((versiondata>>24) & 0xFF, HEX); 
  Serial.print("Firmware ver. "); Serial.print((versiondata>>16) & 0xFF, DEC); 
  Serial.print('.'); Serial.println((versiondata>>8) & 0xFF, DEC);

// BEGIN Board 2 setup
  nfc2.begin();
  
   versiondata = nfc2.getFirmwareVersion();
  if (! versiondata) {
    Serial.print("Didn't find PN53x board 2");
    while (1); // halt
  }
  // Got ok data, print it out!
  Serial.print("Found chip PN5 on board 2"); Serial.println((versiondata>>24) & 0xFF, HEX); 
  Serial.print("Firmware board 2 ver. "); Serial.print((versiondata>>16) & 0xFF, DEC); 
  Serial.print('.'); Serial.println((versiondata>>8) & 0xFF, DEC);

  // Set the max number of retry attempts to read from a card
  // This prevents us from waiting forever for a card, which is
  // the default behaviour of the PN532.
  nfc.setPassiveActivationRetries(0x01);
  nfc2.setPassiveActivationRetries(0x01);
  // configure board to read RFID tags
  nfc.SAMConfig();
  nfc2.SAMConfig();
  Serial.println("Readers ready.  Waiting for an ISO14443A card...");

  setupWiFi();

  // End of trinket special code
  strip.setBrightness(BRIGHTNESS);
  strip.begin();
  strip.show(); // Initialize all pixels to 'off'

  // init machine state
  machineState = NO_FORTUNE;
  lastMachineState = NO_FORTUNE;
}

int step =0;

void loop() {
  // put your main code here, to run repeatedly:
  // do time
  // get time
   now = millis();
  
   // do LEDS
  if (now >= lastBlink + ledPeriod) {
    if (foundCard1 && foundCard2) {
      // both cards on reader
      for(int i=0; i<NUM_LEDS; i++){
        strip.setPixelColor(i, 0xFF00FF);
      }
    } else if (foundCard1) {
      // card1 on reader
      if (stateChanged) {
        // clear ring1 if card1 was just placed
        for(int i=ring1First; i<ring1Last+1; i++){
          strip.setPixelColor(i, 0x000000);
        }        
      }
      id1 = id(uid);
      // progress meter animation color based on uid
      uint32_t c = colors[getFlavor(id1)];
      for(int i=ring1First; i<ring1Last+1; i++){
        strip.setPixelColor(step % NUM_LEDS/2, c);
      }
      // ring 2 idle animation
      for(int i=ring2First; i<ring2Last+1; i++){
        strip.setPixelColor(i,0);
      }
      strip.setPixelColor(step % (NUM_LEDS/2) + ringSize, 0xFF00FF);
    } else if (foundCard2) {
      // card 2 on reader
      if (stateChanged) {
        // clear the strip if the card was just placed
        for(int i=ring2First; i<ring2Last+1; i++){
          strip.setPixelColor(i, 0x000000);
        }        
      }
      id2 = id(uid2);
      // progress meter animation color based on uid
      uint32_t c = colors[getFlavor(id2)];
      for(int i=ring2First; i<ring2Last+1; i++){
        strip.setPixelColor(step % (NUM_LEDS/2) + ringSize, c);
      }
      // ring 1 idle animation
      for(int i=ring1First; i<ring1Last+1; i++){
        strip.setPixelColor(i,0);
      }
      strip.setPixelColor(step % (NUM_LEDS/2), 0xFF00FF);
    } else {
      // no cards on reader
      for(int i=0; i<NUM_LEDS; i++){
        strip.setPixelColor(i,0);
      }
      strip.setPixelColor(step % (NUM_LEDS/2), 0xFF00FF);
      strip.setPixelColor(step % (NUM_LEDS/2) + ringSize, 0xFF00FF);
    }

    // Let the magic happen.
    strip.show();
 
    //update step
    step++;

    // update timer
    lastBlink = now;
  }
  // do LEDs
  // do card read
  if (now >= lastRead + cardreaderPeriod) {
    Serial.print(machineState);
//    Serial.print(".");
    foundCard1 = nfc.readPassiveTargetID(PN532_MIFARE_ISO14443A, &uid[0], &uidLength,100);
    foundCard2 = nfc2.readPassiveTargetID(PN532_MIFARE_ISO14443A, &uid2[0], &uid2Length,100);

    if (foundCard1 && foundCard2) {
      if (machineState != FORTUNE_COMPLETE) {
        machineState = REQUEST_FORTUNE;
        stateChanged = true;
      }
      if (stateChanged) {
        Serial.println("\nCard pair detected.");
        Serial.printf("Reader 1 detected card UID: %d\n", id(uid));
        Serial.printf("Reader 2 detected card UID: %d\n", id(uid2));        
      }
      if (machineState == REQUEST_FORTUNE) {
        // color readers red while performing post
        for(int i=0; i<NUM_LEDS; i++){
          strip.setPixelColor(i, 0xFF0000);
        }
        strip.show();
        // post uids
        testPost(id(uid), id(uid2));
        // color readers green once post is complete
        for(int i=0; i<NUM_LEDS; i++){
          strip.setPixelColor(i, 0x0000FF);
        }
        strip.show();
        machineState = FORTUNE_COMPLETE;
        stateChanged = true;        
      }
    }
    else if (foundCard1 == true) {
      if (stateChanged) {
        Serial.print("\nReader 1 detected card UID: ");
        Serial.println(id(uid));
      }
      machineState = REQUEST_CARD;
    }
    else if (foundCard2 == true) {
      if (stateChanged) {
        Serial.print("\nReader 2 detected card UID: ");
        Serial.println(id(uid2));
      }
      machineState = REQUEST_CARD;
    }
    else {
      if (stateChanged) {
        Serial.println("\nNo cards detected");
      }
      machineState = NO_FORTUNE;
    }

    if (lastMachineState != machineState) {
      Serial.printf("\nMachine state change from %d to %d\n", lastMachineState, machineState);
      stateChanged = true;
    } else {
      stateChanged = false;
    }
    lastRead = now;
    lastMachineState = machineState;
  }
  // do wifi
}

void testPost(long card1, long card2) {
 // https://apitester.com/ is handy for testing.
  Serial.println("Test Post");
  String nfc1Id = String(card1);
  String nfc2Id = String(card2);
  String requestQuotePair = "http://mint.ideafablabs.com/wp-json/mint/v1/quote_pair/";
  String postParams = "NFC1=" + nfc1Id + "&NFC2=" + nfc2Id;
//  Serial.println("calling webRequestPost: " + requestQuotePair + postParams); 
  // we're not rendering the response as a webpage, so send null string
  String responsePage = "";
  String response;
  response = webRequestPost(requestQuotePair, postParams, responsePage); 
  Serial.print("String for display: ");
  Serial.println(response);
  delay(1000);
}

String webRequestPost(String request, String params, String page) {
    String response;
    Serial.println("POST " + request + params);

//    digitalWrite(LEDPin, on);

    Serial.print("[HTTP] begin...\n");
    if (http.begin(client, request)) {  // HTTP
      Serial.print("[HTTP] POST...\n");
      // add headers
      http.addHeader("Content-Type", "application/x-www-form-urlencoded");
      // start connection and send HTTP header
      int httpCode = http.POST(params);
      // httpCode will be negative on error
      if (httpCode > 0) {
        // HTTP header has been send and Server response header has been handled
        Serial.printf("[HTTP] POST... code: %d\n", httpCode);

        // file found at server
        Serial.printf("Payload: ");
        if (httpCode == HTTP_CODE_OK || httpCode == 201 || httpCode == HTTP_CODE_MOVED_PERMANENTLY) {
          response = http.getString();
          Serial.println(response);
        }
      } else {
        response = printf("Error: %s", http.errorToString(httpCode).c_str());
        Serial.printf("[HTTP] POST... failed, error: %s\n", http.errorToString(httpCode).c_str());
      }
      http.end();
    } else {
      Serial.printf("[HTTP} Unable to connect\n");
    }
//    server.send(200, "text/html", page);
//
//    String clientIP;
//    clientIP = server.client().remoteIP().toString();
//    Serial.print("IP: ");
//    Serial.println(clientIP);

//    digitalWrite(LEDPin, off);
    return response;
}

void checkForCard() {

}

//   
void checkForCard2() {

}

//uint8_t getFlavor(long idcode) {
//  switch (idcode % 10) {
//      case 0:
//        return 0;
//        break;
//      case 2:
//        return 1;
//        // do something
//        break;
//      case 4:
//        return 2;
//        break;
//      case 6:
//        return 3;
//        break;
//      case 8:
//        return 4;
//        break;  
//      default:
//        return 5;
//        // do something
//  }
//}

uint8_t getFlavor(long uid) 
//uint8_t getFlavor(nfcid_t uid) 
{
  // we think idcode is always even... 
  // this is mostly because we read the little-endian id as if it were
  // big-endian and are getting kinda lucky. But this /2 mod5 thing works so ok for now. dvb 2019.
  uid /= 2;
  int flavor = uid % 5;
  return flavor;
}

//Show real number for uid
long id(uint8_t bins[]) {
 uint32_t c;
 c = bins[0];
 for (int i=1;i<count(bins);i++){
   c <<= 8;
   c |= bins[i];
 }
 return c;
}

// Input a value 0 to 255 to get a color value.
// The colours are a transition r - g - b - back to r.
uint32_t Wheel(byte WheelPos) {
  WheelPos = 255 - WheelPos;
  if(WheelPos < 85) {
    return strip.Color(255 - WheelPos * 3, 0, WheelPos * 3,0);
  }
  if(WheelPos < 170) {
    WheelPos -= 85;
    return strip.Color(0, WheelPos * 3, 255 - WheelPos * 3,0);
  }
  WheelPos -= 170;
  return strip.Color(WheelPos * 3, 255 - WheelPos * 3, 0,0);
}

uint8_t red(uint32_t c) {
  return (c >> 16);
}
uint8_t green(uint32_t c) {
  return (c >> 8);
}
uint8_t blue(uint32_t c) {
  return (c);
}
