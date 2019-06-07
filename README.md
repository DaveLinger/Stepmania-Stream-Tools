# Stepmania-Stream-Tools
Tools and utilities for interacting with Stepmania 5 to provide added features for live streaming. mysql_schema.sql contains the mysql table structure used by these tools on the "remote web server".

There are several pieces to the puzzle here:

## 1. Song Scraper
This software looks through your Stepmania Songs folder and uploads info for each song to a remote mysql database.

## 2. Song Requests Scripts
This software allows incoming calls to request songs. I use this with moobot, a free web-based twitch bot. When somebody says "!request Some Song", moobot sends their twitch username and their requested song title to my requests script. The script searches the database created by the song scraper for songs matching the title. If there is more than one match, it returns the top 5 matches with their titles, packs, and ID numbers. A twitch user can use "!requestid 1234" to request a song by ID instead of by name. This is useful to pick a specific version of a song with a title shared by many tracks.

Additional rules could be added - for example, you could block users from requesting songs more often than you'd like, or prevent requests from users who have songs still in the request queue that have not yet been played. Currently, the only restriction is that users cannot request songs that have been requested within the past hour. You could add additional commands for certain users only. Restrict these at the moobot-level or the script level - for example to allow moderators to bypass rate limits, rules, or to cancel songs requested by others.

The !cancel command cancels the user's most recently requested song.

### Examples:

---

**ddrdave**: !request Algorithm

(moobot makes a call to "https://www.mywebsite.com/twitch/request.php?user=ddrdave&song=Algorithm")

(script responds with "ddrdave requested ALGORITHM from DDR A"

**moobot:** ddrdave requested ALGORITHM from DDR A

---

**ddrdave**: !request Trip Machine

(moobot makes a call to "https://www.mywebsite.com/twitch/request.php?user=ddrdave&song=Trip+Machine")

(script responds with "Top matches (request with !requestid \[song id\]): \[ 1090 > SP-TRIP MACHINE\~JUNGLE MIX\~ from DDR 2ndMIX \]\[ 3013 > SP-TRIP MACHINE\~JUNGLE MIX\~(SMM-Special) from PS2 - DDR X JP \]\[ 1939 > SP-TRIP MACHINE\~JUNGLE MIX\~(X-Special) from DDR X \]")

**moobot**: Top matches (request with !requestid \[song id\]): \[ 1090 > SP-TRIP MACHINE\~JUNGLE MIX\~ from DDR 2ndMIX \]\[ 3013 > SP-TRIP MACHINE\~JUNGLE MIX\~(SMM-Special) from PS2 - DDR X JP \]\[ 1939 > SP-TRIP MACHINE\~JUNGLE MIX\~(X-Special) from DDR X \]

---

**ddrdave**: !requestid 1090

(moobot makes a call to "https://www.mywebsite.com/twitch/request.php?user=ddrdave&songid=1090")

(script responds with "ddrdave requested SP-TRIP MACHINE\~JUNGLE MIX\~ from DDR 2ndMIX")

**moobot**: ddrdave requested SP-TRIP MACHINE~JUNGLE MIX~ from DDR 2ndMIX

---

**ddrdave**: !request Some Song That Doesn't Exist

(moobot makes a call to "https://www.mywebsite.com/twitch/request.php?user=ddrdave&song=Some+Song+That+Doesnt+Exist")

**moobot**: Didn't find any songs matching that name!

---

**ddrdave**: !requestid 1090

(moobot makes a call to "https://www.mywebsite.com/twitch/request.php?user=ddrdave&songid=1090")

**moobot**: That song has already been requested recently!

---

**ddrdave**: !cancel

(moobot makes a call to "https://www.mywebsite.com/twitch/request.php?user=ddrdave&cancel")

(script responds with "Canceled ddrdave's request for SP-TRIP MACHINE\~JUNGLE MIX\~")

**moobot:** Canceled ddrdave's request for SP-TRIP MACHINE\~JUNGLE MIX\~

## 3. Song List Script
This is just a php web page that pulls the whole list of songs from the song scraper's DB and displays them with IDs, by pack for easy viewing. I have moobot setup to provide a link to this script when a user types "!songlist" or "!songs".

### Example:

**ddrdave**: !songlist

**moobot**: The song list can be found here: https://www.mywebsite.com/twitch/songlist.php

## 4. Requests List Web Widget
This is the web view of the current requests list. This is shown in OBS as a web source. It checks for new requests, completions, and cancelations every 5 seconds. It adds new song requests to the top of the list, removes canceled requests from the list, and applies a specific CSS class to completed songs (dim them and applies a green checkmark). It plays a sound effect when a song is requested, and a different sound when a song is canceled. It uses Jquery and CSS to do fancy animations for each. This page hits a specific php script that returns the new song requests (etc). More details in the readme for the request list widget.

## 5. Stepmania Scene Switching and Song Output
On my stream, I have OBS automatically switch between a "song select/evalution" scene (which shows the whole screen capture), calories burned, face camera, etc) and a "gameplay" scene, which only shows the Player 1 side of the video capture, as well as the input overlay, overhead camera, and current heart rate reading. The way this is accomplished is by having Stepmania output text to a specific text file when it switches to or from one of those screens.

I also output the currently-being-played song title to a different text file. This allows me to "check off" songs that have been requested, as soon as the song starts. This requires the use of a python script I wrote on the computer running Stepmania to watch for changes to this file, and send them off to a php script on my remote web server to parse. Details in the relevant readme.

## 6. Pulsoid Food/Calories Web Widget
I use Pulsoid (free) to display my current heart rate BPM on stream from my Wahoo Tickr heart rate strap. Pulsoid also offers a "calories burned" counter. I copied and modified that page to instead display total calories burned in relation to common food items, similar to DDR A.

## 7. DDR Input Indicator
I use an OBS plugin called **[Input Overlay](https://obsproject.com/forum/resources/input-overlay.552/)** to achieve this - I had to make a custom config file and two custom graphics for this, which I'll include here in the repo. The other key factor here is you need to get the keyboard inputs from your stepmania machine onto your streaming machine, or this won't work. So I use a piece of free software called **[Input Director](https://www.inputdirector.com)** to mirror the keyboard inputs from the Stepmania PC to the streaming PC. There's virtually no latency. Install the software on both PCs, setup your steaming PC as a slave and use the software on your Stepmania PC to "Mirror keyboard input across slaves".

# Bugs

- Currently the song scraper sends up the transliterated title of a song and removes \[square bracket titles\] from the beginnings of the titles as well as (parenthetical subtitles) from the ends of the titles. However, when songs are played with (parenthetical subtitles), they aren't removed before a match is attempted, so often times a song won't get "checked off" when it's played if it ends with a subtitle in parentheses. For example let's say somebody requests ALGORITHM from DDR A. Now you play that song. The script sees that you are playing a song called ALGORITHM from a pack called DDR A, so it matches and checks it off. But now let's say somebody requested CARTOON HEROES (20th Anniversary Mix). It shows up in the requests list as CARTOON HEROES from DDR A, but when you play the song, Stepmania reports that you are playing "CARTOON HEROES (20th Anniversary Mix)" from the pack DDR A, and there's no match for that. This doesn't always happen, so it's hard to figure out.

# Non-Bug Issues

- Moobot has some kind of automatic cooldown for chat, so if it gets invoked many times back to back, it will whisper responses to the users invoking it, rather than posting them in chat. Everything still works.

# Future Development

- Currently the blurred out background images for the request cards must be manually uploaded if you want to have them. Presumably the scripts should just be able to upload these from the packs, which would be super handy.
