#ifndef CONFIG_H
#define CONFIG_H

/*
 * CONFIG DATA: PINS, WIFI PASSWORDS, ETC
 */

#define DNS_NAME "vortex-activator" // Zone DNS
#define READER_COUNT 4 // 0 for Zone, 1 for intake.

#define SSID1 "MYWIFISSID"
#define PASSWORD1 "MYWIFIPASS"
#define SSID2 "MYWIFISSID"
#define PASSWORD2 "MYWIFIPASS"

const String API_BASE = "http://192.168.0.1/"; // Server Location
const String API_ENDPOINT = "wp-json/mint/v1/"; // API Endpoint

#define LOG_FILE "/actions.log"

// LED Details
#define LEDPIN 5
#define NUM_LEDS 12
#define BRIGHTNESS 100

// NFC Details
#define PN532_SCK  (18)
#define PN532_MOSI (23)
#define PN532_MISO (19)
#define PN532_SS1  (4)
#define PN532_SS2  (27)
#define PN532_SS3  (26)
#define PN532_SS4  (25)

#endif // CONFIG_H