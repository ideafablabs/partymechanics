/*
 * IFL Zone +1 Token Registration Code
 * Send the token ID from an NFC or RFID card up to an api endpoint.
 *
 * @contributors: Jordan Layman, David Van Brink, John Szymanski, Geoff Gnau, Tané Tachyon, Corey Brown
 */

#include <Wire.h>
#include <SPI.h>
#include <Adafruit_PN532.h>
#include <ArduinoOTA.h>

// Onboard Libs
#include <ESPAsyncWebServer.h>
#include <asyncHTTPrequest.h>
#include <WebSocketsClient.h>

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

//  NFC
Adafruit_PN532 nfcs[READER_COUNT] = {
    Adafruit_PN532(PN532_SS1)
    // Adafruit_PN532(PN532_SCK, PN532_MISO, PN532_MOSI, PN532_SS2),
    // Adafruit_PN532(PN532_SCK, PN532_MISO, PN532_MOSI, PN532_SS3),
    // Adafruit_PN532(PN532_SCK, PN532_MISO, PN532_MOSI, PN532_SS4),
};

typedef uint32_t nfcid_t; // We treat the NFCs as 4 byte values throughout, for easiest.
long lastRead, successTime = 0;
uint16_t cardreaderPeriod = 500; // ms
uint16_t successPeriod = 3000;   // ms

enum readerState
{
    RQ_STANDBY,
    RQ_PENDING,
    RQ_SUCCESS,
    RQ_FAILED
};
uint8_t state = RQ_STANDBY;

// LED
// Adafruit_NeoPixel strip = Adafruit_NeoPixel(NUM_LEDS, LEDPIN, NEO_GRB + NEO_KHZ800);
CRGB leds[NUM_LEDS];

int step = 0;
long lastBlink = 0;
const uint16_t ledPeriod = 40; // ms 24fps
uint32_t tokenColors[] = {
    0x00FF00,
    0xFFFF00,
    0xFF0000,
    0x0000FF,
    0xFF00FF,
    0xFFFFFF,
}; // Should be G Y R B (& secret purple)

// uint8_t tokenCount = 0;
// uint8_t tokenSelection[READER_COUNT];
// uint32_t tokensAchieved[READER_COUNT];

long holdTimes[READER_COUNT];
long holdStartTimes[READER_COUNT];

// nfcid_t tokensAchieved
nfcid_t lastIDs[READER_COUNT];
nfcid_t tokenIDs[READER_COUNT];

enum mapping
{
    SOCKETSTART1 = 0,
    SOCKETEND1 = 0,
    SOCKETSTART2 = 1,
    SOCKETEND2 = 1,
    SOCKETSTART3 = 2,
    SOCKETEND3 = 2,
    SOCKETSTART4 = 3,
    SOCKETEND4 = 3,
    METERSTART1 = 4,
    METEREND1 = 7,
    METERSTART2 = 8,
    METEREND2 = 11
};

// Communications
WiFiMulti wifiMulti;
asyncHTTPrequest apiClient;
AsyncWebServer server(80);
WebSocketsClient webSocket;
uint8_t socketState;

void setup()
{

    Serial.begin(115200);
    Serial.println("Henlo!");

    setupWiFi();

    setupClient();

    logAction("Booted Up");

    // printLog();
}

void loop()
{

    // Get time.
    now = millis();
    Serial.println("boom!");
    webSocket.loop();
}

void webSocketEvent(WStype_t type, uint8_t *payload, size_t lenght)
{

    switch (type)
    {
    case WStype_DISCONNECTED:
        USE_SERIAL.printf("[WSc] Disconnected!\n");
        break;
    case WStype_CONNECTED:
    {
        USE_SERIAL.printf("[WSc] Connected to url: %s\n", payload);

        // send message to server when Connected
        webSocket.sendTXT("Connected");
    }
    break;
    case WStype_TEXT:
        USE_SERIAL.printf("[WSc] get text: %s\n", payload);

        // send message to server
        // webSocket.sendTXT("message here");
        break;
    case WStype_BIN:
        USE_SERIAL.printf("[WSc] get binary lenght: %u\n", lenght);
        // hexdump(payload, lenght);

        // send data to server
        // webSocket.sendBIN(payload, lenght);
        break;
    }
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
uint8_t extractRed(uint32_t c) { return ((c >> 16) & 0xFF); }
uint8_t extractGreen(uint32_t c) { return ((c >> 8) & 0xFF); }
uint8_t extractBlue(uint32_t c) { return (c & 0xFF); }

uint32_t rgba(byte r, byte g, byte b, int a)
{

    int rr = (r * a) / 100;
    int gg = (g * a) / 100;
    int bb = (b * a) / 100;

    uint32_t hex = (rr << 16L) | (gg << 8L) | bb;

    return hex;
}

uint32_t alpha(uint32_t c, int a)
{

    uint8_t r = extractRed(c);
    uint8_t g = extractGreen(c);
    uint8_t b = extractBlue(c);

    return rgba(r, g, b, a);
}

// https://github.com/boblemaire/asyncHTTPrequest
// https://stackoverflow.com/questions/54820798/how-to-receive-json-response-from-rest-api-using-esp8266-arduino-framework
void onClientStateChange(void *arguments, asyncHTTPrequest *aReq, int readyState)
{

    switch (readyState)
    {
    case 0: // readyStateUnsent: Client created, open not yet called.
        break;

    case 1: // readyStateOpened: open() has been called, connected
        break;

    case 2: // readyStateHdrsRecvd: send() called, response headers available
        break;

    case 3: // readyStateLoading: receiving, partial data available
        break;

    case 4: // readyStateDone: Request complete, all data available.

        // Log Response.
        /// We might want to store the response and check syncronously so log doesn't get chunked.
        String rcode = (String)aReq->responseHTTPcode();
        logAction(rcode + " " + aReq->responseText());

        // request case here.
        if (aReq->responseHTTPcode() == 200)
        {
            state = RQ_SUCCESS;
            successTime = now;
        }
        else
        {
            state = RQ_FAILED;
            successTime = now;
        }

        /// This should probably be json so we can confirm respones types.

        break;
    }
}

// Wifi Setup.
void setupWiFi()
{
    WiFi.mode(WIFI_STA);

    wifiMulti.addAP(SSID1, PASSWORD1);
    wifiMulti.addAP(SSID2, PASSWORD2);

    Serial.print("Wifi Connecting.");
    while (wifiMulti.run() != WL_CONNECTED)
    {
        Serial.print(".");
        delay(1000);
    }

    logAction("WiFi connected to SSID: '" + WiFi.SSID() + "' @ " + WiFi.localIP().toString());
}

void startAsyncRequest(String request, String params, String type)
{

    logAction(type + " REQUEST: " + request + "?" + params);

    if (apiClient.readyState() == 0 || apiClient.readyState() == 4)
    { // Only one send at a time here.
        apiClient.open(type.c_str(), request.c_str());
        if (type == "POST")
            apiClient.setReqHeader("Content-Type", "application/x-www-form-urlencoded");

        apiClient.send(params);
    }
    else
    {
        logAction("Request attempted but busy sending...Try again.");
    }
}

const char *PARAM_MESSAGE = "message"; ///
void setupServer()
{

    // Start the file system.
    if (!SPIFFS.begin())
    {
        Serial.println("An Error has occurred while mounting SPIFFS");
        return;
    }

    // Root / Home
    // server.on("/", HTTP_GET, [](AsyncWebServerRequest *request){
    //      request->send(200, "text/plain", printLog());
    //  });

  server.on("/", HTTP_GET, [](AsyncWebServerRequest *request)
            {
        request->send(200, "text/plain", "fgkldsjklfgjklgfdljk");

        // request->send(SPIFFS, LOG_FILE, "text/plain"); });

        server.serveStatic("/log/", SPIFFS, LOG_FILE);

        AsyncStaticWebHandler &serveStatic(const char *uri, fs::FS &fs, const char *path, const char *cache_control = NULL);

        // server.on("/log/flush", HTTP_GET, [](AsyncWebServerRequest *request){
        //  flushLog();
        //  request->send(200, "text/plain", "Log flushed.");
        //  // request->send(SPIFFS, "/"+LOG_FILE, "text/plain");
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
}
void notFound(AsyncWebServerRequest *request)
{
        request->send(404, "text/plain", "Connected but not found");
}

// Return the 64 bit uid, with ZERO meaning nothing presently detected.
nfcid_t pollNfc(uint8_t reader_id)
{
        uint8_t uidBytes[8] = {0};
        uint8_t uidLength;
        nfcid_t uid = 0;

        // static int pollCount = 0; // just for printing the poll dots.
        // char pollChar = '.'; // dots for no read, + for active.

        // Check for card
        int foundCard = nfcs[reader_id].readPassiveTargetID(PN532_MIFARE_ISO14443A, &uidBytes[0], &uidLength, 100);

        if (foundCard)
        {
            uidLength = 4; // it's little endian, the lower four are enough, and all we can use on this itty bitty cpu. ///magic numbers

            // Unspool the bins right here.
            for (int ix = 0; ix < uidLength; ix++)
                uid = (uid << 8) | uidBytes[ix];

            // pollChar = '+'; //
        }

        // if (pollCount % 20 == 0)  // so the dots dont scroll right forever.
        //  Serial.printf("\n%4d ", pollCount);
        // pollCount++;
        // Serial.print(pollChar);
        return uid;
}

// Count Macro.
#define count(x) (sizeof(x) / sizeof(x[0]))

// Show real number for tokenID.
long id(uint8_t bins[])
{
        uint32_t c;
        c = bins[0];
        for (int i = 1; i < count(bins); i++)
        {
            c <<= 8;
            c |= bins[i];
        }
        return c;
}

// === Log Functions ===
String printLog()
{
        int xCnt = 0;
        String output;

        File f = SPIFFS.open(LOG_FILE, "r");

        if (!f)
        {
            output = "file open failed";
            return output;
        }

        output = "====== Reading from LOG_FILE =======";

        while (f.available())
        {
            // Lets read line by line from the file
            String line = f.readStringUntil('\n');
            // Serial.print(xCnt);
            // Serial.print("  ");
            // Serial.println(line);
            output += xCnt + "  " + line + "\n";

            xCnt++;
        }
        f.close();

        return output;
}

void listFiles()
{
        File root = SPIFFS.open("/");
        File file = root.openNextFile();

        Serial.println("Listing SPIFFS Files");

        while (file)
        {

            Serial.print("FILE: ");
            Serial.println(file.name());

            file = root.openNextFile();
        }
}

void flushLog()
{
        File f = SPIFFS.open(LOG_FILE, "w");
        f.printf("%s %s: ", __DATE__, __TIME__);
        f.println("Begin Log");
        f.close();
}

void logAction(String actionString)
{
        Serial.printf("\n%s %s: ", __DATE__, __TIME__);
        Serial.println(actionString);

        char *mode = "a";

        // if not exists, create using W, other wise append with A
        if (!SPIFFS.exists(LOG_FILE))
            mode = "w";

        File f = SPIFFS.open(LOG_FILE, mode);
        f.printf("%s %s: ", __DATE__, __TIME__);
        f.println(actionString);
        f.close();
}
