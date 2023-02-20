"""
ello m8
"""

import time
import board
import busio
import supervisor
import json
from digitalio import DigitalInOut
from adafruit_pn532.i2c import PN532_I2C

# set to 1 to enable debug messages
DEBUGFLAG = 0

# set appropriately - the small purp boards are "S2MINI"
BOARDTYPE = "tft"
#BOARDTYPE = "S2MINI"


blankToken = {
    "uid"               :   0,
    "email"             :   "nullll",

    "dd_equiped_index" :    0,  # 0 = no weapon, 1-13 refences dd_items
    "hv_npcs_alive":        1,  # t/f
    "hv_quest_complete":    0,  # t/f
    "dragons_den_won":      0,  # t/f
    "pandoras_box":         5,  # 0-9? small int
    "color_code":           0,  # 0-? small int


    #inventory stored at raw level (should be nested)    
    "sword"     :   0,
    "axe"       :   0, 
    "bow"       :   0, 
    "armor"     :   0, 
    "dagger"    :   0, 
    "staff"     :   0, 
    "spear"     :   0, 
    "claws"     :   0, 
    "gun"       :   0, 
    "mushroom"  :   0, 
    "shield"    :   0, 
    "orb"       :   0, 
    "UFO"       :   0,
    
}


masterToken = {
    "uid"               :   69,
    "email"             :   "MASTAH",

    "dd_equiped_index" :    9,  # 0 = no weapon, 1-13 refences dd_items
    "hv_npcs_alive":        1,  # t/f
    "hv_quest_complete":    1,  # t/f
    "dragons_den_won":      1,  # t/f
    "pandoras_box":         5,  # 0-9? small int
    "color_code":           1,  # 0-? small int


    #inventory stored at raw level (should be nested)    
    "sword"     :   1,
    "axe"       :   1, 
    "bow"       :   1, 
    "armor"     :   1, 
    "dagger"    :   1, 
    "staff"     :   1, 
    "spear"     :   1, 
    "claws"     :   1, 
    "gun"       :   1, 
    "mushroom"  :   1, 
    "shield"    :   1, 
    "orb"       :   1, 
    "UFO"       :   1,
    
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
    reset_pin = DigitalInOut(board.D8)
    req_pin = DigitalInOut(board.D9)

    # on D10 on hardware 
    irq_pin = DigitalInOut(board.IO14)
    pn532 = PN532_I2C(i2c, debug=False, reset=reset_pin, req=req_pin, irq=irq_pin)


    ic, ver, rev, support = pn532.firmware_version
    if DEBUGFLAG : print("Found PN532 with firmware version: {0}.{1}".format(ver, rev))


# legacy
token_uid = bytearray(7)
token_uid[0:7] = b"\x69\x69\x69\x69\x69\x69\x69"
if DEBUGFLAG : print("\nOld card UID:", [hex(i) for i in token_uid])







# test byte arrays
testBlockAs = b"\xAA\xAA\xAA\xAA"
testBlockBs = b"\xBB\xBB\xBB\xBB"
testBlock0s = b"\x00\x00\x00\x00"


# read a block and return an int
def blockToInt(blockNumber):
    tokenBlock = readBlock(blockNumber)
#    return tokenBlock.from_bytes()
    result = 0
    for b in tokenBlock:
        result = (result << 8) + b
    return result











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


        if DEBUGFLAG : print('.')
        time.sleep(0.1)




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
    if supervisor.runtime.serial_bytes_available:               # if we have received serial bytes
        value = input().strip()                                 # store the input as 'value' variable without leading/trailing whitespace 

        if value == "":                                         # remove erroneus newlines
            continue

        if DEBUGFLAG : print("RX: {}".format(value))                           # repeat back the command for debugging

        

        #do somefin
        if value == "scan":
            if DEBUGFLAG : print("entering scan mode")
            tokenID = scanForTag()
            if DEBUGFLAG : print('found tag eh')

            buffer = '0x'
            for i in tokenID:
                buffer += hex(i)[2:4]
            if DEBUGFLAG : print("tagID " + buffer)


        elif value == "read":
            if DEBUGFLAG : print("entering read mode")
            block = readBlock(6)
            if DEBUGFLAG : print('done with read mode')
    
        elif value == "write":
            if DEBUGFLAG : print("entering write mode")
            writeBlock(6,testBlockAs)
            if DEBUGFLAG : print('done with write mode')
    

        elif value[0:4] == "json":
            if DEBUGFLAG : print("json switch")
            JSONString = value[4:]
            if DEBUGFLAG : print("json command: "+JSONString)
            JSONobj = json.loads(JSONString)
            if DEBUGFLAG : print(JSONobj['name'])
            writeName(str(JSONobj['name']))


        ##name is stored as 32 ascii bytes in blocks 6,7,8,9
        elif value == "writeName":
            if DEBUGFLAG : print("write name mode")
           
            testName = "aldybaldy123456789"
            writeBlock(6,testName[0:4].encode())
            writeBlock(7,testName[4:8].encode())
            writeBlock(8,testName[8:12].encode())
            writeBlock(9,testName[12:16].encode())
          


        ##name is stored as 16 ascii bytes in blocks 6,7,8,9
        elif value == "readName":
            if DEBUGFLAG : print("json switch")
            try: 
                block = readBlock(6)
                block += readBlock(7)
                block += readBlock(8)
                block += readBlock(9)
                if DEBUGFLAG : print(block.decode())

            except:
                if DEBUGFLAG : print('error')


        ##name is stored as 16 ascii bytes in blocks 6,7,8,9
        elif value == "readNameJSON":
            if DEBUGFLAG : print("json switch")
            try: 
                block = readBlock(6)
                block += readBlock(7)
                block += readBlock(8)
                block += readBlock(9)
                if DEBUGFLAG : print(block.decode())
                buffer = {}
                buffer['name'] = block.decode()
                print(json.dumps(buffer))
                

            except:
                if DEBUGFLAG : print('error')

        elif value == "writeMasterJSON":
            if DEBUGFLAG : print("json switch")




        ##name is stored as 16 ascii bytes in blocks 6,7,8,9
        elif value == "readMapJSON":
            if DEBUGFLAG : print("json switch")
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
            tokenBuffer["sword"]        = blockToInt(50)
            tokenBuffer["axe"]          = blockToInt(51)
            tokenBuffer["bow"]          = blockToInt(52)
            tokenBuffer["armor"]        = blockToInt(53)
            tokenBuffer["dagger"]       = blockToInt(54)
            tokenBuffer["staff"]        = blockToInt(55)
            tokenBuffer["spear"]        = blockToInt(56)
            tokenBuffer["claws"]        = blockToInt(57)
            tokenBuffer["gun"]          = blockToInt(58)
            tokenBuffer["mushroom"]     = blockToInt(59)
            tokenBuffer["shield"]       = blockToInt(60)
            tokenBuffer["orb"]          = blockToInt(61)
            tokenBuffer["UFO"]          = blockToInt(62)
            

            print(json.dumps(tokenBuffer))





        elif value == "writeMaster":
            if DEBUGFLAG : print("writing master token")
     
            #indices & booleans
            writeBlockInt(10, masterToken["dd_equiped_index"])
            writeBlockInt(11, masterToken["hv_npcs_alive"])
            writeBlockInt(12, masterToken["hv_quest_complete"])
            writeBlockInt(13, masterToken["dragons_den_won"])
            writeBlockInt(14, masterToken["pandoras_box"])
            writeBlockInt(15, masterToken["color_code"])
        
        
            #items
            writeBlockInt(50, masterToken["sword"])
            writeBlockInt(51, masterToken["axe"])
            writeBlockInt(52, masterToken["bow"])
            writeBlockInt(53, masterToken["armor"])
            writeBlockInt(54, masterToken["dagger"])
            writeBlockInt(55, masterToken["staff"])
            writeBlockInt(56, masterToken["spear"])
            writeBlockInt(57, masterToken["claws"])
            writeBlockInt(58, masterToken["gun"])
            writeBlockInt(59, masterToken["mushroom"])
            writeBlockInt(60, masterToken["shield"])
            writeBlockInt(61, masterToken["orb"])
            writeBlockInt(62, masterToken["UFO"])
            

