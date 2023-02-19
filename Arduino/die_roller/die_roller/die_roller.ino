#include "Adafruit_WS2801.h"
#include "SPI.h" // Comment out this line if using Trinket or Gemma
 #include <ArduinoWebsockets.h>
#include <ESPAsyncWebServer.h>
#include <asyncHTTPrequest.h>
#include <WiFi.h>
#include <ArduinoJson.h>
#include <stdio.h>
#include <stdint.h> // To handle string conversion
// Communications
asyncHTTPrequest apiClient;
AsyncWebServer server(443);

#ifdef ESP32
#include <WiFiMulti.h>
#include <ESPmDNS.h>
#include <SPIFFS.h>
#else
#include <ESP8266WiFiMulti.h>
#include <ESP8266mDNS.h>
#include <FS.h>
#endif


// Choose which 2 pins you will use for output.
const uint8_t dataPin  = 23;    // Yellow wire on Adafruit Pixels
const uint8_t clockPin = 18;    // Green wire on Adafruit Pixels
const uint8_t touchPin = 4;

//Touch Sensitivity
const uint8_t threshold = 30;


#define SSID1 "Greenby"
#define PASSWORD1 ""
#define SSID2 "Idea Fab Labs"
#define PASSWORD2 "vortexrings"

// https://pusher.com/
//[scheme]://ws-[cluster_name].pusher.com:[port]/app/[key]
// ws - for a normal WebSocket connection
// wss - for a secure WebSocket connection
// cluster_name - The name of the cluster that you’re using
// port - Default WebSocket ports: 80 (ws) or 443 (wss)
// key - The app key for the application connecting to Pusher Channels
const char *websockets_connection_string = "ws://192.168.0.33:443"; // Enter server adress

const char pusher_ssl_ca_cert[] PROGMEM =
    "-----BEGIN CERTIFICATE-----\n"
    "MIIGLjCCBRagAwIBAgIQdATkQFYKykh1q4tPMVCRjDANBgkqhkiG9w0BAQsFADBf\n"
    "MQswCQYDVQQGEwJGUjEOMAwGA1UECBMFUGFyaXMxDjAMBgNVBAcTBVBhcmlzMQ4w\n"
    "DAYDVQQKEwVHYW5kaTEgMB4GA1UEAxMXR2FuZGkgU3RhbmRhcmQgU1NMIENBIDIw\n"
    "HhcNMjIwNDA3MDAwMDAwWhcNMjMwNDIxMjM1OTU5WjAXMRUwEwYDVQQDDAwqLnB1\n"
    "c2hlci5jb20wggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDXMYx+u6oW\n"
    "XbfBIbInBq1f7TkKRfxbw9oZHNKx7VeukHrhTHXh438zzyRCABHVR+CX91D8Ork9\n"
    "V86mOVwRBFF05VoweX7EjFhirz3l6XTbPUUR5XyIwKNxYTXovxNkK7ahVbnA1+et\n"
    "SAnFyJxuhEz8zKcm0UAl7JzghRNb73SVhePmr95xyno58sTckqKwDCx202ebelbB\n"
    "IfhX19vt2KA3VbCapc8f1vjtunnr5yIldYU8jsjMWZxZSneg8AVHaRRToh5ngIch\n"
    "uXfWhH2HolzrXfrASdLvnmL93qSdRkhAthPBg33e+72KF2KecYrDJTrPJ/2rmJdZ\n"
    "yw5AhrO1nz4fAgMBAAGjggMsMIIDKDAfBgNVHSMEGDAWgBSzkKfYya9OzWE8n3yt\n"
    "XX9B/Wkw6jAdBgNVHQ4EFgQUD7PZ/LHJRRk8TBp5ZmYKAEzWjIkwDgYDVR0PAQH/\n"
    "BAQDAgWgMAwGA1UdEwEB/wQCMAAwHQYDVR0lBBYwFAYIKwYBBQUHAwEGCCsGAQUF\n"
    "BwMCMEsGA1UdIAREMEIwNgYLKwYBBAGyMQECAhowJzAlBggrBgEFBQcCARYZaHR0\n"
    "cHM6Ly9jcHMudXNlcnRydXN0LmNvbTAIBgZngQwBAgEwQQYDVR0fBDowODA2oDSg\n"
    "MoYwaHR0cDovL2NybC51c2VydHJ1c3QuY29tL0dhbmRpU3RhbmRhcmRTU0xDQTIu\n"
    "Y3JsMHMGCCsGAQUFBwEBBGcwZTA8BggrBgEFBQcwAoYwaHR0cDovL2NydC51c2Vy\n"
    "dHJ1c3QuY29tL0dhbmRpU3RhbmRhcmRTU0xDQTIuY3J0MCUGCCsGAQUFBzABhhlo\n"
    "dHRwOi8vb2NzcC51c2VydHJ1c3QuY29tMCMGA1UdEQQcMBqCDCoucHVzaGVyLmNv\n"
    "bYIKcHVzaGVyLmNvbTCCAX0GCisGAQQB1nkCBAIEggFtBIIBaQFnAHUArfe++nz/\n"
    "EMiLnT2cHj4YarRnKV3PsQwkyoWGNOvcgooAAAGAA3xK2wAABAMARjBEAiALNO0C\n"
    "nxK5O7PVwrJ0bvX964Hf5wmhDt0jPXLSYyRscAIgSc3L+mr5CdvhVAM4tJGYmKPV\n"
    "euKMTuhtPeRZVtwzIPMAdwB6MoxU2LcttiDqOOBSHumEFnAyE4VNO9IrwTpXo1Lr\n"
    "UgAAAYADfErZAAAEAwBIMEYCIQCECmH3cn5NeOL+D10AEjbDjBg/GFb+oDKTPEwa\n"
    "8NdLCAIhAPOGps4v/OFjPi0d971wydiZgfOGqk7xIreiVRDPP+WZAHUA6D7Q2j71\n"
    "BjUy51covIlryQPTy9ERa+zraeF3fW0GvW4AAAGAA3xKvQAABAMARjBEAiAiHqWD\n"
    "WD4MNOC83pcGFss1rGIrA1XoIk8V5dkNlCgR3gIgCNOzBEOYUmG7JxcoK2K/ZMq7\n"
    "LhLio5kZwgFFVJSDzGEwDQYJKoZIhvcNAQELBQADggEBACFAw+gkHM/P0dGu+3T5\n"
    "68hzmlzTB6gAvVEYP4r89Xk+bRF0iydZ+5gRjhQOecne+VGFoITdIePJy2aqMbaW\n"
    "+xfNO2c5wWpTuXxK6INmjHs5UexKTbA3g9jjI1R5LDQF3yZkuFFsGvvaLlycZSNX\n"
    "DLr8iA37lIjSsYVwJo9KUXfFFfyZ6zok/r5MeO658MTxCcGsF7pQF9svie2y8LbC\n"
    "x63CXOuf7wjdv0onfMFwMugO7yrA7uyreQsmdTOiSl7PieJsW28BylNPaAYPxatm\n"
    "nChpyGQqoS715avbaEBuhdxrOFROMeIOJRG2K2l2H9cx3958CVNns0lxKz9r5oyD\n"
    "8is=\n"
    "-----END CERTIFICATE-----\n";
// JSON object to hold ESP32 data
DynamicJsonDocument jsonDoc(1024);

using namespace websockets;

//Device Chip ID in unsigned 64bit integer
//https://microdigisoft.com/esp32-with-arduino-json-using-arduino-ide/
uint32_t chipId = 0;

void onMessageCallback(WebsocketsMessage message)
{
    Serial.print("Got Message: ");
    Serial.println(message.data());
}


void onEventsCallback(WebsocketsEvent event, String data)
{
    if (event == WebsocketsEvent::ConnectionOpened)
    {
        Serial.println("Connnection Opened");
    }
    else if (event == WebsocketsEvent::ConnectionClosed)
    {
        Serial.println("Connnection Closed");
    }
    else if (event == WebsocketsEvent::GotPing)
    {
        Serial.println("Got a Ping!");
    }
    else if (event == WebsocketsEvent::GotPong)
    {
        Serial.println("Got a Pong!");
    }
}
#ifdef ESP32
WiFiMulti wifiMulti;
#else
ESP8266WiFiMulti wifiMulti;
#endif
WebsocketsClient client;


int touchValue;
bool rolling = 0;
bool beginRoll = true;

uint8_t i, currentFace, rollSpeed, remainingSteps;
long lastStepTime = 0;



// Don't forget to connect the ground wire to Arduino ground,
// and the +5V wire to a +5V supply

// Set the first variable to the NUMBER of pixels. 25 = 25 pixels in a row
Adafruit_WS2801 strip = Adafruit_WS2801(20, dataPin, clockPin);


void setup() {
#if defined(__AVR_ATtiny85__) && (F_CPU == 16000000L)
  clock_prescale_set(clock_div_1); // Enable 16 MHz on Trinket
#endif

  Serial.begin(115200);
  Serial.println("Ding!");  

  strip.begin();
  strip.show();

  
    WiFi.mode(WIFI_STA);
    wifiMulti.addAP(SSID1, PASSWORD1);
    wifiMulti.addAP(SSID2, PASSWORD2);
    // Wait some time to connect to wifi
    Serial.print("Wifi Connecting.");
    while (wifiMulti.run() != WL_CONNECTED)
    {
        Serial.print(".");
        delay(1000);
    }
    if (wifiMulti.run() == WL_CONNECTED)
    {
        Serial.println("");
        Serial.println("WiFi connected");
        Serial.println("IP address: ");
        Serial.println(WiFi.localIP());
//        jsonDoc["wifi_network"] = "Test";
////        jsonDoc["wifi_network"] = WiFi.SSID().toString();
//        jsonDoc["ip_address"] = WiFi.localIP().toString();
//        jsonDoc["clock_time"] = time;
    }

    //    client.setCACert(pusher_ssl_ca_cert);

    Serial.println("Connected to Wifi, Connecting to server.");
    // try to connect to Websockets server
    if (client.connect(websockets_connection_string))
    {
        Serial.println("Connected!");
        client.send("Hello Server");
//        jsonDoc["wifi_network"] = WiFi.SSID();
//        jsonDoc["ip_address"] = WiFi.localIP().toString();
//        jsonDoc["clock_time"] = time;
        //serializeJson(jsonDoc, Serial);
//        jsonDoc["mdns_address"] = "esp32.local";
//        jsonDoc["last_error"] = String(ESP.getLastError());
//        jsonDoc["chip_id"] = String(ESP.getChipId());
//        Serial.println(jsonDoc);
    }
    else
    {
        Serial.println("Not Connected!");
    }

    // run callback when messages are received
    client.onMessage([&](WebsocketsMessage message)
    {
       Serial.print("Got Message: ");
       Serial.println(message.data()); 
    });
//    
//    serializeJson(jsonDoc, jsonString);
//    Serial.println(jsonDoc);
  // Write JSON object to log file
  //  if (logFile) {
  //    serializeJson(jsonDoc, logFile);
  //    logFile.println();
//    }

  // Send JSON object to client
  //  String jsonString;
  //  serializeJson(jsonDoc, jsonString);
  //  server.send(200, "application/json", jsonString);


  
}


long WSlastPollTime = 0;
long WSpollInterval = 3000;

uint8_t rnumb = 0;

void loop() {

  if (beginRoll == true) {
    rnumb = random(1,20);
    rolling = true;
    currentFace = rnumb - 1;
    rollSpeed = 30;
    remainingSteps = 60;

    Serial.print("rnumb: ");
    Serial.println(rnumb);

    Serial.print("currentFace: ");
    Serial.println(currentFace);

    Serial.print("rollSpeed: ");
    Serial.println(rollSpeed);

    Serial.print("remainingSteps: ");
    Serial.println(remainingSteps);
    
    beginRoll = false;
  }

  if (remainingSteps == 0) {
      String message = "Die Roll: " + rnumb;
      client.send(message);

    
      delay(1000);  /// should get rid of delay and do timer here.
      strip.setPixelColor(currentFace, 0);
      strip.show();
      delay(100);
      strip.setPixelColor(currentFace, 0xFFFFFF);
      strip.show();
      delay(100);
      strip.setPixelColor(currentFace, 0);
      strip.show();
      delay(100);
      strip.setPixelColor(currentFace, 0xFFFFFF);
      strip.show();
      delay(100);
      strip.setPixelColor(currentFace, 0);
      strip.show();
      delay(100);
      strip.setPixelColor(currentFace, 0xFFFFFF);
      strip.show();
      
      remainingSteps == 60; ///rough way to end this
      rolling = false;
  }
  
  if (rolling == true) {
    
    if ((millis() - lastStepTime) > rollSpeed) {
      currentFace++;
      if (currentFace > 19) {currentFace=0;}

      for (i=0; i < strip.numPixels(); i++) {
        strip.setPixelColor(i, 0);  
      }        
       
      strip.setPixelColor(currentFace, 0xFF0000); /// color magic number here
      strip.show();
      
    Serial.print("currentFace: ");
    Serial.println(currentFace);

      
      rollSpeed += 3;
      remainingSteps--;
      lastStepTime = millis();
    }
  
  } else {
    
    // Waiting for Touch
    touchValue = touchRead(touchPin);
//    Serial.println(touchValue);    
    
    if (touchValue < threshold) {
      for (i=0; i < strip.numPixels(); i++) {
          strip.setPixelColor(i, 0xFF0000);  
      }
      strip.show();
        
      delay(500);
      
      for (i=0; i < strip.numPixels(); i++) {
          strip.setPixelColor(i, 0);  
      }        
      strip.show();

      beginRoll = true;  
    } 

    if ((millis() - WSlastPollTime) > WSpollInterval) {
      
    
      for(int i=0; i<17; i=i+8) {
        chipId |= ((ESP.getEfuseMac() >> (40 - i)) & 0xff) << i;
      }
    
      Serial.printf("ESP32 Chip model = %s Rev %d\n", ESP.getChipModel(), ESP.getChipRevision());
      Serial.printf("This chip has %d cores\n", ESP.getChipCores());
      Serial.print("Chip ID: "); Serial.println(chipId);
      // let the websockets client check for incoming messages
      if (client.available())
      {
          client.poll();
      }

      
      WSlastPollTime = millis();
    }
  
  }
 
  
}

void loopn() {
  
  uint8_t j,d,l,i;

  if (rolling == true) {
    
    uint8_t rnumb = random(1,20);
    
    i = rnumb - 1;
    uint16_t rollSpeed = 30;
    
    for (j=0; j < 60; j++) {     // 3 cycles of all 256 colors in the wheel        
        i++;
        if (i > 19) {i=0;}
        
        for (l=0; l < strip.numPixels(); l++) {
          strip.setPixelColor(l, 0);  
        }        
         
        strip.setPixelColor(i, 0xFF0000);
        strip.show();   // write all the pixels out  
        
        delay(rollSpeed);  
        rollSpeed += 3;
        
    }  
   

//  if (rolling == true) {
//      int i=0, j;
//
//    uint8_t rnumb = random(120,2260);
////  uint8_t rnumb = random(,2256);
//
////   for (i=0; i < strip.numPixels(); i++) {
//     for (j=0; j < rnumb; j= j+5) {     // 3 cycles of all 256 colors in the wheel        
//        i++;
//        if (i > 19) {i=0;}
//        
//        for (int l=0; l < strip.numPixels(); l++) {
//          strip.setPixelColor(l, 0);  
//        }        
//         strip.setPixelColor(i, 0xFF0000);
//        
//        delay(j);  
//      strip.show();   // write all the pixels out  
//    }  
//    
    
    delay(1000);
    strip.setPixelColor(i, 0);
    strip.show();
    delay(100);
    strip.setPixelColor(i, 0xFFFFFF);
    strip.show();
    delay(100);
    strip.setPixelColor(i, 0);
    strip.show();
    delay(100);
    strip.setPixelColor(i, 0xFFFFFF);
    strip.show();
    delay(100);
    strip.setPixelColor(i, 0);
    strip.show();
    delay(100);
    strip.setPixelColor(i, 0xFFFFFF);
    strip.show();

    rolling = 0;
  }
  
 
    touchValue = touchRead(touchPin);
    Serial.println(touchValue);    
    
    if (touchValue < threshold) {
      for (l=0; l < strip.numPixels(); l++) {
          strip.setPixelColor(l, 0xFF0000);  
        }
        strip.show();
        
      delay(500);
      
      for (l=0; l < strip.numPixels(); l++) {
          strip.setPixelColor(l, 0);  
      }        
      strip.show();

      rolling = true;  
    }
}






/* Helper functions */

// Create a 24 bit color value from R,G,B
uint32_t Color(byte r, byte g, byte b)
{
  uint32_t c;
  c = r;
  c <<= 8;
  c |= g;
  c <<= 8;
  c |= b;
  return c;
}

//Input a value 0 to 255 to get a color value.
//The colours are a transition r - g -b - back to r
uint32_t Wheel(byte WheelPos)
{
  if (WheelPos < 85) {
   return Color(WheelPos * 3, 255 - WheelPos * 3, 0);
  } else if (WheelPos < 170) {
   WheelPos -= 85;
   return Color(255 - WheelPos * 3, 0, WheelPos * 3);
  } else {
   WheelPos -= 170; 
   return Color(0, WheelPos * 3, 255 - WheelPos * 3);
  }
}
