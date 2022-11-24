/* Example sketch to control a stepper motor with TB6600 stepper motor driver, AccelStepper library and Arduino: acceleration and deceleration. More info: https://www.makerguides.com */

// Include the AccelStepper library:
#include <AccelStepper.h>
#include <Servo.h>
#include <Bounce2.h>

#define dirPin 6 //direction of stepperw
#define stepPin 7 //send steps to stepper
#define motorInterfaceType 1
#define homeSwitchPin 4 //limit switch pin
#define triggerPin 5 //press button to fire --replace with a low signal from arduino
#define latchServoPin 3
#define smokeServoPin 2
#define triggerPin2 12
int steps;

int buttonState;             // the current reading from the input pin
int lastButtonState = 0;   // the previous reading from the input pin
// the following variables are unsigned longs because the time, measured in
// milliseconds, will quickly become a bigger number than can be stored in an int.
unsigned long lastDebounceTime = 0;  // the last time the output pin was toggled
unsigned long debounceDelay = 50;   

// Create a new instance of the AccelStepper class:
AccelStepper stepper = AccelStepper(motorInterfaceType, stepPin, dirPin);

Servo latchServo;
Servo smokeServo;

Bounce limitSwitch = Bounce(); // Instantiate a Bounce object

void setup() {
  // Set the maximum speed and acceleration:
  stepper.setMaxSpeed(2000);
  stepper.setAcceleration(1000);
  Serial.begin(115200);
  delay(100);

  limitSwitch.attach(homeSwitchPin,INPUT_PULLUP); // Attach the debouncer to a pin with INPUT_PULLUP mode
  limitSwitch.interval(25); // Use a debounce interval of 25 milliseconds
//  trigger.attach(triggerPin,INPUT_PULLUP); // Attach the debouncer to a pin with INPUT_PULLUP mode
//  trigger.interval(25); // Use a debounce interval of 25 milliseconds

  pinMode(triggerPin, INPUT_PULLUP); //limit switch to detect home
  pinMode(triggerPin2, INPUT_PULLUP);
//  pinMode(homeSwitchPin, INPUT_PULLUP); //limit switch to detect home
  
  latchServo.attach(latchServoPin); 
  smokeServo.attach(smokeServoPin); 
  smokeServo.write(0);
  goHome(); //zero the stepper at home
  
  delay(200);
}

void loop() {
    int reading = digitalRead(triggerPin);
    Serial.print("reading: ");
    Serial.println(reading);
    Serial.print("bs: ");
    Serial.println(buttonState);

    if (reading != lastButtonState) {
    // reset the debouncing timer
    lastDebounceTime = millis();
  }
    if ((millis() - lastDebounceTime) > debounceDelay) {
    // whatever the reading is at, it's been there for longer than the debounce
    // delay, so take it as the actual current state:
    // if the button state has changed:
    if (reading != buttonState) {
      buttonState = reading;
      Serial.print("trigger2: ");
      Serial.println(digitalRead(buttonState));
      // only toggle the LED if the new button state is LOW
      if (buttonState == LOW) {
        Serial.println("triggered");
        fill(30,6000); //rotate servo 30 degrees for 4 seconds
  //    goHome(); // just ensure the drive tray is close to the cannon
        arm(700); //pull back bungee 4200 steps
      fire(); //release servo and go home
      }
    }
  }

  
}

void goHome() { //need to add debounce
  limitSwitch.update();
  latchServo.write(0); //make sure latch is open so it doesn't catch on the way up
  stepper.setCurrentPosition(0);
  stepper.moveTo(-500); 
      stepper.runToPosition();
    stepper.setCurrentPosition(0);
}


void goHomeback() { //need to add debounce
  limitSwitch.update();
  latchServo.write(0); //make sure latch is open so it doesn't catch on the way up
  delay(100);
  Serial.println("Going home");
//  while (digitalRead(homeSwitchPin)) {  // Do this until the switch is closed
//    stepper.setSpeed(500); //backward
//    stepper.runSpeed();
//    Serial.println("GOING home...");
//  }

    Serial.print("Limit switch hit:");
    Serial.println(homeSwitchPin);
    stepper.setCurrentPosition(0);
    stepper.moveTo(-4300); //come off the switch to get carriage
    stepper.runToPosition();
    stepper.setCurrentPosition(0);
    //zero the counter
    Serial.println("home!");
}

void fill(int servoEnd, int fillTime){
  Serial.println("filling");
  smokeServo.write(servoEnd); //hold down button
  delay(fillTime);
  smokeServo.write(0); //remove servo arm from buttom
  Serial.println("done filling");
}

void arm(int distance) {
  latchServo.write(0); //move servo arm to catch tray
  Serial.println("arming");
  stepper.moveTo(-4000); //come off the switch to get carriage
  stepper.runToPosition();
  delay(200);
  latchServo.write(40); //move servo arm to catch tray
  delay(300); //wait for servo to move
  stepper.moveTo(-200); //move stepper back to arm
//  stepper.setCurrentPosition(0);
  stepper.runToPosition(); 
  Serial.println("done arming");
}

void fire(){
  delay(1000);
  Serial.println("firing!");
  latchServo.write(0);
  delay(200);
}
