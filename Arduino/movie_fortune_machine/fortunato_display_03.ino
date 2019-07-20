/*
 * 00 - display is working with hardcoded string
 * 01 - add Wifi support, http client support
 *      call test fortune endpoint to retrieve test fortune from
 *      mint server, handle timeouts, put fortune on display
 * 02 - cleanup code not used.
 */
#include <ESP8266WiFi.h>
#include <WiFiClient.h>
#include <ESP8266WiFiMulti.h>
#include <ESP8266HTTPClient.h>

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

//int data = 11;    // 8, DIN pin of MAX7219 module
//int load = 10;    // 9, CS pin of MAX7219 module
//int clock = 13;  // 10, CLK pin of MAX7219 module
int data = 14;    // 8, DIN pin of MAX7219 module
int load = 15;    // 9, CS pin of MAX7219 module
int clk = 13;  // 10, CLK pin of MAX7219 module

int maxInUse = 8;    //change this variable to set how many MAX7219's you'll use

MaxMatrix m(data, load, clk, maxInUse); // define module

byte buffer[10];

// active sentences
//char string1[] = " Don't forget your towel! ";

// on Board
int LEDPin = LED_BUILTIN;

// WiFi

ESP8266WiFiMulti WiFiMulti;

const char* ssid = "Idea Fab Labs";
const char* password = "vortexrings";
// Home test Wifi
//const char* ssid = "";
//const char* password = "";

const int SCROLL_SPEED = 25;

// HTTP Client
HTTPClient http;

String postURL= "http://mint.ideafablabs.com/wp-json/mint/v1/quote_pair/";
String resetParams = "NFC1=00000000&NFC2=00000000";

/// WiFi Client - might not be needed for the display application
//WiFiClient client;

// middle tier logic
int now, lastGet, requestPeriod;
int startTime, currentTime;

//char* displayString;
boolean fortuneIsNeeded = false;
boolean same = true;

String currentQuote, oldQuote;

//char* initialString = " Find a Friend and Get Your Movie Fortune                ";

void setup(){
  
  Serial.begin(115200); // serial communication initialize
  Serial.println("");
  Serial.println("Movie Fortune Display");
  // make the LED pin output and initially turned off
  pinMode(LEDPin, OUTPUT);

  // init the display matrix
  m.init(); // module initialize
  m.setIntensity(0); // dot matix intensity 0-15

  // setup WiFi
  setupWiFi();

  postQuote(postURL, resetParams );  // reset to "Find a Friend and Get Your Movie Fortune" on server side
  currentQuote= fetchQuote();
  displayQuote(currentQuote, SCROLL_SPEED);

 
}

void loop(){

  oldQuote = currentQuote;
  currentQuote= fetchQuote();
  
  //currentQuote=fetchQuote();
  
   Serial.println("currentQuote= " + currentQuote );
  same = compareFirstTenChars(oldQuote, currentQuote);
  
  if (!same){  // not the same
       Serial.println("not same");
    displayQuote(currentQuote, SCROLL_SPEED);   // display new quote
   // postQuote(postURL, resetParams );   
     postQuote(postURL, resetParams );  
     postQuote(postURL, resetParams );  
    // reset 
    delay(1000);
  }
  else{
    Serial.println("same");
    displayQuote(currentQuote, SCROLL_SPEED);
    
  }
 
  delay(500);
  m.clear();
  delay(500);
    
} // main loop

// This routine only compares string sizes right now 
// If two consecutive fetched strings are the same lenth, it will fail
// Comparing first 10 characters TBD  - johnszy Apr 6 2019
boolean compareFirstTenChars(String oldQuote, String currentQuote){

  boolean same;
  unsigned int oldSize =  oldQuote.length();
  unsigned int curSize =  currentQuote.length();
  
  if (oldSize != curSize)
     same = false;
  else
     same = true;
     
  return same;
  
}


String postQuote(String request, String params) {

    String response; 
        
    WiFiClient client;

    HTTPClient http;
    
//  Serial.println("POST " + request + params);
//  Serial.print("[HTTP] begin...\n");
    
    if (http.begin(client, request)) {  // HTTP
//    Serial.print("[HTTP] POST...\n");
//    add headers
      http.addHeader("Content-Type", "application/x-www-form-urlencoded");
      http.addHeader("Cache-Control", "no-store");
      http.addHeader("Pragma", "no-cache");
//    start connection and send HTTP header
      int httpCode = http.POST(params);
//    httpCode will be negative on error
      if (httpCode > 0) {
//        HTTP header has been sent and Server response header has been handled
//        Serial.printf("[HTTP] POST... code: %d\n", httpCode);

//        file found at server
//        Serial.printf("Payload: ");
          if (httpCode == HTTP_CODE_OK || httpCode == 201 || httpCode == HTTP_CODE_MOVED_PERMANENTLY) {
             response = http.getString();
//        Serial.println(response);
          }
      } else {
          response = printf("Error: %s", http.errorToString(httpCode).c_str());
//        Serial.printf("[HTTP] POST... failed, error: %s\n", http.errorToString(httpCode).c_str());
      }
    http.end();
    } else {
//      Serial.printf("[HTTP] Unable to connect\n");
        response = "HTTP Unable to connect";
    }

    return response;
}

String fetchQuote(){
  
  String response;
   
  if ((WiFiMulti.run() == WL_CONNECTED)) {

    WiFiClient client;

    HTTPClient http;

  //  Serial.print("[HTTP] begin...\n");
    if (http.begin(client, "http://mint.ideafablabs.com/wp-json/mint/v1/quote_pair/")) {  // HTTP

      http.addHeader("Content-Type", "application/x-www-form-urlencoded");
      http.addHeader("Cache-Control", "no-store");
      http.addHeader("Pragma", "no-cache");
    //  Serial.print("[HTTP] GET...\n");
      // start connection and send HTTP header
      int httpCode = http.GET();

      // httpCode will be negative on error
      if (httpCode > 0) {
        // HTTP header has been send and Server response header has been handled
     //   Serial.printf("[HTTP] GET... code: %d\n", httpCode);

        // file found at server
        if (httpCode == HTTP_CODE_OK || httpCode == HTTP_CODE_MOVED_PERMANENTLY) {
          response = http.getString();
       //   Serial.println(response);
        }
      } else {
      //  Serial.printf("[HTTP] GET... failed, error: %s\n", http.errorToString(httpCode).c_str());
        response = printf("HTTP GET... failed, error: %s\n",http.errorToString(httpCode).c_str());
      }

      http.end();
    } else {
     // Serial.printf("[HTTP} Unable to connect\n");
      response = "HTTP Unable to connect";
    }
  }  
  return response;
 
}


void displayQuote(String quote, int scrollSpeed){   //100 scrollSpeed is pretty good
  
  char responseBuffer[quote.length()+1];
  quote.toCharArray(responseBuffer, quote.length()+1);
  printStringWithShift(responseBuffer, scrollSpeed, quote.length());  

}



void clearDisplay(){
  // clear the display
  /// TODO: if it's this simple this doesn't need to be here :P
  m.clear();
}
void blankDisplay(){   // disable Display in hardware
  
  digitalWrite(LEDPin, HIGH);   
}

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

void printString(char* s)
{
  int col = 0;
  while (*s != 0)
  {
    if (*s < 32) continue;
    char c = *s - 32;
    memcpy_P(buffer, CH + 7*c, 7);
    m.writeSprite(col, 0, buffer);
    m.setColumn(col + buffer[0], 0);
    col += buffer[0] + 1;
    s++;
  }
}
