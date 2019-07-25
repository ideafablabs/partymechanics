
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

// LED Matrix driver
#include <MaxMatrix.h>
#include <avr/pgmspace.h>


const PROGMEM char CH[] = {
3, 8, B00000000, B00000000, B00000000, B00000000, B00000000, // space
1, 8, B01011111, B00000000, B00000000, B00000000, B00000000, // !
3, 8, B00000011, B00000000, B00000011, B00000000, B00000000, // "
5, 8, B00010100, B00111110, B00010100, B00111110, B00010100, // #
4, 8, B00100100, B01101010, B00101011, B00010010, B00000000, // $
5, 8, B01100011, B00010011, B00001000, B01100100, B01100011, // %
5, 8, B00110110, B01001001, B01010110, B00100000, B01010000, // &
1, 8, B00000011, B00000000, B00000000, B00000000, B00000000, // '
3, 8, B00011100, B00100010, B01000001, B00000000, B00000000, // (
3, 8, B01000001, B00100010, B00011100, B00000000, B00000000, // )
5, 8, B00101000, B00011000, B00001110, B00011000, B00101000, // *
5, 8, B00001000, B00001000, B00111110, B00001000, B00001000, // +
2, 8, B10110000, B01110000, B00000000, B00000000, B00000000, // ,
4, 8, B00001000, B00001000, B00001000, B00001000, B00000000, // -
2, 8, B01100000, B01100000, B00000000, B00000000, B00000000, // .
4, 8, B01100000, B00011000, B00000110, B00000001, B00000000, // /
4, 8, B00111110, B01000001, B01000001, B00111110, B00000000, // 0
3, 8, B01000010, B01111111, B01000000, B00000000, B00000000, // 1
4, 8, B01100010, B01010001, B01001001, B01000110, B00000000, // 2
4, 8, B00100010, B01000001, B01001001, B00110110, B00000000, // 3
4, 8, B00011000, B00010100, B00010010, B01111111, B00000000, // 4
4, 8, B00100111, B01000101, B01000101, B00111001, B00000000, // 5
4, 8, B00111110, B01001001, B01001001, B00110000, B00000000, // 6
4, 8, B01100001, B00010001, B00001001, B00000111, B00000000, // 7
4, 8, B00110110, B01001001, B01001001, B00110110, B00000000, // 8
4, 8, B00000110, B01001001, B01001001, B00111110, B00000000, // 9
2, 8, B01010000, B00000000, B00000000, B00000000, B00000000, // :
2, 8, B10000000, B01010000, B00000000, B00000000, B00000000, // ;
3, 8, B00010000, B00101000, B01000100, B00000000, B00000000, // <
3, 8, B00010100, B00010100, B00010100, B00000000, B00000000, // =
3, 8, B01000100, B00101000, B00010000, B00000000, B00000000, // >
4, 8, B00000010, B01011001, B00001001, B00000110, B00000000, // ?
5, 8, B00111110, B01001001, B01010101, B01011101, B00001110, // @
4, 8, B01111110, B00010001, B00010001, B01111110, B00000000, // A
4, 8, B01111111, B01001001, B01001001, B00110110, B00000000, // B
4, 8, B00111110, B01000001, B01000001, B00100010, B00000000, // C
4, 8, B01111111, B01000001, B01000001, B00111110, B00000000, // D
4, 8, B01111111, B01001001, B01001001, B01000001, B00000000, // E
4, 8, B01111111, B00001001, B00001001, B00000001, B00000000, // F
4, 8, B00111110, B01000001, B01001001, B01111010, B00000000, // G
4, 8, B01111111, B00001000, B00001000, B01111111, B00000000, // H
3, 8, B01000001, B01111111, B01000001, B00000000, B00000000, // I
4, 8, B00110000, B01000000, B01000001, B00111111, B00000000, // J
4, 8, B01111111, B00001000, B00010100, B01100011, B00000000, // K
4, 8, B01111111, B01000000, B01000000, B01000000, B00000000, // L
5, 8, B01111111, B00000010, B00001100, B00000010, B01111111, // M
5, 8, B01111111, B00000100, B00001000, B00010000, B01111111, // N
4, 8, B00111110, B01000001, B01000001, B00111110, B00000000, // O
4, 8, B01111111, B00001001, B00001001, B00000110, B00000000, // P
4, 8, B00111110, B01000001, B01000001, B10111110, B00000000, // Q
4, 8, B01111111, B00001001, B00001001, B01110110, B00000000, // R
4, 8, B01000110, B01001001, B01001001, B00110010, B00000000, // S
5, 8, B00000001, B00000001, B01111111, B00000001, B00000001, // T
4, 8, B00111111, B01000000, B01000000, B00111111, B00000000, // U
5, 8, B00001111, B00110000, B01000000, B00110000, B00001111, // V
5, 8, B00111111, B01000000, B00111000, B01000000, B00111111, // W
5, 8, B01100011, B00010100, B00001000, B00010100, B01100011, // X
5, 8, B00000111, B00001000, B01110000, B00001000, B00000111, // Y
4, 8, B01100001, B01010001, B01001001, B01000111, B00000000, // Z
2, 8, B01111111, B01000001, B00000000, B00000000, B00000000, // [
4, 8, B00000001, B00000110, B00011000, B01100000, B00000000, // \ backslash
2, 8, B01000001, B01111111, B00000000, B00000000, B00000000, // ]
3, 8, B00000010, B00000001, B00000010, B00000000, B00000000, // hat
4, 8, B01000000, B01000000, B01000000, B01000000, B00000000, // _
2, 8, B00000001, B00000010, B00000000, B00000000, B00000000, // `
4, 8, B00100000, B01010100, B01010100, B01111000, B00000000, // a
4, 8, B01111111, B01000100, B01000100, B00111000, B00000000, // b
4, 8, B00111000, B01000100, B01000100, B00101000, B00000000, // c
4, 8, B00111000, B01000100, B01000100, B01111111, B00000000, // d
4, 8, B00111000, B01010100, B01010100, B00011000, B00000000, // e
3, 8, B00000100, B01111110, B00000101, B00000000, B00000000, // f
4, 8, B10011000, B10100100, B10100100, B01111000, B00000000, // g
4, 8, B01111111, B00000100, B00000100, B01111000, B00000000, // h
3, 8, B01000100, B01111101, B01000000, B00000000, B00000000, // i
4, 8, B01000000, B10000000, B10000100, B01111101, B00000000, // j
4, 8, B01111111, B00010000, B00101000, B01000100, B00000000, // k
3, 8, B01000001, B01111111, B01000000, B00000000, B00000000, // l
5, 8, B01111100, B00000100, B01111100, B00000100, B01111000, // m
4, 8, B01111100, B00000100, B00000100, B01111000, B00000000, // n
4, 8, B00111000, B01000100, B01000100, B00111000, B00000000, // o
4, 8, B11111100, B00100100, B00100100, B00011000, B00000000, // p
4, 8, B00011000, B00100100, B00100100, B11111100, B00000000, // q
4, 8, B01111100, B00001000, B00000100, B00000100, B00000000, // r
4, 8, B01001000, B01010100, B01010100, B00100100, B00000000, // s
3, 8, B00000100, B00111111, B01000100, B00000000, B00000000, // t
4, 8, B00111100, B01000000, B01000000, B01111100, B00000000, // u
5, 8, B00011100, B00100000, B01000000, B00100000, B00011100, // v
5, 8, B00111100, B01000000, B00111100, B01000000, B00111100, // w
5, 8, B01000100, B00101000, B00010000, B00101000, B01000100, // x
4, 8, B10011100, B10100000, B10100000, B01111100, B00000000, // y
3, 8, B01100100, B01010100, B01001100, B00000000, B00000000, // z
3, 8, B00001000, B00110110, B01000001, B00000000, B00000000, // {
1, 8, B01111111, B00000000, B00000000, B00000000, B00000000, // |
3, 8, B01000001, B00110110, B00001000, B00000000, B00000000, // }
4, 8, B00001000, B00000100, B00001000, B00000100, B00000000, // ~
};



#define count(x)   (sizeof(x) / sizeof(x[0]))

#define PN532_SCK  14
#define PN532_MOSI 13
#define PN532_SS   15
#define PN532_MISO 12
#define PN532_SS2   16


int data = 12;    // 8, DIN pin of MAX7219 module
int load = 4;    // 9, CS pin of MAX7219 module
int clk = 14;  // 10, CLK pin of MAX7219 module

int maxInUse = 8;    //change this variable to set how many MAX7219's you'll use
MaxMatrix m(data, load, clk, maxInUse); // define module
byte buffer[10];

Adafruit_PN532 nfc(PN532_SCK, PN532_MISO, PN532_MOSI, PN532_SS);
Adafruit_PN532 nfc2(PN532_SCK, PN532_MISO, PN532_MOSI, PN532_SS2);

#define PIN 5
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
//const char* ssid = "Idea Fab Labs";
//const char* password = "vortexrings";

const char* ssid = "Rainbow";
const char* password = "Un1c0rn!";

long now,lastBlink,lastRead =0;
uint16_t ledPeriod = 300; // ms
uint16_t cardreaderPeriod = 500; // ms

int LEDPin = LED_BUILTIN;

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

   String postURL= "http://mint.ideafablabs.com/wp-json/mint/v1/quote_pair/";
   String resetParams = "NFC1=00000000&NFC2=00000000";

  long id1, id2;

//  uint32_t colors[] = { 0xFF0000, 0xFFFF00, 0x00FF00, 0x0000FF };
  uint32_t colors[] = { 0x00FF00, 0xFFFF00, 0xFF0000, 0x0000FF, 0xFF00FF, 0xFFFFFF,  }; // Should be G Y R B

  uint8_t color= 100;  // number between 1-255
  uint8_t colorCase= 0;

  const int SCROLL_SPEED = 25;
  String currentQuote, oldQuote;

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

  delay(1000);
  // put your setup code here, to run once:
  Serial.begin(115200);
  Serial.println("Hello!");

  
  nfc.begin();
  delay(500);
  uint32_t versiondata = nfc.getFirmwareVersion();

  delay(500);
  if (! versiondata) {
    Serial.print("Didn't find PN53x board");
    while (1); // halt
  }
  // Got ok data, print it out!
  Serial.print("Found chip PN5"); Serial.println((versiondata>>24) & 0xFF, HEX); 
  Serial.print("Firmware ver. "); Serial.print((versiondata>>16) & 0xFF, DEC); 
  Serial.print('.'); Serial.println((versiondata>>8) & 0xFF, DEC);
  delay(1000);
// BEGIN Board 2 setup
  nfc2.begin();
  delay(500);
   versiondata = nfc2.getFirmwareVersion();
   delay(500);
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


    //delay(500);
    //init the display matrix
   // m.init(); // module initialize
   // m.setIntensity(0); // dot matix intensity 0-15
    //delay(500);

   // currentQuote= " RFID readers setup success.";
    //displayQuote(currentQuote, SCROLL_SPEED);

    //delay(500);

    
    setupWiFi();

   // currentQuote= "   WIFI setup success.";
    //displayQuote(currentQuote, SCROLL_SPEED);

    //delay(500);

    // reset string with test params, 0,0
    // " Find a Friend and Get Your Movie Fortune                "
    currentQuote= testPost(0,0);
    //displayQuote(currentQuote, SCROLL_SPEED);

    //delay(500);
    
  // End of trinket special code
  strip.setBrightness(BRIGHTNESS);
  strip.begin();
  strip.show(); // Initialize all pixels to 'off'

  // init machine state
  machineState = NO_FORTUNE;
  lastMachineState = NO_FORTUNE;



  //currentQuote= fetchQuote();
  
  //currentQuote= "this is a quote from John";
  //displayQuote(currentQuote, SCROLL_SPEED);
}

int step =0;

void loop() {
  // put your main code here, to run repeatedly:
  // do time
  // get time

  
  // Send a theater pixel chase in...
  //theaterChase(strip.Color(127, 127, 127), 50); // White
  //theaterChase(strip.Color(127, 0, 0), 50); // Red
 // theaterChase(strip.Color(0, 0, 127), 50); // Blue
   
   //displayQuote(currentQuote, SCROLL_SPEED);
   now = millis();

   
  
   // do LEDS  ---
  
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
  // end do LEDs

 

  
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

String testPost(long card1, long card2) {
 // https://apitester.com/ is handy for testing.
  Serial.println("Test Post");
  

  
  String nfc1Id = String(card1);
  String nfc2Id = String(card2);
  String requestQuotePair = "http://mint.ideafablabs.com/wp-json/mint/v1/quote_pair/";
  
  String postParams = "NFC1=" + nfc1Id + "&NFC2=" + nfc2Id;
  if ((card1 == 0) && (card2==0))
    postParams = resetParams;
//  Serial.println("calling webRequestPost: " + requestQuotePair + postParams); 
  // we're not rendering the response as a webpage, so send null string
  String responsePage = "";
  String response;
  response = webRequestPost(requestQuotePair, postParams, responsePage); 
  Serial.print("String for display: ");
  Serial.println(response);
  return response;
  
  //delay(1000);
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



void displayQuote(String quote, int scrollSpeed){   
  
  char responseBuffer[quote.length()+1];
  quote.toCharArray(responseBuffer, quote.length()+1);
  printStringWithShift(responseBuffer, scrollSpeed, quote.length());  

}

void printCharWithShift(char c, int shift_speed){
  if (c < 32) return;
  c -= 32;
  memcpy_P(buffer, CH + 7*c, 7);
  m.writeSprite(maxInUse*8, 0, buffer);
  m.setColumn(maxInUse*8 + buffer[0], 0);
  
  for (int i=0; i<buffer[0]+1; i++) 
  {
    delay(shift_speed);
    m.shiftLeft(false, false);
  }
}


void printStringWithShift(char* s, int shift_speed, int charlength){
  Serial.printf("Display string: %s\n", s);
  s++; // remove first quote
  int i=0;
  while ((*s != 0) && (i < charlength-2)){   // charlength removes end quote, 
    printCharWithShift(*s, shift_speed);
    s++;
    i++;
  }
}


//Theatre-style crawling lights.
void theaterChase(uint32_t c, uint8_t wait) {
  for (int j=0; j<10; j++) {  //do 10 cycles of chasing
    for (int q=0; q < 3; q++) {
      for (uint16_t i=0; i < strip.numPixels(); i=i+3) {
        strip.setPixelColor(i+q, c);    //turn every third pixel on
      }
      strip.show();

      delay(wait);

      for (uint16_t i=0; i < strip.numPixels(); i=i+3) {
        strip.setPixelColor(i+q, 0);        //turn every third pixel off
      }
    }
  }
}
