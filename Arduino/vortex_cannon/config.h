#ifndef CONFIG_H
#define CONFIG_H

/*
 * CONFIG DATA: PINS, WIFI PASSWORDS, ETC
 */

const String DNS_NAME = "vortex-cannon"; // Zone DNS

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

#define MOTORPIN 2

#endif // CONFIG_H