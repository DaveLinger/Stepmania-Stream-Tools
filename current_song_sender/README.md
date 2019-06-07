### How to use this

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

Then comes the modification to "ScreenGameplay overlay/default.lua":

```
---------------------------------
local f = RageFileUtil.CreateRageFile()

if f:Open("Save/SongInfo.txt", 2) then	

	f:Write("GAMEPLAY\n")

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
	local stepData = GAMESTATE:GetCurrentSteps(0)
	
	-- name
	local name = "\"song\":\""..song:GetTranslitFullTitle().."\","
	-- artist
	local artist = "\"artist\":\""..song:GetTranslitArtist().."\","
	-- pack
	local pack = "\"pack\":\""..song:GetGroupName().."\","
	-- diff
	local diff =  "\"diff\":\""..stepData:GetMeter().."\","
	-- steps
	local steps = "\"steps\":\""..stepData:GetRadarValues(0):GetValue(5).."\","
	-- time
	local time = song:GetStepsSeconds()
	time = string.format("\"time\":\"%d:%02d\"", math.floor(time/60), math.floor(time%60))

	-- complete! 
	f:Write("{"..name..artist..pack..diff..steps..time.."}")

else
	local fError = f:GetError()
	Trace( "[FileUtils] Error writing to file: ".. fError )
	f:ClearError()
end
f:destroy()
---------------------------------
```

So you can see here, we are writing "GAMEPLAY" to the SongInfo.txt file that OBS is going to read for scene switching, and we are writing a json object to Out/SongInfoUpload.txt containing the current song, artist, pack, difficulty, number of steps, and song duration. The "send current song" python script will see this change and sned this data to the remote server script.
