#include <ArduinoJson.h>
#include <WiFi.h>
#include <WiFiMulti.h>
#include <DNSServer.h>
#include <WebServer.h>
#include <ESPmDNS.h>

// JSON object to hold ESP32 data
DynamicJsonDocument jsonDoc(1024);

// WiFi variables
WiFiMulti wifiMulti;
DNSServer dnsServer;
WebServer server(80);

// Create a log file on the ESP32
File logFile;

void setup() {
  // Initialize serial communication
  Serial.begin(115200);
  delay(10);

  // Connect to WiFi network
  wifiMulti.addAP("SSID", "password");
  while (wifiMulti.run() != WL_CONNECTED) {
    delay(250);
    Serial.print(".");
  }

  // Set up mDNS responder
  if (MDNS.begin("esp32")) {
    Serial.println("mDNS responder started");
  }

  // Set up DNS server
  dnsServer.start(DNS_PORT, "*", WiFi.localIP());

  // Set up web server
  server.onNotFound([]() {
    server.send(404, "text/plain", "404: Not Found");
  });
  server.begin();
  Serial.println("Web server started");

  // Create a log file on the ESP32
  logFile = SPIFFS.open("/log.txt", "w");
}

void loop() {
  // Handle incoming client requests
  server.handleClient();

  // Get current time
  String time = String(millis());

  // Add data to JSON object
  jsonDoc["wifi_network"] = WiFi.SSID();
  jsonDoc["ip_address"] = WiFi.localIP().toString();
  jsonDoc["clock_time"] = time;
  jsonDoc["mdns_address"] = "esp32.local";
//  jsonDoc["last_error"] = String(ESP.getLastError());
//  jsonDoc["chip_id"] = String(ESP.getChipId());

  // Write JSON object to log file
  if (logFile) {
    serializeJson(jsonDoc, logFile);
    logFile.println();
  }

  // Send JSON object to client
  String jsonString;
  serializeJson(jsonDoc, jsonString);
  server.send(200, "application/json", jsonString);

  delay(1000);
}
