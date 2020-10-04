#!/usr/bin/env python3

import requests
import ST7735
import time
from enviroplus import gas
from bme280 import BME280
from pms5003 import PMS5003, ReadTimeoutError
from subprocess import PIPE, Popen, check_output
from PIL import Image, ImageDraw, ImageFont
from fonts.ttf import RobotoMedium as UserFont

try:
    from smbus2 import SMBus
except ImportError:
    from smbus import SMBus

print("""

""")

bus = SMBus(1)

# Create BME280 instance
bme280 = BME280(i2c_dev=bus)

# Create LCD instance
disp = ST7735.ST7735(
    port=0,
    cs=1,
    dc=9,
    backlight=12,
    rotation=270,
    spi_speed_hz=10000000
)

# Create PMS5003 instance
pms5003 = PMS5003()

# server
server_address = "https://romainrossier.com/airquality/api/push.php"
key = ""

#
time_lag = 15

# Compensation factor for temperature
comp_factor = 2.25

# Text settings
font_size = 16
font = ImageFont.truetype(UserFont, font_size)

# Read values from BME280, MICS6814, and PMS5003 and return as dict
def read_values():
    values = {}
    cpu_temp = get_cpu_temperature()
    raw_temp = bme280.get_temperature()
    raw_gas = gas.read_all()
    comp_temp = raw_temp - ((cpu_temp - raw_temp) / comp_factor)
    values["temperature"] = "{:.2f}".format(comp_temp)
    values["pressure"] = "{:.2f}".format(bme280.get_pressure() * 100)
    values["humidity"] = "{:.2f}".format(bme280.get_humidity())
    values["reducing"] = "{:.2f}".format(raw_gas.reducing)
    values["oxidising"] = "{:.2f}".format(raw_gas.oxidising)
    values["nh3"] = "{:.2f}".format(raw_gas.nh3)
    try:
        pm_values = pms5003.read()
    except ReadTimeoutError:
        pms5003.reset()
        pm_values = pms5003.read()
    
    values["P0.3_air"] = str(pm_values.pm_per_1l_air(0.3))
    values["P0.5_air"] = str(pm_values.pm_per_1l_air(0.5))
    values["P1_air"] = str(pm_values.pm_per_1l_air(1))
    values["P2.5_m3"] = str(pm_values.pm_ug_per_m3(2.5))
    values["P10_m3"] = str(pm_values.pm_ug_per_m3(10))
    values["time"] = time.time()
    return values


# Get CPU temperature to use for compensation
def get_cpu_temperature():
    process = Popen(['vcgencmd', 'measure_temp'], stdout=PIPE, universal_newlines=True)
    output, _error = process.communicate()
    return float(output[output.index('=') + 1:output.rindex("'")])


# Get Raspberry Pi serial number to use as ID
def get_serial_number():
    with open('/proc/cpuinfo', 'r') as f:
        for line in f:
            if line[0:6] == 'Serial':
                return line.split(":")[1].strip()


# Check for Wi-Fi connection
def check_wifi():
    if check_output(['hostname', '-I']):
        return True
    else:
        return False


# Display Raspberry Pi serial and Wi-Fi status on LCD
def display_status():
    # Initialize display
    disp.begin()
    # Width and height to calculate text position
    WIDTH = disp.width
    HEIGHT = disp.height
    wifi_status = "connected" if check_wifi() else "disconnected"
    text_colour = (255, 255, 255)
    back_colour = (0, 170, 170) if check_wifi() else (85, 15, 15)
    id = get_serial_number()
    message = "{}\nWi-Fi: {}".format(id, wifi_status)
    img = Image.new('RGB', (WIDTH, HEIGHT), color=(0, 0, 0))
    draw = ImageDraw.Draw(img)
    size_x, size_y = draw.textsize(message, font)
    x = (WIDTH - size_x) / 2
    y = (HEIGHT / 2) - (size_y / 2)
    draw.rectangle((0, 0, 160, 80), back_colour)
    draw.text((x, y), message, font=font, fill=text_colour)
    disp.display(img)


def send_to_server(values):
    formatted_values = [{"value_type": key, "value": val} for key, val in values.items()]

    post_request = requests.post(
        server_address,
        json={
            "values": formatted_values
        },
        headers={
            "X-key": key,
            "Content-Type": "application/json",
            "cache-control": "no-cache"
        }
    )

    if post_request.success:
        return True
    else:
        return False


# Display Raspberry Pi serial and Wi-Fi status
print("Raspberry Pi serial: {}".format(get_serial_number()))
print("Wi-Fi: {}\n".format("connected" if check_wifi() else "disconnected"))

time_since_update = 0
update_time = time.time()

# Main loop to read data, display, and send to server
while True:
    try:
        time_since_update = time.time() - update_time
        if time_since_update > time_lag:
            values = read_values()
            print(values)
            resp = send_to_server(values)
            update_time = time.time()
            print("Response: {}\n".format("ok" if resp else "failed"))
        display_status()
    except Exception as e:
        print(e)