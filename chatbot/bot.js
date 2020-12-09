const tmi = require('tmi.js');
const { exec } = require("child_process");
const { isNull, isNullOrUndefined } = require('util');
require('dotenv').config()

if(isNullOrUndefined(process.env.BOTUSERNAME) || isNullOrUndefined(process.env.BOTPASSWORD) || isNullOrUndefined(process.env.BOTCHANNEL) || isNullOrUndefined(process.env.URLBASE) || isNullOrUndefined(process.env.SECRET_KEY)){
	console.log("Invalid environment variables!");
	process.exit();
}

// Define configuration options
const opts = {
  identity: {
    username: process.env.BOTUSERNAME,
    password: process.env.BOTPASSWORD
  },
  channels: [
    process.env.BOTCHANNEL
  ]
};

//Define other globals vars
urlbase = process.env.URLBASE;
secretKey = process.env.SECRET_KEY;

// Create a client with our options
const client = new tmi.client(opts);

// Register our event handlers (defined below)
client.on('message', onMessageHandler);
client.on('connected', onConnectedHandler);

// Connect to Twitch:
client.connect();

// Called every time a message comes in
function onMessageHandler (target, context, msg, self) {
  if (self) { return; } // Ignore messages from the bot
  
  displayName = context["display-name"];
  emotes = context["emotes"];
  moderator = context["mod"];
  //if (context.hasOwnProperty("badges")) {
  //  if(context["badges"].hasOwnProperty("broadcaster")){
  //    if(context["badges"]["broadcaster"] == "1"){
  //      moderator = true;
  //    }
  //  }
  //}
  subscriber = context["subscriber"];
  userId = context["user-id"];
  
  //console.log("emotes: "+emotes);
  //console.log(emotes);
  howManyEmotes = 0;
  for (var emoteId in emotes) {
    howManyEmotes++;
    if(emoteId == "922359"){
      client.say(target, "woof!");
    }
  }
  if(howManyEmotes < 1 && (msg.indexOf("bandit") !== -1 || msg.indexOf("Bandit") !== -1)){
      client.say(target, "woof!");
  }
  //bandit face is emote 922359
  //aa emote is 922366
  //do max 300 emote is 922375

  // Remove whitespace from chat message
  const commandName = msg.trim();
  
  theWholeCommand = commandName.toLowerCase();
  spacePos = theWholeCommand.indexOf(" ");
  if(theWholeCommand.includes(" ")){ //If this command has a space...
	theCommand = theWholeCommand.substring(0,spacePos+1); //returns the first piece of text from the message, plus the space after it. Like "!request ".
	theArgs = theWholeCommand.substring(spacePos+1); //returns the rest of the text after the space.
  }else{
	theCommand = theWholeCommand;
    theArgs = "";
  }
  
  if(theCommand.startsWith("!requestid ") || theCommand.startsWith("!rid ") || theCommand.startsWith("!srid ")){
	  
	if(theArgs == "69" || theArgs == "420" || theArgs == "1234" || theArgs == "12345" || theArgs == "123"){
		client.say(target, "Hurr hurr, good one!");
	}else{

	encodedURI = encodeURI('/request.php?security_key='+secretKey+'&user='+displayName+'&userid='+userId+'&songid='+theArgs);
	requestURI = urlbase+encodedURI;
	var request = require("request");
	request(
		{ uri: urlbase+encodedURI },
		function(error, response, body) {
			if(!isNullOrUndefined(error)){console.log(error);}
			client.say(target, body);
		}
	);
	
	}

  }
   
  if(theCommand.startsWith("!request ") || theCommand.startsWith("!sr ") || theCommand.startsWith("!songrequest ")){
	
	encodedURI = encodeURI('/request.php?security_key='+secretKey+'&user='+displayName+'&userid='+userId+'&song='+theArgs);
	requestURI = urlbase+encodedURI;
	var request = require("request");
	request(
		{ uri: urlbase+encodedURI },
		function(error, response, body) {
			console.log(error);
			console.log(body);
			client.say(target, body);
		}
	);
	
  }
  
  if(theCommand.startsWith("!bansong ")){
	if(moderator){
		encodedURI = encodeURI('/song_admin.php?security_key='+secretKey+'&bansong='+theArgs);
		requestURI = urlbase+encodedURI;
		var request = require("request");
		request(
			{ uri: urlbase+encodedURI },
			function(error, response, body) {
				console.log(error);
				console.log(body);
				client.say(target, body);
			}
		);
	}else{
	  //Not a mod
	}
  }	
	
  if(theCommand.startsWith("!banuser ")){
	if(moderator){
		encodedURI = encodeURI('/requestor.php?security_key='+secretKey+'&banuser='+theArgs);
		requestURI = urlbase+encodedURI;
		var request = require("request");
		request(
			{ uri: urlbase+encodedURI },
			function(error, response, body) {
				console.log(error);
				console.log(body);
				client.say(target, body);
			}
		);
	}else{
	  //Not a mod
	}
  }
  
  if(theCommand == "!cancel"){
	encodedURI = encodeURI('/request.php?security_key='+secretKey+'&user='+displayName+'&userid='+userId+'&cancel='+theArgs);
	requestURI = urlbase+encodedURI;
	var request = require("request");
	request(
		{ uri: urlbase+encodedURI },
		function(error, response, body) {
			console.log(error);
			console.log(body);
			client.say(target, body);
		}
	);
  }
  
  if(theCommand == "!random"){
	encodedURI = encodeURI('/rand_request.php?security_key='+secretKey+'&user='+displayName+'&userid='+userId+'&random=random&num='+theArgs);
	requestURI = urlbase+encodedURI;
	var request = require("request");
	request(
		{ uri: urlbase+encodedURI },
		function(error, response, body) {
			console.log(error);
			console.log(body);
			client.say(target, body);
		}
	);
  }
  
  if(theCommand == "!randomben"){
	encodedURI = encodeURI('/rand_request.php?security_key='+secretKey+'&user='+displayName+'&userid='+userId+'&random=Ben+Speirs&num='+theArgs);
	requestURI = urlbase+encodedURI;
	var request = require("request");
	request(
		{ uri: urlbase+encodedURI },
		function(error, response, body) {
			console.log(error);
			console.log(body);
			client.say(target, body);
		}
	);
  }
  
  if(theCommand == "!skip"){
	if(moderator){
		encodedURI = encodeURI('/request.php?security_key='+secretKey+'&user='+displayName+'&userid='+userId+'&skip='+theArgs);
		requestURI = urlbase+encodedURI;
		var request = require("request");
		request(
			{ uri: urlbase+encodedURI },
			function(error, response, body) {
				console.log(error);
				console.log(body);
				client.say(target, body);
			}
		);
	}else{
	  //Not a mod	
	}
  }
  
  if((theCommand == "!songlist" || theCommand == "!songs")){
	client.say(target, `The song list can be found here: https://www.davelinger.com/twitch/songlist.php`);
  }
  
  if(theCommand == "!top"){
	encodedURI = encodeURI('/rand_request.php?security_key='+secretKey+'&user='+displayName+'&userid='+userId+'&random=top&num='+theArgs);
	requestURI = urlbase+encodedURI;
	var request = require("request");
	request(
		{ uri: urlbase+encodedURI },
		function(error, response, body) {
			console.log(error);
			console.log(body);
			client.say(target, body);
		}
	);
  }
  
  if(theCommand.startsWith("!whitelist ")){
	if(moderator){
		encodedURI = encodeURI('/requestor.php?security_key='+secretKey+'&whitelist='+theArgs);
		requestURI = urlbase+encodedURI;
		var request = require("request");
		request(
			{ uri: urlbase+encodedURI },
			function(error, response, body) {
				console.log(error);
				console.log(body);
				client.say(target, body);
			}
		);
	}else{
	  //Not a mod
	}
  }

}
// Called every time the bot connects to Twitch chat
function onConnectedHandler (addr, port) {
  console.log(`* Connected to ${addr}:${port}`);
}

