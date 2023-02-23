import time
import board
import digitalio
# import neopixel
from adafruit_ws2801 import WS2801
import busio
from wifi import WIFISTATUS
import wifi
import socketpool
from adafruit_esp32spi import adafruit_esp32spi, adafruit_esp32spi_wifimanager
import ssl
import adafruit_requests as requests

# Communications
api_url = "http://<server_address>/api/test"
wifi_status = WIFISTATUS.NOTCONNECTED
api_response = ""

# Set up ESP32 Connection
esp32_cs = digitalio.DigitalInOut(board.GP7)
esp32_ready = digitalio.DigitalInOut(board.GP10)
esp32_reset = digitalio.DigitalInOut(board.GP11)
spi = busio.SPI(board.GP18, board.GP19, board.GP16)
esp = adafruit_esp32spi.ESP_SPIcontrol(spi, esp32_cs, esp32_ready, esp32_reset)
pool = socketpool.SocketPool(esp)

# WiFi Connection Details
wifi_ssid = ""
wifi_password = "vortexrings"

# WS2801 LED Details
led_count = 20
led_data = board.GP23
led_clock = board.GP18
led = WS2801(led_data, led_clock, led_count, brightness=0.2)

# Touch Sensor
touch_pin = board.GP4
touch = touchio.TouchIn(touch_pin)

# Touch Sensitivity
touch_threshold = 30

# WiFi Connection
wifi_manager = adafruit_esp32spi_wifimanager.ESPSPI_WiFiManager(esp, pool)
wifi_manager.connect()
wifi_status = wifi_manager.status

# Connect to API
ssl_context = ssl.create_default_context()
ssl_context.set_alpn_protocols(["h2", "http/1.1"])
requests.set_socket(socketpool.SocketPool(esp, ssl_context=ssl_context))
response = requests.get(api_url)
api_response = response.text
response.close()

# WS2801 Test
color = (255, 255, 255)
for i in range(0, led_count):
    led[i] = color
led.show()
time.sleep(1)

# Turn off LEDs
color = (0, 0, 0)
for i in range(0, led_count):
    led[i] = color
led.show()

while True:
    # Check Touch Sensor
    touch_value = touch.raw_value
    if touch_value > touch_threshold:
        print("Touched!")
        
        # Roll Die
        color = (255, 255, 255)
        led[random.randint(0, led_count - 1)] = color
        led.show()
        time.sleep(1)
        color = (0, 0, 0)
        led.show()
    else:
        print("Not touched.")
    time.sleep(0.1)
