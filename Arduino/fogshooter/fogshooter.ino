/* Example sketch to control a stepper motor with TB6600 stepper motor driver, AccelStepper library and Arduino: acceleration and deceleration. More info: https://www.makerguides.com */

// Include the AccelStepper library:
#include <AccelStepper.h>
#include <Servo.h>
#include <Bounce2.h>

#define dirPin 6 //direction of stepper
#define stepPin 7 //send steps to stepper
#define motorInterfaceType 1
#define homeSwitchPin 4 //limit switch pin
#define triggerPin 5 //press button to fire --replace with a low signal from arduino
#define latchServoPin 3
#define smokeServoPin 2
#define webPin 12
int steps;

// Latch values
#define latchOpen 0
#define latchClosed 50
#define latchDelay 1500

// Stepper coordinates
#define stepperBackward 500
#define stepperStartPos -200
#define stepperLoadPos -4200

// Smoke machine
#define smokeDeactive 0
#define smokeActive 7
#define smokeFillTime 6000

int buttonState;             // the current reading from the input pin
int webSignal;
int homeSwitchState = 1;

enum State {
  CALIBRATE = 0,
  LOAD,
  FIRE,
  IDLE
};
State state;

// Create a new instance of the AccelStepper class:
AccelStepper stepper = AccelStepper(motorInterfaceType, stepPin, dirPin);

Servo latchServo;
Servo smokeServo;

 // Instantiate a Bounce object
Bounce triggerButton = Bounce();
Bounce homeSwitch = Bounce();

void setup() {
  // Set the maximum speed and acceleration:
  stepper.setMaxSpeed(2000);
  stepper.setAcceleration(1000);
  Serial.begin(115200);
  delay(100);

  // Attach the debouncer to a pin with INPUT_PULLUP mode
  triggerButton.attach(triggerPin, INPUT_PULLUP);
  homeSwitch.attach(homeSwitchPin, INPUT_PULLUP);
  // Use a debounce interval of 25 milliseconds
  triggerButton.interval(25);
  homeSwitch.interval(25);

  pinMode(webPin, INPUT_PULLUP);
  pinMode(homeSwitchPin, INPUT_PULLUP); //limit switch to detect home
  
  latchServo.attach(latchServoPin); // Must write initial value to latch servo as default is unknown
  latchServo.write(latchOpen);
  delay(latchDelay);
  smokeServo.attach(smokeServoPin); 
  smokeServo.write(smokeDeactive);
  delay(latchDelay);

  /* Set initial state */
  state = CALIBRATE;
}

void loop() {
  /* Refresh button state */
  triggerButton.update();
  /* Read web signal */
  webSignal = 0; // digitalRead(webPin);

  switch (state)
  {
  case CALIBRATE:
    /* Only runs once */
    Serial.println("Calibrating");
    calibrateStepper();
    Serial.println("Calibration complete");
    
    /* Once calibrated, change to load state */
    Serial.println("CALIBRATE --> LOAD");
    state = LOAD;
    break;

  case LOAD:
    /* Move forwards, latch, retract and fill */
    Serial.println("Loading canon!");
    loadCanon();

    /* Once loaded, change to idle state */
    Serial.println("LOAD --> IDLE");
    state = IDLE;
    break;

  case FIRE:
    /* Fire cannon! */
    Serial.println("Fire in the hole!");
    fireCanon();
    Serial.println("FIRE --> LOAD");
    state = LOAD;
    break;
  
  case IDLE:
    /* Read button press or web server signal */
    if (triggerButton.read() == 0 || webSignal == 1) 
    {
      /* If signal received, set to fire state */
      Serial.println("IDLE --> FIRE");
      state = FIRE;
    }
    break;

  default:
    Serial.println("Invalid state");
    break;
  }
}

void calibrateStepper() {
  // Move stepper backward until home switch is toggled
  while (homeSwitchState) {
    homeSwitch.update();
    homeSwitchState = homeSwitch.read();
    stepper.setSpeed(stepperBackward);
    stepper.runSpeed();
  }
  // Set orin to home switch 
  stepper.setCurrentPosition(0);

  // Move stepper slightly forwards
  stepper.moveTo(stepperStartPos); 
  stepper.runToPosition();
}

void loadCanon() {
  /* Advance towards carriage */
  stepper.moveTo(stepperLoadPos);
  stepper.runToPosition();

  /* Close latch */
  latchServo.write(latchClosed);
  delay(latchDelay);

  /* Retract to load */
  stepper.moveTo(stepperStartPos);
  stepper.runToPosition();

  /* Start filling with smoke */
  smokeServo.write(smokeActive);
  delay(smokeFillTime);
  smokeServo.write(smokeDeactive);
}

void fireCanon() {
  /* Fire canon by opening latch */
  latchServo.write(latchOpen);
  delay(latchDelay);
}