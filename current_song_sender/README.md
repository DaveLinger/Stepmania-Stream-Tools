# How to use this

Firstly, credit to Dan Guzec [dguzek/Simply-Love-SM5](https://github.com/dguzek/Simply-Love-SM5) for showing me this method.

In order for this "current song sender" to work, your copy of Stepmania must be configured to output the current song name to a text file.

The way I accomplish this is by using some Lua code in my theme's overlay files. In your Stepmania data directory, go into Themes, then into your preferred theme's directory, then into BGAnimations.

In there, you'll find "overlay" files for all of the "screens". Profile select, music select, evaluation, gameplay, etc. In this case, open **ScreenSelectMusic overlay**, and edit default.lua.

Scroll to the bottom, and you'll see that it ends with `return t`. Right BEFORE that, we are going to add in the following code:

```
--------------------------------------------------------------------------------------
-- Clear text files
local f = RageFileUtil.CreateRageFile()
if f:Open("Save/SongInfo.txt", 2) then
	--local mods = GAMESTATE:GetPlayerState(0):GetPlayerOptionsString(2)
	f:Write("MUSICSELECT\n")
else
	-- do nothing
end
f:destroy()

-- Clear text files
local f = RageFileUtil.CreateRageFile()
if f:Open("Save/Out/SongInfoUpload.txt", 2) then
	--local mods = GAMESTATE:GetPlayerState(0):GetPlayerOptionsString(2)
	f:Write("{\"action\":\"clear\"}")
else
	-- do nothing
end
f:destroy()
--------------------------------------------------------------------------------------
```

What this does is writes to two text files in the "Save" directory in your Stepmania data folder. "SongInfo.txt" is what we are going to use to control what scene OBS is showing, so we're just going to make the file say "MUSICSELECT" (then a line break, because for whatever reason this won't work without a line break after it)

Then for our "send current song" script, it writes "SongInfoUpload.txt" to a new subfolder we make called Out. We are writing the artist/song/pack data in json format, but because we are on the music select screen, we're actually just writing that there's no song playing by writing {"action:clear"}.

I put this exact same code into the end of "ScreenEvaluationNew decorations/default.lua", except I replaced "MUSICSELECT" with "EVALUATION". This way if I wanted to have a special "score" scene in OBS, I could.

MrTwinkles: For my implementation, I needed to also find which player is playing (P1 or P2). This modification also finds the MasterPlayerNumber, which should work whether you play on the left or right side of the stage.

Then comes the modification to "ScreenGameplay overlay/default.lua":

```
---------------------------------
local f = RageFileUtil.CreateRageFile()

if f:Open("Save/SongInfo.txt", 2) then	

	local pn = ToEnumShortString(GAMESTATE:GetMasterPlayerNumber())
	f:Write("GAMEPLAY\n"..pn.."\n")

else
	local fError = f:GetError()
	Trace( "[FileUtils] Error writing to file: ".. fError )
	f:ClearError()
end
f:destroy()
---------------------------------

---------------------------------
local f = RageFileUtil.CreateRageFile()

if f:Open("Save/Out/SongInfoUpload.txt", 2) then	
	-- get gamestate objects
	local song = GAMESTATE:GetCurrentSong()
	local pn = ToEnumShortString(GAMESTATE:GetMasterPlayerNumber())
	local stepData = GAMESTATE:GetCurrentSteps(pn)
	
	--dir
	local dir = "\"dir\":\""..song:GetSongDir().."\","
	-- name
	local name = "\"song\":\""..song:GetTranslitFullTitle().."\","
	-- artist
	local artist = "\"artist\":\""..song:GetTranslitArtist().."\","
	-- pack
	local pack = "\"pack\":\""..song:GetGroupName().."\","
	-- diff
	local diff =  "\"diff\":\""..stepData:GetMeter().."\","
	-- steps
	local steps = "\"steps\":\""..stepData:GetRadarValues(pn):GetValue(5).."\","
	-- time
	local time = song:GetStepsSeconds()
	time = string.format("\"time\":\"%d:%02d\"", math.floor(time/60), math.floor(time%60))
	-- player
	local pn = "\"player\":\""..pn.."\","

	-- complete! 
	f:Write("{"..dir..name..artist..pack..pn..diff..steps..time.."}")

else
	local fError = f:GetError()
	Trace( "[FileUtils] Error writing to file: ".. fError )
	f:ClearError()
end
f:destroy()
---------------------------------
```

So you can see here, we are writing "GAMEPLAY" to the SongInfo.txt file that OBS is going to read for scene switching, and we are writing a json object to Out/SongInfoUpload.txt containing the current songdir, song, artist, pack, difficulty, number of steps, and song duration. The "send current song" python script will see this change and send this data to the remote server script.

# Automatic Scene Switching

I use the OBS plugin [**Advanced Scene Switcher**](https://obsproject.com/forum/resources/advanced-scene-switcher.395/) to read my SongInfo.txt file to automatically switch scenes based on what Stepmania is doing. Install the plugin, go to "Tools -> Advanced Scene Switcher" in OBS, and under the "Write to File / Read from File" tab, use browse button by "Switch scene based on file contents" to pick the SongInfo.txt file from your Stepmania install (I mount my Stepmania PC's Stepmania Data folder as a shared drive for this), then in "contains", add the text you want to trigger a scene switch. For example - for me, I have GAMEPLAY trigger a switch to my main gameplay OBS scene, and EVALUATION and MUSICSELECT both switch to their respective scenes in OBS.

I recommend checking "Only check contents if modification date has changed", which will allow you to manually switch between scenes. With this unchecked, it'll instantly switch back to whatever scene the text file specifies if you manually switch scenes (which you probably don't want).

# Song Request List Automatic "Check off" / Song Play History

Use the send_current_song.py script in here to monitor Out/ for changes, and upload Out/SongInfoUpload.txt to status.php upon change. This way the server knows instantly when you start playing a song, so it can be checked off of your requests list. This also maintains a historical list of songs played in the database.
