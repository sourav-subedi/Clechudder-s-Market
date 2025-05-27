import serial
import winsound  # Works only on Windows

# CHANGE THIS to your correct COM port
arduino_port = 'COM3'
baud_rate = 9600

# Define known tag UIDs
tag1_uid = "33652903"
tag2_uid = "994ACC01"

# tag1_uid = "B6BEBF01"
# tag2_uid = "016C8F02"

try:
    ser = serial.Serial(arduino_port, baud_rate)
    print(f"Listening on {arduino_port}...")

    while True:
        line = ser.readline().decode('utf-8').strip()
        print("Scanned UID:", line)

        if line == tag1_uid:
            print("Tag 1 detected")
            winsound.Beep(1000, 600)  # High-pitched tone

        elif line == tag2_uid:
            print("Tag 2 detected")
            winsound.Beep(600, 600)   # Lower tone, longer

        else:
            print("Unknown tag")

except Exception as e:
    print("Error:", e)
