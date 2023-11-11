"""
ello m8
biome waypoint controller
writes a successful waypoint interaction
flourishes LEDs
"""

import time
import board
import busio
import json
import neopixel
from digitalio import DigitalInOut
from digitalio import Direction
from adafruit_pn532.i2c import PN532_I2C

# set to 1 to enable debug messages
DEBUGFLAG = 1

# set appropriately - the small purp boards are "S2MINI"
# BOARDTYPE = "tft"
BOARDTYPE = "S2MINI"

# Define the step, direction, and enable pins
step_pin = DigitalInOut(board.IO39)
dir_pin = DigitalInOut(board.IO21)

# Set the direction of the pins
step_pin.direction = Direction.OUTPUT
dir_pin.direction = Direction.OUTPUT

# Set the direction
dir_pin.value = True  # Set to False to change direction



blankToken = {
    "uid"               :   0,
    "biome1"            :   0,
    "biome2"            :   0,
    "biome3"            :   0,
    "biome4"            :   0,
    "biome5"            :   0,
    
    "color"             :   0
      
    # should we have it so if there is not a color field, it reads the endian like we were doing before?
    # so any time a tag is read and the color of it matters, it checks to see if there is a color block:
    # if not, it reads the color endian, uses that, and also sets the color block to that endian value.
    # If that ends up being incorrect (the digital color is different than the physical color) we will need a wiping station or something like that.
    
}

#initialize empty token buffer
tokenBuffer = blankToken
last_scan = 0
uid = 0



########################
## init
########################
if DEBUGFLAG : print("init I2C")


########################
## tft board and mini board use diff pins
########################
if BOARDTYPE == "tft":
    if DEBUGFLAG : print("TFT board init")
    # I2C connection:
    i2c = busio.I2C(board.SCL, board.SDA)
    
    # currently NC on hardware
    reset_pin = DigitalInOut(board.D6)
    req_pin = DigitalInOut(board.D12)

    # on D10 on hardware 
    irq_pin = DigitalInOut(board.D10)
    pn532 = PN532_I2C(i2c, debug=False, reset=reset_pin, req=req_pin, irq=irq_pin)


    ic, ver, rev, support = pn532.firmware_version
    if DEBUGFLAG : print("Found PN532 with firmware version: {0}.{1}".format(ver, rev))

########################
## this one for the small/cheap S2MINI boards 
########################
elif BOARDTYPE == "S2MINI":
    if DEBUGFLAG : print("S2MINI board init")
    # I2C connection:
    i2c = busio.I2C(board.SCL, board.SDA)
    
    # currently NC on hardware
    reset_pin = DigitalInOut(board.D4)
    req_pin = DigitalInOut(board.D5)

    # on D10 on hardware 
    irq_pin = DigitalInOut(board.IO14)
    pn532 = PN532_I2C(i2c, debug=False, reset=reset_pin, req=req_pin, irq=irq_pin)


    ic, ver, rev, support = pn532.firmware_version
    if DEBUGFLAG : print("Found PN532 with firmware version: {0}.{1}".format(ver, rev))

    # Solenoid on D4
    solenoid_pin = DigitalInOut(board.IO2)
    solenoid_pin.direction = Direction.OUTPUT

    # Configure the setup
    PIXEL_PIN = board.IO6  # Pin where NeoPixels are connected
    

NUM_PIXELS = 10  # Number of NeoPixels in your strip
# ORDER = neopixel.GRB  # Pixel color channel order
ORDER = neopixel.RGB  # Pixel color channel order
BRIGHTNESS = 1  # Set brightness (0.0 to 1.0)
LED_RATE_MS = 100
last_frame = 0
now = time.monotonic

j=0
fade_dir = 1


# Initialize the NeoPixels
pixels = neopixel.NeoPixel(PIXEL_PIN, NUM_PIXELS, brightness=BRIGHTNESS, auto_write=False, pixel_order=ORDER)

# Define some basic colors
RED = (255, 0, 0)
GREEN = (0, 255, 0)
BLUE = (0, 0, 255)
YELLOW = (255, 150, 0)
PURPLE = (180, 0, 255)
CYAN = (0, 255, 255)
WHITE = (255, 255, 255)

# LED PATTERNS 

def clear():
    """Clears all the pixels."""
    pixels.fill((0, 0, 0))
    pixels.show()

def color_chase(color, wait):
    """Chase a single color down the strip."""
    for i in range(NUM_PIXELS):
        pixels[i] = color
        time.sleep(wait)
        pixels.show()
    clear()

def rainbow_cycle():
    """Draws a rainbow that uniformly distributes itself across all pixels."""
    global j
    j+=1
    if j>255: j=0
    for i in range(NUM_PIXELS):
        pixel_index = (i * 256 // NUM_PIXELS) + j
        pixels[i] = wheel(pixel_index & 255)
        pixels.show()        

def wheel(pos):
    """Generate rainbow colors across 0-255 positions."""
    if pos < 85:
        return (pos * 3, 255 - pos * 3, 0)
    elif pos < 170:
        pos -= 85
        return (255 - pos * 3, 0, pos * 3)
    else:
        pos -= 170
        return (0, pos * 3, 255 - pos * 3)

def breathe(color, wait, steps=50):
    """Gradually lights up and down a color."""
    for i in range(0, steps):
        brightness = (i / float(steps))**2
        pixels.fill([int(c * brightness) for c in color])
        pixels.show()
        time.sleep(wait)
    for i in range(steps, -1, -1):
        brightness = (i / float(steps))**2
        pixels.fill([int(c * brightness) for c in color])
        pixels.show()
        time.sleep(wait)

def breathe(color, steps=50):
    """Gradually lights up and down a color."""
    global j, fade_dir    
    if fade_dir == 1:
        j+=1
        if j>=steps:          
            fade_dir = 0
    else:
        j-=1
        if j<=0:            
            fade_dir = 1 

    brightness = (j / float(steps))**2
    pixels.fill([int(c * brightness) for c in color])
    pixels.show()
  

#############################
## scan for tag / init comms 
## (cannot read or write before init)
#############################
def scanForTag():
    pn532.listen_for_passive_target()
    if DEBUGFLAG : print("Waiting for RFID/NFC card...")
    while True:
        # Check if a card is available to read
        if irq_pin.value == 0:
            read_uid = pn532.get_passive_target()
            if read_uid != 0:
                try:
                    new_uid = read_uid
                    if DEBUGFLAG : print("\nFound card with UID:", [hex(i) for i in new_uid])
                    return new_uid
                    break
                except:
                    if DEBUGFLAG : print("error on scan"    )


        if DEBUGFLAG : print('.',end="")
        # we want to be able to CTRL D here... ///
        time.sleep(0.1)

    # we want to do a clear buffer here ///
      
def scanOnceForTag():
    uid = pn532.read_passive_target(0x00,.05)

    # Extract the first 4 bytes (assuming big-endian order for UID)
    if uid:
        uid_four_bytes = uid[:4]

        # Combine into a 32-bit integer
        uid = int.from_bytes(uid_four_bytes, 'big')        

    return uid


# Function to get the "color" or "flavor" from the UID
def get_token_color(uid):
    uid //= 2
    flavor = uid % 5
    return flavor



############################
## write a block
## take block num, and 4 byte array
############################
def writeBlock(blockNumber, dataToWrite):
    if DEBUGFLAG : print("WRITE BLOCK: "+str(blockNumber))

    pn532.ntag2xx_write_block(blockNumber, dataToWrite)



############################
## write a block as a single int (4 byte limit)
## take block num, and int
############################
def writeBlockInt(blockNumber, intToWrite):
    if DEBUGFLAG : print("WRITE BLOCK: "+str(blockNumber))
    pn532.ntag2xx_write_block(blockNumber,  intToWrite.to_bytes(4, 'big_endian'))


# read a block and return an int
def blockToInt(blockNumber):
    tokenBlock = readBlock(blockNumber)
#    return tokenBlock.from_bytes()
    result = 0

    try: 
        for b in tokenBlock:
            result = (result << 8) + b
        return result
    except:
        if DEBUGFLAG :
            print("blockToInt failed")


############################
## read a block 
############################
def readBlock(blockNumber):
    if DEBUGFLAG : print("READ BLOCK: "+str(blockNumber))
    ntag2xx_block = pn532.ntag2xx_read_block(blockNumber)

    if ntag2xx_block is not None:
        if DEBUGFLAG : print(
            "Reading block: "+str(blockNumber),
            [hex(x) for x in ntag2xx_block],
        )
    else:
        if DEBUGFLAG : print("FAIL BLOCK:" + str(blockNumber))    

    return ntag2xx_block


############################
## write a name (16 chars)
## takes name string and converts to byte array and writes
############################
def writeName(nameString):
    testName = "aldybaldy123456789"
    testName = nameString
    try:
        writeBlock(6,testName[0:4].encode())
        writeBlock(7,testName[4:8].encode())
        writeBlock(8,testName[8:12].encode())
        writeBlock(9,testName[12:16].encode())

    except:
        if DEBUGFLAG : print("fail during write (maybe partial write)")


####################################
##### Control loop and helpers #####
####################################

TOKEN_COLOR = CYAN
last_frame = 0.0
last_scan = 0.0
is_openning = False
is_closing = False
is_open = False

step_count = 3000
open_timer = 0.0

def token_scan(now):
    global last_scan
    global TOKEN_COLOR
    if now - last_scan > .5:
        tokenID = scanOnceForTag()
        # uid = poll_nfc()
        print("Scanning for token...")

        if tokenID != None:
            print("Found token!")
            flavor = get_token_color(tokenID)
            print(f"The 32-bit UID is: {tokenID:#010x}")  # Using #010x to print it as a zero-padded hexadecimal            
            print(f"flavor: {flavor}")           
            
            if flavor == 0:
                TOKEN_COLOR = GREEN
            elif flavor == 1:  
                TOKEN_COLOR = YELLOW
            elif flavor == 2:  
                TOKEN_COLOR = RED    
            elif flavor == 3:  
                TOKEN_COLOR = BLUE
            elif flavor == 4:  
                TOKEN_COLOR = PURPLE    
            else:  
                TOKEN_COLOR = CYAN

            last_scan = now
            return True
        
        last_scan = now  # Reset the last update time
    return False

def led_frame(now):
    global last_frame
    global TOKEN_COLOR
    if now - last_frame > .1:  
        breathe(TOKEN_COLOR,40)  # Rainbow cycle
        last_frame = now  # Reset the last update time 

def open_door(now):
    dir_pin.value = True
    # Generate steps
    for i in range(200):  # Move 200 steps in one direction
        step_pin.value = True
        time.sleep(0.001)  # Determine step interval based on your motor's specifications
        step_pin.value = False
        time.sleep(0.001)

def close_door(now):
    dir_pin.value = False
    # Move back
    for i in range(200):  # Move 200 steps in the other direction
        step_pin.value = True
        time.sleep(0.001)
        step_pin.value = False
        time.sleep(0.001)

# Red 1, green 2, yellow 3, blue 4

def clear_token():
    # Write the block back to the tag
    writeBlock(6, "0000".encode())


# Write a specific bit to the block
def write_to_token():
    # Write the block back to the tag
    writeBlock(6, "1111".encode())

# Read a specific bit from the block
def read_from_token():
    # Read the block
    print("READING")
    block = readBlock(6)
    print(block)

def caught_em_all():
    block = readBlock(6)
    return True if block == "1111".encode() else False 

########################
## main loop
########################

while True:     # loop tp listen to serial input.  if tones.txt is created, program will auto reload    
    now = time.monotonic()

    # Do NFC Scan
    if is_openning:
        step_pin.value = True
        time.sleep(0.001)
        step_pin.value = False
        time.sleep(0.001)
        step_count -= 1

    if step_count <= 0:
        is_openning = False
        is_open = True
        step_count = 3000

    if is_open:
        time.sleep(5)
        is_open = False
        is_closing = True

    if is_closing:
        dir_pin.value = False
        step_pin.value = True
        time.sleep(0.001)
        step_pin.value = False
        time.sleep(0.001)
        step_count -= 1

    if step_count <= 0:
        is_closing = False
        step_count = 3000

    # if is_openning:
    #     open_door(now)
    # elif is_closing:
    #     pass
    # elif is_open:
    #     pass
    # else:
        # Do LED frame
    led_frame(now)       
    
    if not is_openning and not is_closing and not is_open:
        # # Do NFC Scan
        if token_scan(now):
            # Fake write to token
            write_to_token()
            if caught_em_all():
                print("You caught them all!")
                is_openning = True
    
    # Do Motor things
    # open_door(now)
    # close_door(now)
    
