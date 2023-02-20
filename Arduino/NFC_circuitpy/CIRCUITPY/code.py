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
DEBUGFLAG = 1


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


#initialize empty token buffer
tokenBuffer = blankToken







########################
## init
########################
if DEBUGFLAG : print("init I2C")


# I2C connection:
i2c = busio.I2C(board.SCL, board.SDA)
 
# currently NC on hardware
reset_pin = DigitalInOut(board.D4)
req_pin = DigitalInOut(board.D5)

# on D10 on hardware 
irq_pin = DigitalInOut(board.IO14)
pn532 = PN532_I2C(i2c, debug=False, reset=reset_pin, req=req_pin, irq=irq_pin)


ic, ver, rev, support = pn532.firmware_version
print("Found PN532 with firmware version: {0}.{1}".format(ver, rev))

token_uid = bytearray(7)
token_uid[0:7] = b"\x69\x69\x69\x69\x69\x69\x69"
print("\nOld card UID:", [hex(i) for i in token_uid])

testBlockAs = b"\xAA\xAA\xAA\xAA"
testBlockBs = b"\xBB\xBB\xBB\xBB"






#############################
## scan for tag / init comms 
## (cannot read or write before init)
#############################
def scanForTag():
    pn532.listen_for_passive_target()
    print("Waiting for RFID/NFC card...")
    while True:
        # Check if a card is available to read
        if irq_pin.value == 0:
            read_uid = pn532.get_passive_target()
            if read_uid != 0:
                new_uid = read_uid
                print("\nFound card with UID:", [hex(i) for i in new_uid])
                return new_uid
                break

        print('.')
        time.sleep(0.1)




############################
## read a block 
############################
def readBlock(blockNumber):
    print("READ BLOCK: "+str(blockNumber))
    ntag2xx_block = pn532.ntag2xx_read_block(blockNumber)


    if ntag2xx_block is not None:
        print(
            "Reading block: "+str(blockNumber),
            [hex(x) for x in ntag2xx_block],
        )
    else:
        print("FAIL BLOCK:" + str(blockNumber))    

    return ntag2xx_block




############################
## write a block
## take block num, and 4 byte array
############################
def writeBlock(blockNumber, dataToWrite):
    print("WRITE BLOCK: "+str(blockNumber))

    pn532.ntag2xx_write_block(blockNumber, dataToWrite)



def writeName(nameString):
    testName = "aldybaldy123456789"
    testName = nameString
    try:
        writeBlock(6,testName[0:4].encode())
        writeBlock(7,testName[4:8].encode())
        writeBlock(8,testName[8:12].encode())
        writeBlock(9,testName[12:16].encode())

    except:
        print("fail during write (maybe partial write)")


########################
## main loop
########################
while True:                                                     # loop tp listen to serial input.  if tones.txt is created, program will auto reload
    #print('trying to enter serail read loop')
    if supervisor.runtime.serial_bytes_available:               # if we have received serial bytes
        value = input().strip()                                 # store the input as 'value' variable without leading/trailing whitespace 

        if value == "":                                         # remove erroneus newlines
            continue

        print("RX: {}".format(value))                           # repeat back the command for debugging

        

        #do somefin
        if value == "scan":
            print("entering scan mode")
            tokenID = scanForTag()
            print('found tag eh')

            buffer = '0x'
            for i in tokenID:
                buffer += hex(i)[2:4]
            print("tagID " + buffer)


        elif value == "read":
            print("entering read mode")
            block = readBlock(6)
            print('done with read mode')
    
        elif value == "write":
            print("entering write mode")
            writeBlock(6,testBlockAs)
            print('done with write mode')
    

        elif value[0:4] == "json":
            print("json switch")
            JSONString = value[4:]
            print("json command: "+JSONString)
            JSONobj = json.loads(JSONString)
            print(JSONobj['name'])
            writeName(str(JSONobj['name']))


        ##name is stored as 32 ascii bytes in blocks 6,7,8,9
        elif value == "writeName":
            print("write name mode")
           
            testName = "aldybaldy123456789"
            writeBlock(6,testName[0:4].encode())
            writeBlock(7,testName[4:8].encode())
            writeBlock(8,testName[8:12].encode())
            writeBlock(9,testName[12:16].encode())
          


        ##name is stored as 16 ascii bytes in blocks 6,7,8,9
        elif value == "readName":
            print("json switch")
            try: 
                block = readBlock(6)
                block += readBlock(7)
                block += readBlock(8)
                block += readBlock(9)
                print(block.decode())

            except:
                print('error')


        ##name is stored as 16 ascii bytes in blocks 6,7,8,9
        elif value == "readNameJSON":
            print("json switch")
            try: 
                block = readBlock(6)
                block += readBlock(7)
                block += readBlock(8)
                block += readBlock(9)
                print(block.decode())
                buffer = {}
                buffer['name'] = block.decode()
                print("json: " + json.dumps(buffer))
                

            except:
                print('error')

        elif value == "writeMasterJSON":
            print("json switch")
            try: 




        ##name is stored as 16 ascii bytes in blocks 6,7,8,9
        elif value == "readMapJSON":
            print("json switch")
            try: 
                block = readBlock(10)
                block += readBlock(11)
                block += readBlock(12)
                block += readBlock(13)

    #indices & booleans
    tokenBuffer["dd_equiped_index"]       =    readBlock(10)
    tokenBuffer["hv_npcs_alive"]          =    readBlock(11)
    tokenBuffer["hv_quest_complete"]      =    readBlock(12)
    tokenBuffer["dragons_den_won"]        =    readBlock(13)
    tokenBuffer["pandoras_box"]           =    readBlock(14)
    tokenBuffer["color_code"]             =    readBlock(15)
 
 
    #items
    tokenBuffer["sword"]        = readBlock(50)
    tokenBuffer["axe"]          = readBlock(51)
    tokenBuffer["bow"]          = readBlock(52)
    tokenBuffer["armor"]        = readBlock(53)
    tokenBuffer["dagger"]       = readBlock(54)
    tokenBuffer["staff"]        = readBlock(55)
    tokenBuffer["spear"]        = readBlock(56)
    tokenBuffer["claws"]        = readBlock(57)
    tokenBuffer["gun"]          = readBlock(58)
    tokenBuffer["mushroom"]     = readBlock(59)
    tokenBuffer["shield"]       = readBlock(60)
    tokenBuffer["orb"]          = readBlock(61)
    tokenBuffer["UFO"]          = readBlock(62)






                print(block.decode())

            except:
                print('error')
