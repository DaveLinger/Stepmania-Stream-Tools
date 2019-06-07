# Stepmania-Stream-Tools
Tools and utilities for interacting with Stepmania 5 to provide added features for live streaming

There are basically 3 pieces to the code here:

## Song Scraper
This software looks through your Stepmania Songs folder and uploads info for each song to a remote mysql database.

## Song Requests Scripts
This software allows incoming calls to request songs. I use this with moobot, a free web-based twitch bot. When somebody says "!request Some Song", moobot sends their twitch username and their requested song title to my requests script. The script searches the database created by the song scraper for songs matching the title. If there is more than one match, it returns the top 5 matches with their titles, packs, and ID numbers. A twitch user can use "!requestid 1234" to request a song by ID instead of by name. This is useful to pick a specific version of a song with a title shared by many tracks.

### Examples:

---

**ddrdave**: !request Algorithm

(moobot makes a call to "https://www.davelinger.com/twitch/request.php?user=ddrdave&song=Algorithm")

(script responds with "ddrdave requested ALGORITHM from DDR A"

**moobot:** ddrdave requested ALGORITHM from DDR A

---

**ddrdave**: !request Trip Machine

(moobot makes a call to "https://www.davelinger.com/twitch/request.php?user=ddrdave&song=Trip+Machine")

(script responds with "Top matches (request with !requestid \[song id\]): \[ 1090 > SP-TRIP MACHINE\~JUNGLE MIX\~ from DDR 2ndMIX \]\[ 3013 > SP-TRIP MACHINE\~JUNGLE MIX\~(SMM-Special) from PS2 - DDR X JP \]\[ 1939 > SP-TRIP MACHINE\~JUNGLE MIX\~(X-Special) from DDR X \]")

**moobot**: Top matches (request with !requestid \[song id\]): \[ 1090 > SP-TRIP MACHINE\~JUNGLE MIX\~ from DDR 2ndMIX \]\[ 3013 > SP-TRIP MACHINE\~JUNGLE MIX\~(SMM-Special) from PS2 - DDR X JP \]\[ 1939 > SP-TRIP MACHINE\~JUNGLE MIX\~(X-Special) from DDR X \]

---

**ddrdave**: !requestid 1090

(moobot makes a call to "https://www.davelinger.com/twitch/request.php?user=ddrdave&songid=1090")

(script responds with "ddrdave requested SP-TRIP MACHINE\~JUNGLE MIX\~ from DDR 2ndMIX")

**moobot**: ddrdave requested SP-TRIP MACHINE~JUNGLE MIX~ from DDR 2ndMIX

---

**ddrdave**: !request Some Song That Doesn't Exist

(moobot makes a call to "https://www.davelinger.com/twitch/request.php?user=ddrdave&song=Some+Song+That+Doesnt+Exist")

**moobot**: Didn't find any songs matching that name!

---

**ddrdave**: !requestid 1090

(moobot makes a call to "https://www.davelinger.com/twitch/request.php?user=ddrdave&songid=1090")

**moobot**: That song has already been requested recently!

---

## Song List Script
This is just a php web page that pulls the whole list of songs from the song scraper's DB and displays them with IDs, by pack for easy viewing. I have moobot setup to provide a link to this script when a user types "!songlist" or "!songs".

### Example:

**ddrdave**: !songlist

**moobot**: The song list can be found here: https://www.davelinger.com/twitch/songlist.php
