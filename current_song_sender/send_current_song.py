#Start this script with the folder you want to monitor for changes as the argument. For example, python send_current_song.py "C:/Users/Dave/AppData/Roaming/Stepmania 5/Save/Out"

import sys
import time
import logging
import urllib
from watchdog.observers import Observer
from watchdog.events import FileSystemEventHandler

class CustomFileEventHandler(FileSystemEventHandler):
    def on_modified(self, event):
        file_name = event.src_path
        print(">")
        try:
                with open("C:/Users/Dave/AppData/Roaming/Stepmania 5/Save/Out/SongInfoUpload.txt", 'r') as file:
					data = file.read().replace('\n', '')
					global olddata
					if data != olddata:
						olddata = data
						newdata = urllib.quote_plus(data)
						link = "https://www.davelinger.com/twitch/status.php?data=" + newdata
						f = urllib.urlopen(link)
						response = f.read()
						print(response)
					else:
						print(">>")
        except:
			print("Error opening file")
			pass

if __name__ == "__main__":
    path = sys.argv[1] if len(sys.argv) > 1 else '.'
    print("Started monitoring " + path)
    olddata = ""
    event_handler = CustomFileEventHandler()
    observer = Observer()
    observer.schedule(event_handler, path)
    observer.start()
    try:
        while True:
            time.sleep(1)
    except KeyboardInterrupt:
        observer.stop()
    observer.join()
