from io import BytesIO
import ssl
import wifi
import socketpool
import board
import displayio
import adafruit_requests as requests
import adafruit_wiznet5k.adafruit_wiznet5k_socket as socket
import adafruit_logging as logging
from adafruit_minimqtt.adafruit_minimqtt import MQTT
import time
import json

# Define event payload
# EVENT_PAYLOAD = {
#     "chipId": "1",
#     "chipName": "Chip 1",
#     "status": "online",
#     "triggerAction": "test-action",
#     "UserTriggererId": "user-name",
#     "UserCheckpoint": "user-checkpoint",
#     "EventMessage": "test message"
# }

try:
    from secrets import secrets
except ImportError:
    print("WiFi secrets are kept in secrets.py, please add them there!")
    raise

print("ESP32-S2 WebClient Test")

print("My MAC addr:", [hex(i) for i in wifi.radio.mac_address])

print("Available WiFi networks:")
for network in wifi.radio.start_scanning_networks():
    print("\t%s\t\tRSSI: %d\tChannel: %d" % (str(network.ssid, "utf-8"),
                                             network.rssi, network.channel))
wifi.radio.stop_scanning_networks()

print("Connecting to %s" % secrets["ssid"])
wifi.radio.connect(secrets["ssid"], secrets["password"])
print("Connected to %s!" % secrets["ssid"])
print("My IP address is", wifi.radio.ipv4_address)

pool = socketpool.SocketPool(socket, ssl.create_default_context())
https = requests.Session(socket, ssl.create_default_context())

# pylint: disable=line-too-long
url = "wss://ws-us3.pusher.com/app/94b06acb67c44442887f?protocol=7&client=ESP32&version=7.0.3&protocol=5"
websocket = pool.ws(url)

# Send a message over the WebSocket
websocket.send("Hello, world!")

# Receive messages from the WebSocket
while True:
    message = websocket.receive()
    print("Received message:", message)
