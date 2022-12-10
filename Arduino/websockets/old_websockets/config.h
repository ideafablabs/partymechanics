#ifndef CONFIG_H
#define CONFIG_H

/*
 * CONFIG DATA: PINS, WIFI PASSWORDS, ETC
 */
#define READER_COUNT 1               // 0 for Zone, 1 for intake.
const String DNS_NAME = "kobelizer"; // Zone DNS

#define SSID1 "sendingnudes"
#define PASSWORD1 "sendnudes"
#define SSID2 "Idea Fab Labs"
#define PASSWORD2 "vortexrings"
// #define SSID3 "themint"
// #define PASSWORD1 "vortexrings"

bool socketActive = 1;

// const String API_BASE = "http://10.0.4.127/"; //Temporary Local
// const String API_BASE = "http://192.168.0.72/"; //Temporary Local
const String API_BASE = "http://mint.ideafablabs.com/"; // Live
// const String API_BASE = "http://santacruz.ideafablabs.com/"; //Live

// const String API_ENDPOINT = "wp-json/zoneplusone/v1/";
const String API_ENDPOINT = "wp-json/mint/v1/";

#define LOG_FILE "/actions.log"

// LED Details
#define LEDPIN 16
#define LEDCLK 33
#define NUM_LEDS 16
#define BRIGHTNESS 100

#define WEBSOCKETSERVER_ADDRESS "192.168.0.30"
#define USE_SERIAL Serial

// NFC Details

// ESP32
// #define PN532_SCK  (18)
// #define PN532_MOSI (23)
// #define PN532_MISO (19)
#define PN532_SCK (14)
#define PN532_MOSI (13)
#define PN532_MISO (12)

#define PN532_SS1 (4)
#define PN532_SS2 (27)
#define PN532_SS3 (26)
#define PN532_SS4 (25)

#endif // CONFIG_H
