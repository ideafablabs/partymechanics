#include "util.h"

CRGB operator *(const CRGB &pixel, float scale)
{
  return pixel * (double)scale;
}

CRGB operator *(float scale, const CRGB &pixel)
{
  return pixel * (double)scale;
}

CRGB operator *(const CRGB &pixel, double scale)
{
  CRGB result;
  result.r = pixel.r * scale;
  result.g = pixel.g * scale;
  result.b = pixel.b * scale;
  return result;
}

CRGB operator *(double scale, const CRGB &pixel)
{
  return pixel * scale;
}

void pp(CRGB co)
{
  Serial.printf("%02x%02x%02x", co.r, co.g, co.b);
}

int umod(int x, int m)
{
  if (x >= 0)
    return x % m;
  else
    return m - 1 - ((-x) % m);
}


/// random range
float rr(float low, float high)
{
    return low + drand48() * (high - low);
}


float migrate(float x, float target, float amount)
{
  if(x < target)
  {
    x += amount;
    if(x >= target)
      x = target;
  }
  else if(x > target)
  {
    x -= amount;
    if(x < target)
      x = target;
  }
  return x;
}

// adapted from second good answer on
// https://stackoverflow.com/questions/3018313/algorithm-to-convert-rgb-to-hsv-and-hsv-to-rgb-in-range-0-255-for-both
CRGB hsvToRgb(CHSV hsv)
{
    CRGB rgb;
    unsigned char region, remainder, p, q, t;

    if (hsv.s == 0)
    {
        rgb.r = hsv.v;
        rgb.g = hsv.v;
        rgb.b = hsv.v;
        return rgb;
    }

    region = hsv.h / 43;
    remainder = (hsv.h - (region * 43)) * 6; 

    p = (hsv.v * (255 - hsv.s)) >> 8;
    q = (hsv.v * (255 - ((hsv.s * remainder) >> 8))) >> 8;
    t = (hsv.v * (255 - ((hsv.s * (255 - remainder)) >> 8))) >> 8;

    switch (region)
    {
        case 0:
            rgb.r = hsv.v; rgb.g = t; rgb.b = p;
            break;
        case 1:
            rgb.r = q; rgb.g = hsv.v; rgb.b = p;
            break;
        case 2:
            rgb.r = p; rgb.g = hsv.v; rgb.b = t;
            break;
        case 3:
            rgb.r = p; rgb.g = q; rgb.b = hsv.v;
            break;
        case 4:
            rgb.r = t; rgb.g = p; rgb.b = hsv.v;
            break;
        default:
            rgb.r = hsv.v; rgb.g = p; rgb.b = q;
            break;
    }

    return rgb;
}

CHSV rgbToHsv(CRGB rgb)
{
    CHSV hsv;
    unsigned char rgbMin, rgbMax;

    rgbMin = rgb.r < rgb.g ? (rgb.r < rgb.b ? rgb.r : rgb.b) : (rgb.g < rgb.b ? rgb.g : rgb.b);
    rgbMax = rgb.r > rgb.g ? (rgb.r > rgb.b ? rgb.r : rgb.b) : (rgb.g > rgb.b ? rgb.g : rgb.b);

    hsv.v = rgbMax;
    if (hsv.v == 0)
    {
        hsv.h = 0;
        hsv.s = 0;
        return hsv;
    }

    hsv.s = 255 * long(rgbMax - rgbMin) / hsv.v;
    if (hsv.s == 0)
    {
        hsv.h = 0;
        return hsv;
    }

    if (rgbMax == rgb.r)
        hsv.h = 0 + 43 * (rgb.g - rgb.b) / (rgbMax - rgbMin);
    else if (rgbMax == rgb.g)
        hsv.h = 85 + 43 * (rgb.b - rgb.r) / (rgbMax - rgbMin);
    else
        hsv.h = 171 + 43 * (rgb.r - rgb.g) / (rgbMax - rgbMin);

    return hsv;
}
