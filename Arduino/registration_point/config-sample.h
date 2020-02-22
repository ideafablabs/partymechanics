#ifndef CONFIG_H
#define CONFIG_H

/*
 * CONFIG DATA: PINS, WIFI PASSWORDS, ETC
 */

#define ZONE_ID 1 // Zone ID's can be found on the website.
#define DNS_NAME "zone-plus-one" // Zone DNS
#define READER_ID 0 // 0 for Zone, 1 for intake.

#define SSID1 "MYWIFISSID"
#define PASSWORD1 "MYWIFIPASS"
#define SSID2 "MYWIFISSID"
#define PASSWORD2 "MYWIFIPASS"

const String API_BASE = "http://192.168.0.1/"; // Server Location
const String API_ENDPOINT = "wp-json/zoneplusone/v1/"; // API Endpoint
const String LOG_FILE = "actions.log";

// LED Details
#define LEDPIN 2
#define NUM_LEDS 12
#define BRIGHTNESS 100

// NFC Details
#define PN532_SCK  14
#define PN532_MOSI 13
#define PN532_SS   15
#define PN532_MISO 12

#endif // CONFIG_H