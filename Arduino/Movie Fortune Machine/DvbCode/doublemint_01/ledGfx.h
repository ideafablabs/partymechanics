// some basic drawing methods

#include "util.h"
// defines a run of LEDs within an array of Leds
class Strip
{
public:
  CRGB *leds;
  int first;
  int size;
  bool ring; // if true, wraps around
  Strip(CRGB *leds, int first, int size, bool ring = false)
  {
    this->leds = leds;
    this->first = first;
    this->size = size;
    this->ring = ring;
  }

  void clear()
  {
    for(int ix = 0; ix < this->size; ix++)
      this->leds[ix + this->first] = 0;
  }

  void set(int x, CRGB co)
  {
    if(this->ring)
      x = umod(x, this->size);
    else
    {
      if(x < 0 || x >= this->size)
        return;
    }
    this->leds[this->first + x] += co;
  }

  void pp(CRGB co)
  {
    Serial.printf("%02x%02x%02x", co.r, co.g, co.b);
  }
  void draw(float x, float width, CRGB co)
  {
    float e = x + width;
    int xi = floor(x);
    int ei = floor(e);
    if (xi == ei)
    {
      // only one LED lit
      float f = e - x;
      CRGB coF = co * f;
      this->set(xi, coF);
    }
    else
    {
      CRGB coF;
      float f;
  
      // leftmost pixel
      f = xi + 1 - x;
      coF = co * f;
//      Serial.printf("draw[");pp(coF);
      this->set(xi, coF);
  
      // middle pixels, if any
//      Serial.printf("/");pp(co);
      for (int k = xi + 1; k < ei; k++)
        this->set(k, co);
  
      // rightmost pixel
      f = e - ei;
      coF = co * f;
//      Serial.printf("/");pp(coF);Serial.printf("]\n");
      this->set(xi, coF);
      this->set(ei, coF);
    }
  }
}; // end of class

class Sparkle
{
public:
  CRGB co;
  int lifeTime = 1; // number of ticks left to live (pauses at 1 until shrunk to no width
  float x = 0;
  float xVel = 0;
  float w = 0; // current width
  float wVel = 1; // how fast to grow
  float wMax = 1; // how big to grow

  // default constructor
  Sparkle()
  {
    return;
  }

  void log()
  {
    Serial.printf("sparkle w:%.2g wVel:%.2g wMax:%.2g lifeTime:%d\n", this->w, this->wVel, this->wMax, this->lifeTime);
  }

  // whatever that sparkle was... make it be this, now.
  void init(CRGB co, float x, float xVel, float wVel, float wMax, int lifeTime)
  {
    this->co = co;
    this->x = x;
    this->xVel = xVel;
    this->w = 0;
    this->wVel = wVel;
    this->wMax = wMax;
    this->lifeTime = lifeTime;
  }

  void fini(float wVel = -2)
  {
    // tell it to wind down...
    if(wVel > 0)
      this->wVel = wVel;
    this->lifeTime = 1;
  }

  // update and draw to the strip
  // return true if still alive
  bool tick(Strip *strip)
  {
    this->x += this->xVel; // in any case.

    if(this->lifeTime <= 1)
    {
      // shrinking.
      this->w = migrate(this->w, 0, this->wVel);
      if(this->w == 0)
        return false; // a) we're done, b) no need to draw
    }
    else
    {
      this->lifeTime--;
      this->w = migrate(this->w, this->wMax, this->wVel);
//      this->log();
    }

    strip->draw(this->x - this->w / 2.0, this->w, this->co);
    return true;
  }
};

