#include "Adafruit_WS2801.h"
#include "SPI.h" // Comment out this line if using Trinket or Gemma
#ifdef __AVR_ATtiny85__
 #include <avr/power.h>
#endif
 
/*****************************************************************************
Example sketch for driving Adafruit WS2801 pixels!


  Designed specifically to work with the Adafruit RGB Pixels!
  12mm Bullet shape ----> https://www.adafruit.com/products/322
  12mm Flat shape   ----> https://www.adafruit.com/products/738
  36mm Square shape ----> https://www.adafruit.com/products/683

  These pixels use SPI to transmit the color data, and have built in
  high speed PWM drivers for 24 bit color per pixel
  2 pins are required to interface

  Adafruit invests time and resources providing this open source code, 
  please support Adafruit and open-source hardware by purchasing 
  products from Adafruit!

  Written by Limor Fried/Ladyada for Adafruit Industries.  
  BSD license, all text above must be included in any redistribution

*****************************************************************************/

// Choose which 2 pins you will use for output.
// Can be any valid output pins.
// The colors of the wires may be totally different so
// BE SURE TO CHECK YOUR PIXELS TO SEE WHICH WIRES TO USE!
const uint8_t dataPin  = 23;    // Yellow wire on Adafruit Pixels
const uint8_t clockPin = 18;    // Green wire on Adafruit Pixels
const uint8_t touchPin = 4;

const uint8_t threshold = 30;

int touchValue;

// Don't forget to connect the ground wire to Arduino ground,
// and the +5V wire to a +5V supply

// Set the first variable to the NUMBER of pixels. 25 = 25 pixels in a row
Adafruit_WS2801 strip = Adafruit_WS2801(20, dataPin, clockPin);


void setup() {
#if defined(__AVR_ATtiny85__) && (F_CPU == 16000000L)
  clock_prescale_set(clock_div_1); // Enable 16 MHz on Trinket
#endif

  Serial.begin(115200);
  Serial.println("Ding!");  

  strip.begin();
  strip.show();
}

bool rolling = 0;
bool beginRoll = true;

uint8_t i, currentFace, rollSpeed, remainingSteps;
long lastStepTime = 0;


void loop() {

  if (beginRoll == true) {
    uint8_t rnumb = random(1,20);
    rolling = true;
    currentFace = rnumb - 1;
    rollSpeed = 30;
    remainingSteps = 60;

    Serial.print("rnumb: ");
    Serial.println(rnumb);

    Serial.print("currentFace: ");
    Serial.println(currentFace);

    Serial.print("rollSpeed: ");
    Serial.println(rollSpeed);

    Serial.print("remainingSteps: ");
    Serial.println(remainingSteps);
    
    beginRoll = false;
  }

  if (remainingSteps == 0) {
      delay(1000);  /// should get rid of delay and do timer here.
      strip.setPixelColor(currentFace, 0);
      strip.show();
      delay(100);
      strip.setPixelColor(currentFace, 0xFFFFFF);
      strip.show();
      delay(100);
      strip.setPixelColor(currentFace, 0);
      strip.show();
      delay(100);
      strip.setPixelColor(currentFace, 0xFFFFFF);
      strip.show();
      delay(100);
      strip.setPixelColor(currentFace, 0);
      strip.show();
      delay(100);
      strip.setPixelColor(currentFace, 0xFFFFFF);
      strip.show();

      remainingSteps == 60; ///rough way to end this
      rolling = false;
  }
  
  if (rolling == true) {
    
    if ((millis() - lastStepTime) > rollSpeed) {
      currentFace++;
      if (currentFace > 19) {currentFace=0;}

      for (i=0; i < strip.numPixels(); i++) {
        strip.setPixelColor(i, 0);  
      }        
       
      strip.setPixelColor(currentFace, 0xFF0000); /// color magic number here
      strip.show();
      
    Serial.print("currentFace: ");
    Serial.println(currentFace);

      
      rollSpeed += 3;
      remainingSteps--;
      lastStepTime = millis();
    }
  
  } else {
    
    // Waiting for Touch
    touchValue = touchRead(touchPin);
//    Serial.println(touchValue);    
    
    if (touchValue < threshold) {
      for (i=0; i < strip.numPixels(); i++) {
          strip.setPixelColor(i, 0xFF0000);  
      }
      strip.show();
        
      delay(500);
      
      for (i=0; i < strip.numPixels(); i++) {
          strip.setPixelColor(i, 0);  
      }        
      strip.show();

      beginRoll = true;  
    } 
  
  }
 
  
}

void loopn() {
  
  uint8_t j,d,l,i;

  if (rolling == true) {
    
    uint8_t rnumb = random(1,20);
    
    i = rnumb - 1;
    uint16_t rollSpeed = 30;
    
    for (j=0; j < 60; j++) {     // 3 cycles of all 256 colors in the wheel        
        i++;
        if (i > 19) {i=0;}
        
        for (l=0; l < strip.numPixels(); l++) {
          strip.setPixelColor(l, 0);  
        }        
         
        strip.setPixelColor(i, 0xFF0000);
        strip.show();   // write all the pixels out  
        
        delay(rollSpeed);  
        rollSpeed += 3;
        
    }  
   

//  if (rolling == true) {
//      int i=0, j;
//
//    uint8_t rnumb = random(120,2260);
////  uint8_t rnumb = random(,2256);
//
////   for (i=0; i < strip.numPixels(); i++) {
//     for (j=0; j < rnumb; j= j+5) {     // 3 cycles of all 256 colors in the wheel        
//        i++;
//        if (i > 19) {i=0;}
//        
//        for (int l=0; l < strip.numPixels(); l++) {
//          strip.setPixelColor(l, 0);  
//        }        
//         strip.setPixelColor(i, 0xFF0000);
//        
//        delay(j);  
//      strip.show();   // write all the pixels out  
//    }  
//    
    
    delay(1000);
    strip.setPixelColor(i, 0);
    strip.show();
    delay(100);
    strip.setPixelColor(i, 0xFFFFFF);
    strip.show();
    delay(100);
    strip.setPixelColor(i, 0);
    strip.show();
    delay(100);
    strip.setPixelColor(i, 0xFFFFFF);
    strip.show();
    delay(100);
    strip.setPixelColor(i, 0);
    strip.show();
    delay(100);
    strip.setPixelColor(i, 0xFFFFFF);
    strip.show();

    rolling = 0;
  }
  
 
    touchValue = touchRead(touchPin);
    Serial.println(touchValue);    
    
    if (touchValue < threshold) {
      for (l=0; l < strip.numPixels(); l++) {
          strip.setPixelColor(l, 0xFF0000);  
        }
        strip.show();
        
      delay(500);
      
      for (l=0; l < strip.numPixels(); l++) {
          strip.setPixelColor(l, 0);  
      }        
      strip.show();

      rolling = true;  
    }
}






/* Helper functions */

// Create a 24 bit color value from R,G,B
uint32_t Color(byte r, byte g, byte b)
{
  uint32_t c;
  c = r;
  c <<= 8;
  c |= g;
  c <<= 8;
  c |= b;
  return c;
}

//Input a value 0 to 255 to get a color value.
//The colours are a transition r - g -b - back to r
uint32_t Wheel(byte WheelPos)
{
  if (WheelPos < 85) {
   return Color(WheelPos * 3, 255 - WheelPos * 3, 0);
  } else if (WheelPos < 170) {
   WheelPos -= 85;
   return Color(255 - WheelPos * 3, 0, WheelPos * 3);
  } else {
   WheelPos -= 170; 
   return Color(0, WheelPos * 3, 255 - WheelPos * 3);
  }
}
