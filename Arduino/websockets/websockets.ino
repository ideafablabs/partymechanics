#include <ArduinoWebsockets.h>
#include <ESPAsyncWebServer.h>
#include <asyncHTTPrequest.h>
#include <WiFi.h>
// Communications
asyncHTTPrequest apiClient;
AsyncWebServer server(80);

#ifdef ESP32
#include <WiFiMulti.h>
#include <ESPmDNS.h>
#include <SPIFFS.h>
#else
#include <ESP8266WiFiMulti.h>
#include <ESP8266mDNS.h>
#include <FS.h>
#endif

#define SSID1 "Idea Fab Labs"
#define PASSWORD1 "vortexrings"

// https://pusher.com/
//[scheme]://ws-[cluster_name].pusher.com:[port]/app/[key]
// ws - for a normal WebSocket connection
// wss - for a secure WebSocket connection
// cluster_name - The name of the cluster that you’re using
// port - Default WebSocket ports: 80 (ws) or 443 (wss)
// key - The app key for the application connecting to Pusher Channels
const char *websockets_connection_string = "wss://ws-us3.pusher.com:443/app/01a52d68bccce5e260bd"; // Enter server adress

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

using namespace websockets;

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
void setup()
{
    Serial.begin(115200);

    WiFi.mode(WIFI_STA);
    wifiMulti.addAP(SSID1, PASSWORD1);
    // Wait some time to connect to wifi
    Serial.print("Wifi Connecting.");
    while (wifiMulti.run() == WL_CONNECTED)
    {
        Serial.println("");
        Serial.println("WiFi connected");
        Serial.println("IP address: ");
        Serial.println(WiFi.localIP());
    }
    //    while (wifiMulti.run() != WL_CONNECTED)
    //    {
    //      Serial.print(".");
    //      delay(1000);
    //    }
    //      Serial.println("Connecting Wifi...");

    client.setCACert(pusher_ssl_ca_cert);

    Serial.println("Connected to Wifi, Connecting to server.");
    // try to connect to Websockets server
    bool connected = client.connect(websockets_connection_string);
    if (connected)
    {
        Serial.println("Connected!");
        client.send("Hello Server");
    }
    else
    {
        Serial.println("Not Connected!");
    }

    // run callback when messages are received
    client.onMessage([&](WebsocketsMessage message)
                     {
        Serial.print("Got Message: ");
        Serial.println(message.data()); });
}

void loop()
{
    // let the websockets client check for incoming messages
    if (client.available())
    {
        client.poll();
    }
    delay(500);
}