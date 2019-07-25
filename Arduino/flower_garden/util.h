
#ifndef _UTIL_H_
#define _UTIL_H_

#include <FastLED.h>

// Some helpers for all
CRGB operator *(const CRGB &pixel, float scale);
CRGB operator *(float scale, const CRGB &pixel);
CRGB operator *(const CRGB &pixel, double scale);
CRGB operator *(double scale, const CRGB &pixel);
int umod(int x, int m);
float rr(float low, float high);
float migrate(float x, float target, float amount);

CRGB hsvToRgb(CHSV hsv);
CHSV rgbToHsv(CRGB rgb);

template <class T>
void remove(std::vector<T> &container, int index)
{
    if(index >= 0 && index < container.size())
        container.erase(container.begin() + index);
}


#endif // _UTIL_H_
