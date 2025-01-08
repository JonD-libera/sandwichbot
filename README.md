# sandwichbot
A helpful sandwich virtual assistant

To setup the bot, you'll need a few things. There are multiple ways to get there, but here's how I did it.

Setup an asterisk server. You can use bare asterisk or freepbx.

Route an incoming number to the included dialplan context found in dialplan.conf
If using freepbx, you'll want to put this dialplan in extensions_custom.conf, and configure a route to it in the freepbx UI.

Setup mimic3 in docker. https://github.com/MycroftAI/mimic3

Modify sandwichbot.php with your openai auth and path to mimic3. Alternatively, you can modify sandwichbot.php to use a local transcription engine and a local or alternate AI engine.
