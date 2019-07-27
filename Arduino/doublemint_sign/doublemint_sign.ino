/*
   00 - prove NFC reading works
   01 - add WiFi, add more NFC card reading debug messages
   02 - add post UID to mint server, caution: bug in readers present
   colortest repurpose
   00 - JUST TEST
*/

#define FASTLED_ALLOW_INTERRUPTS 0
#include <FastLED.h>
#include <Wire.h>
#include <SPI.h>
#include <Adafruit_PN532.h>
#include "ledGfx.h"
#include "util.h"
#include <vector>

#define count(x)   (sizeof(x) / sizeof(x[0]))

#define LED_DATA_PIN 2
#define READER_ID 1

#define PN532_SCK  14
#define PN532_MOSI 13
#define PN532_SS   15
#define PN532_MISO 12
#define PN532_SS2   16

Adafruit_PN532 nfc(PN532_SCK, PN532_MISO, PN532_MOSI, PN532_SS);


#define TESTING 1

#if TESTING
#define LED_COUNT 12 // ring test
#else
#define LED_COUNT 373 // big doublemint sign
#endif

#define BRIGHTNESS 100

CRGB leds[LED_COUNT];
//Adafruit_NeoPixel strip = Adafruit_NeoPixel(NUM_LEDS, PIN, NEO_GRB + NEO_KHZ800);

long now, lastBlink, lastRead = 0;
uint16_t ledPeriod = 38; // ms
uint16_t cardreaderPeriod = 291; // ms


typedef uint32_t nfcid_t; // we treat the NFCs as 4 byte values throughout, for easiest.

uint32_t colors[] = { 0x00FF00, 0xFFFF00, 0xFF0000, 0x0000FF, 0xFF00FF, 0xFFFFFF,  }; // Should be G Y R B (& secret purple)
uint8_t color = 100; // number between 1-255
uint8_t colorCase = 0;

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
  if (pollCount % 20 == 0) // so the dots dont scroll right forever.
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
    for (int ix = 0; ix < uidLength; ix++)
      uid = (uid << 8) | uidBytes[ix];
  }
  return uid;
}

Strip *strip1;
Sparkle sparkle1;
std::vector<Sparkle>sparkles;

// use two sparkles to define a range of values to select from
typedef struct SparkleDesc
{
  int count; // the desired number of active sparkles
  int percentChance; // percent chance of adding a new sparkle, if we have fewer than we need
  Sparkle low;
  Sparkle high;
};

SparkleDesc idling;
SparkleDesc tagging; // range to use when someone touches the reader.

void setup()
{
  Serial.begin(115200);
  delay(100);
  Serial.printf("doublemint_00 booting, built %s %s\n", __DATE__, __TIME__);

  // sparkle-ey consts
  // init(CRGB co, float x, float xVel, float wVel, float wMax, int lifeTime)

  strip1 = new Strip(leds, 0, LED_COUNT, true);
  idling.count = 10;
  idling.percentChance = 5;
  idling.low.init  (0,            0, -0.5, 0.1, 30, 400);
  idling.high.init (0, strip1->size, +0.5, 1.4, 80, 500);

  tagging.count = 50;
  tagging.percentChance = 45;
  tagging.low.init (0,            0, -3.0, 0.5, 10, 10);
  tagging.high.init(0, strip1->size, +3.0, 3.5, 20, 20);

#if TESTING
  // smaller world, to fit on the itty bitty ring.
  strip1 = new Strip(leds, 0, LED_COUNT, true);
  idling.count = 2;
  idling.percentChance = 5;
  idling.low.init  (0,            0, -0.5, 0.1, 3, 40);
  idling.high.init (0, strip1->size, +0.5, 1.4, 8, 50);

  tagging.count = 4;
  tagging.percentChance = 45;
  tagging.low.init (0,            0, -3.0, 0.5, 1, 10);
  tagging.high.init(0, strip1->size, +3.0, 3.5, 2, 20);
#endif
  // led setup
  FastLED.addLeds<WS2812, LED_DATA_PIN, GRB>(leds, LED_COUNT);

  // nfc setup
  nfc.begin();
  uint32_t versiondata = nfc.getFirmwareVersion();
  if (!versiondata)
  {
    Serial.print("Didn't find PN53x board");
    delay(1000); // wait a second and give it a go.
    ESP.restart();
  }

  Serial.printf("PN53X %02x firmware version %d.%d\n",
                (versiondata >> 24) & 0xFF,
                (versiondata >> 16) & 0xFF,
                (versiondata >> 8) & 0xFF);

  // Set the max number of retry attempts to read from a card
  // This prevents us from waiting forever for a card, which is
  // the default behaviour of the PN532.
  nfc.setPassiveActivationRetries(0x01);

  // configure board to read RFID tags
  nfc.SAMConfig();

  Serial.printf("Ready and waiting for an ISO14443A card.\n");

  // Light it up
  FastLED.setBrightness(BRIGHTNESS);
  FastLED.show(); // Initialize all pixels to 'off'
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
    if (thisUid != lastUid)
    {
      int flavor = getFlavor(thisUid);
      if (thisUid == 0)
        Serial.printf("\nNo Card\n");
      else
        Serial.printf("\ncard id %08x flavor %d\n", thisUid, flavor);
      lastUid = thisUid;

      // transition. If it's a card, init the sparkle.
      if (thisUid != 0)
      {
        sparkle1.init(colors[getFlavor(thisUid)], rr(0, 20), rr(-.1, +.1), rr(0.1, 0.3), rr(5, 10), rr(300, 400));
        //        sparkle1.init(colors[getFlavor(thisUid)], 5, 0, 1, 10, 50);
      }
      else
      {
        sparkle1.fini(0.8);
      }

      // Transition. if it's a card, quell all the sparkles quicker, so the user-colored ones can manifest sooner.
      if (thisUid != 0)
      {
        for (Sparkle &sparkle : sparkles)
        {
          sparkle.fini(5); // shrink it down!
        }
      }
    }
    lastRead = now;
  }

  // +-------------------------
  // | Tick the LEDs
  if (now >= lastBlink + ledPeriod)
  {
    strip1->clear();

    // the single (for now) sparkle
    //    sparkle1.tick(strip1);

    // manage the sparkles
    // run each one, and if it's done, remove it.
#if 1
    for (int ix = 0; ix < sparkles.size(); ix++)
    {
      bool going = sparkles[ix].tick(strip1);
      if (!going)
      {
        Serial.printf("\nremoving sparkle");
        remove(sparkles, ix);
        ix--;
      }
    }
    SparkleDesc *sd = thisUid ? &tagging : &idling;
    if (sparkles.size() < sd->count)
    {
      // we have fewer sparkles than we're allowed. Maybe just maybe add a fresh one!
      if (random(100) < sd->percentChance)
      {
        // ok. add a new sparkle.
        int co = thisUid ? colors[getFlavor(thisUid)] : colors[random(4)]; // idle without the "secret" fifth color.
        Sparkle s;
        CRGB coRgb = co;
        CHSV coHsv = rgbToHsv(coRgb);
        coHsv.h += rr(-10, +10);
        s.init(coHsv, rr(sd->low.x, sd->high.x),
               rr(sd->low.xVel, sd->high.xVel),
               rr(sd->low.wVel, sd->high.wVel),
               rr(sd->low.wMax, sd->high.wMax),
               rr(sd->low.lifeTime, sd->high.lifeTime));
        sparkles.push_back(s);
        Serial.printf("\nadding sparkle");
      }
    }
#endif

    //    // and the moving white dot
    //    leds[step % LED_COUNT] = 0x101010;

    // Let the magic happen.
    FastLED.show();

    //update step
    step++;

    // update timer
    lastBlink = now;
  }

  // do LEDs
}

