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
    PIXEL_PIN = board.D6  # Pin where NeoPixels are connected
    NUM_PIXELS = 30  # Number of NeoPixels in your strip
    ORDER = neopixel.GRB  # Pixel color channel order
    BRIGHTNESS = 1  # Set brightness (0.0 to 1.0)



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


########################
## main loop
########################
while True:                                                     # loop tp listen to serial input.  if tones.txt is created, program will auto reload
    
    #if DEBUGFLAG : print('trying to enter serail read loop')
    tokenID = scanForTag()

    if DEBUGFLAG : print("writing master token")
     
    #indices & booleans
    writeBlockInt(10, masterToken["dd_equiped_index"])
    writeBlockInt(11, masterToken["hv_npcs_alive"])
    writeBlockInt(12, masterToken["hv_quest_complete"])
    writeBlockInt(13, masterToken["dragons_den_won"])
    writeBlockInt(14, masterToken["pandoras_box"])
    writeBlockInt(15, masterToken["color_code"])


    #items
    writeBlockInt(16, masterToken["sword"])
    writeBlockInt(17, masterToken["axe"])
    writeBlockInt(18, masterToken["bow"])
    writeBlockInt(19, masterToken["armor"])
    writeBlockInt(20, masterToken["dagger"])
    writeBlockInt(21, masterToken["staff"])
    writeBlockInt(22, masterToken["spear"])
    writeBlockInt(23, masterToken["claws"])
    writeBlockInt(24, masterToken["gun"])
    writeBlockInt(25, masterToken["mushroom"])
    writeBlockInt(26, masterToken["shield"])
    writeBlockInt(27, masterToken["orb"])
    writeBlockInt(28, masterToken["UFO"])
    
    print("write success")

    if DEBUGFLAG : print("readMapJSON")
    try: 
        block = readBlock(10)
        block += readBlock(11)
        block += readBlock(12)
        block += readBlock(13)
        if DEBUGFLAG : print(block)

    except:
        if DEBUGFLAG : print("error reading blocks 10-13")

    #indices & booleans
    tokenBuffer["dd_equiped_index"]       =    blockToInt(10)
    tokenBuffer["hv_npcs_alive"]          =    blockToInt(11)
    tokenBuffer["hv_quest_complete"]      =    blockToInt(12)
    tokenBuffer["dragons_den_won"]        =    blockToInt(13)
    tokenBuffer["pandoras_box"]           =    blockToInt(14)
    tokenBuffer["color_code"]             =    blockToInt(15)

    #items
    tokenBuffer["sword"]        = blockToInt(16)
    tokenBuffer["axe"]          = blockToInt(17)
    tokenBuffer["bow"]          = blockToInt(18)
    tokenBuffer["armor"]        = blockToInt(19)
    tokenBuffer["dagger"]       = blockToInt(20)
    tokenBuffer["staff"]        = blockToInt(21)
    tokenBuffer["spear"]        = blockToInt(22)
    tokenBuffer["claws"]        = blockToInt(23)
    tokenBuffer["gun"]          = blockToInt(24)
    tokenBuffer["mushroom"]     = blockToInt(25)
    tokenBuffer["shield"]       = blockToInt(26)
    tokenBuffer["orb"]          = blockToInt(27)
    tokenBuffer["UFO"]          = blockToInt(28)
    
    print(json.dumps(tokenBuffer))

    time.sleep(3)    
