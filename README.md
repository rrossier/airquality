# airquality
Air quality station with RaspberryPi and your own web-server

- airquality.py sends sensor data from a RPi to the web-server (based on [Luftdaten script from Pimoroni](https://learn.pimoroni.com/tutorial/sandyj/enviro-plus-and-luftdaten-air-quality-station))
- api/push.php is the server endpoint for saving the data
- view/index.php is the visualisation page based on saved data

Sensors:
---
- PMS5003 particulate sensor
- MICS6814 analog gas sensor
- BME280 temperature, pressure, and humidity sensor

ToDo:
---
- implement two y-axis on graphs
- limit nb of points on graphs
- add transformer function support with parser
- add AQI function for overall score
