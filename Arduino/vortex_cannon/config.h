#ifndef CONFIG_H
#define CONFIG_H

/*
 * CONFIG DATA: PINS, WIFI PASSWORDS, ETC
 */

#define ZONE_ID 1 // Zone ID's can be found on the website.
#define READER_ID 2 // 0 for Zone, 1 for intake.
const String DNS_NAME = "nfc-reader-2"; // Zone DNS

#define SSID1 "sendingnudes"
#define PASSWORD1 "sendnudes"
#define SSID2 "Idea Fab Labs"
#define PASSWORD2 "vortexrings"
// #define SSID3 "themint"
// #define PASSWORD1 "vortexrings"

// const String API_BASE = "http://192.168.0.72/"; //Temporary Local
const String API_BASE = "http://mint.ideafablabs.com/"; //Live
// const String API_BASE = "http://santacruz.ideafablabs.com/"; //Live

// const String API_ENDPOINT = "wp-json/zoneplusone/v1/";
const String API_ENDPOINT = "wp-json/mint/v1/";

#define LOG_FILE "/actions.log"

// LED Details
#define LEDPIN 2
#define NUM_LEDS 16
#define BRIGHTNESS 100

// NFC Details

// LOLIN MCU
// #define PN532_SCK  14	// SK
// #define PN532_MOSI 13	// S1
// #define PN532_SS   9		// SC
// #define PN532_MISO 10	// SO

// WEMOS D1
#define PN532_SCK  14
#define PN532_MOSI 13
#define PN532_SS   15
#define PN532_MISO 12
// #define PN532_SS   4

#endif // CONFIG_H