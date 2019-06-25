/*
 * 00 - prove NFC reading works
 * 01 - add WiFi, add more NFC card reading debug messages
 * 02 - add post UID to mint server, caution: bug in readers present
 * colortest repurpose
 * 00 - JUST TEST
 */

#include <Adafruit_NeoPixel.h>
#include <Wire.h>
#include <SPI.h>
#include <Adafruit_PN532.h>

#define count(x)   (sizeof(x) / sizeof(x[0]))

#define PIN 5
#define READER_ID 1

#define PN532_SCK  14
#define PN532_MOSI 13
#define PN532_SS   15
#define PN532_MISO 12
#define PN532_SS2   16

Adafruit_PN532 nfc(PN532_SCK, PN532_MISO, PN532_MOSI, PN532_SS);

#define NUM_LEDS 11
#define BRIGHTNESS 50

Adafruit_NeoPixel strip = Adafruit_NeoPixel(NUM_LEDS, PIN, NEO_GRB + NEO_KHZ800);

long now,lastBlink,lastRead =0;
uint16_t ledPeriod = 38; // ms
uint16_t cardreaderPeriod = 291; // ms


typedef uint32_t nfcid_t; // we treat the NFCs as 4 byte values throughout, for easiest.

uint32_t colors[] = { 0x00FF00, 0xFFFF00, 0xFF0000, 0x0000FF, 0xFF00FF, 0xFFFFFF,  }; // Should be G Y R B
uint8_t color= 100;  // number between 1-255
uint8_t colorCase= 0;

uint8_t getFlavor(nfcid_t uid) 
{
  // we think idcode is always even... 
  // this is mostly because we read the little-endian id as if it were
  // big-endian and are getting kinda lucky. But this /2 mod5 thing works so ok for now. dvb 2019.
  uid /= 2;
  int flavor = uid % 5;
  return flavor;
}

nfcid_t pollNfc()
{
  // return the 64 bit uid, with ZERO meaning nothing presently detected
  static int pollCount = 0; // just for printing the poll dots.
  if(pollCount % 20 == 0) // so the dots dont scroll right forever.
    Serial.printf("\n%4d ", pollCount);
  pollCount++;
  Serial.print(".");

  uint8_t uidBytes[8] = {0};
  uint8_t uidLength;
  
  int foundCard = nfc.readPassiveTargetID(PN532_MIFARE_ISO14443A, &uidBytes[0], &uidLength, 100);
  nfcid_t uid = 0;
  
  if (foundCard) 
  {
    uidLength = 4; // it's little endian, the lower four are enough, and all we can use on this itty bitty cpu.
    // unspool the bins right here.
    for(int ix = 0; ix < uidLength; ix++)
      uid = (uid << 8) | uidBytes[ix];
  }
  return uid;
}

void setup() 
{
  Serial.begin(115200);
  delay(100);
  Serial.printf("doublemint_00 booting, built %s %s\n", __DATE__,__TIME__);
  
  nfc.begin();
  uint32_t versiondata = nfc.getFirmwareVersion();
  if (!versiondata) 
  {
    Serial.print("Didn't find PN53x board");
    delay(1000); // wait a second and give it a go.
    ESP.restart();
  }

  Serial.printf("PN53X %02x firmware version %d.%d\n", 
      (versiondata>>24) & 0xFF, 
      (versiondata>>16) & 0xFF,
      (versiondata>>8) & 0xFF);

  // Set the max number of retry attempts to read from a card
  // This prevents us from waiting forever for a card, which is
  // the default behaviour of the PN532.
  nfc.setPassiveActivationRetries(0x01);

  // configure board to read RFID tags
  nfc.SAMConfig();

  Serial.printf("Ready and waiting for an ISO14443A card.\n");

  // Light it up
  strip.setBrightness(BRIGHTNESS);
  strip.begin();
  strip.show(); // Initialize all pixels to 'off'
}

int step = 0;

void loop() 
{
   now = millis();

  // +-------------------------
  // | Poll the NFC
  static nfcid_t lastUid = -1;
  static nfcid_t thisUid = -1;
  
  if (now >= lastRead + cardreaderPeriod) // time for next poll?
  {
    thisUid = pollNfc();
    if(thisUid != lastUid)
    {
      int flavor = getFlavor(thisUid);
      if(thisUid == 0)
        Serial.printf("\nNo Card\n");
      else
        Serial.printf("\ncard id %08x flavor %d\n", thisUid, flavor);
      lastUid = thisUid;
    }
    lastRead = now;
  }

  // +-------------------------
  // | Tick the LEDs
  if (now >= lastBlink + ledPeriod) 
  {
    for(int i=0; i<NUM_LEDS; i++)
      strip.setPixelColor(i,0);

    if (thisUid != 0)
    {
      for(int i=0; i<NUM_LEDS; i++)
        strip.setPixelColor(i, colors[getFlavor(thisUid)]);
    } 

    // and the moving white dot
    strip.setPixelColor(step % NUM_LEDS, 0xFFFFFF);

    // Let the magic happen.
    strip.show();
 
    //update step
    step++;

    // update timer
    lastBlink = now;
  }
  
  // do LEDs
}

// Input a value 0 to 255 to get a color value.
// The colours are a transition r - g - b - back to r.
uint32_t Wheel(byte WheelPos) {
  WheelPos = 255 - WheelPos;
  if(WheelPos < 85) {
    return strip.Color(255 - WheelPos * 3, 0, WheelPos * 3,0);
  }
  if(WheelPos < 170) {
    WheelPos -= 85;
    return strip.Color(0, WheelPos * 3, 255 - WheelPos * 3,0);
  }
  WheelPos -= 170;
  return strip.Color(WheelPos * 3, 255 - WheelPos * 3, 0,0);
}

uint8_t red(uint32_t c) {
  return (c >> 16);
}
uint8_t green(uint32_t c) {
  return (c >> 8);
}
uint8_t blue(uint32_t c) {
  return (c);
}
