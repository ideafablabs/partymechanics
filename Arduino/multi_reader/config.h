#ifndef CONFIG_H
#define CONFIG_H

/*
 * CONFIG DATA: PINS, WIFI PASSWORDS, ETC
 */

#define ZONE_ID 1 // Zone ID's can be found on the website.
#define READER_ID 0 // 0 for Zone, 1 for intake.
const String DNS_NAME = "vortexrings"; // Zone DNS

//  NFC
#define READER_COUNT 1

#define SSID1 "omino warp"
#define PASSWORD1 "0123456789"
#define SSID2 "Idea Fab Labs"
#define PASSWORD2 "vortexrings"

// const String API_BASE = "http://10.0.4.127/"; //Temporary Local
// const String API_BASE = "http://192.168.0.72/"; //Temporary Local
const String API_BASE = "https://mint.ideafablabs.com/"; //Live
// const String API_BASE = "https://santacruz.ideafablabs.com/"; //Live

const String API_ENDPOINT = "wp-json/mint/v1/";
const String LOG_FILE = "actions.log";

// LED Details
#define LEDPIN 5
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
#define PN532_MISO 12
#define PN532_SS1  15
#define PN532_SS2   4

#endif // CONFIG_H