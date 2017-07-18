# README #

Getting the bot up and running is very simple, 

* Download or clone the repository

* copy the default configuration so you can create your own:
`cp .env.default .env`

* edit .env and customize your settings. 

* run the bot from the command line:
`php bot.php`

* (optional) run the bot from the command line with alternate config file:
`php bot.php .env.alternate`


### What is this repository for? ###

* DMBot, the PHP IRC Bot - revived.
* 5.0.0

### Creating Modules ###

The DMBot by itself is rather dumb, give it some personality by creating your own modules.

Included are two modules:
   * Greeting - replies to *some* direct greetings
   * Logbot - a logging bot, saves conversations to the DB for later use. 

To create your own module, create a directory in the modules/ folder:

`mkdir modules/myModule`

and create a file (named like your module) in the folder:

`modules/myModule/myModule.php`

Your modules must extend from DMBot\Module and live in the DMBot\Modules namespace: 

`namespace DMBot\Modules;
use DMBot\Module;
use DMBot\Modules;

class MyModule extends Module { }`

(see DMBot/Module.php for the properties you need to implement)

To receive IRC Events, simply write a method for the event you want to receive, available events are:

* PRIVMSG
* JOIN
* PART
* QUIT
* NOTICE
* MOTD

Your method will receive 1 parameter of type DMBot\IRC\Message. 

In this same php file, after your class is declared, register your module by calling: 

Modules::addModule('myModule', 'DMBot\Modules\MyModule');

(See included modules for more details)

### Contribution guidelines ###

* Feel free to fork and create your own modules. 
* Submit pull requests if you'd like them considered for inclusion with the core. 


### Who do I talk to? ###

* @wammy21