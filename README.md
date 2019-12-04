# Some in-process WordPress and Arduino files for Idea Fab Labs Santa Cruz's Mint party project

[rest-api.php](https://github.com/ideafablabs/partymechanics/blob/master/rest-api.php) contains the code for these API
endpoints:
* **quote_pair** - call it with two token IDs *NFC1* and *NFC2* and it returns the movie quote assigned to that
pairing (if that pairing has not been seen previously, then a new quote gets assigned to it and is then returned)
* **get_user_id_from_token_id** - call it with a token ID *TOKEN_ID* to retrieve the user ID that token was registered to
* **get_token_ids_from_user_id** - call it with a user ID *USER_ID* to retrieve all token IDs registered to that user
* **add_token_id_and_user_id_to_tokens_table** - call it with a token ID *TOKEN_ID* and a user ID *USER_ID* to register
that token to that user in the tokens table

[ifl-party-mechanics.php](https://github.com/ideafablabs/partymechanics/blob/master/ifl-party-mechanics.php) is a
WordPress plugin containing the functions used by rest-api.php, and some in-process events/attendance/special guests 
code. Most of the movie quotes functionality has been moved to movie-quotes.php below, but the functions used by the 
four endpoints above are now just wrappers for calling those functions in movie_quotes.php

[movie-quotes.php](https://github.com/ideafablabs/partymechanics/blob/master/movie-quotes.php) - the MovieQuotes class
contains functions for working with the movie quotes, user pairings, and rf tokens tables in general.

[quotes.csv](https://github.com/ideafablabs/partymechanics/blob/master/quotes.csv) is the database of short science
fiction movie quotes in CSV form.

The [Party Mechanics Notes](https://docs.google.com/document/d/1-3XrTe-Q02qRC4WK6LZkSj_1pk22UXLcHj5TGS_8biM/edit)
Google Doc is the place to collaborate on more extensive documentation.

Right now the Arduino folder contains the ESP example sketch WiFiMulti, illustrating configuring your WeMos D1 R1
board to connect to whichever one of one or more predefined wifi networks is available, and many in-process
sketches from just before last March's Doublemint party.

Right now loading the "Exhibition RSVPs" custom menu from the wp-admin dashboard creates some of
the tables for this project if they didn't already exist. With the wp-attendance and
wp-special-guests tables, because they use the wp-users table's ID field as a foreign key,
the wp-users table needs to be using the InnoDB storage engine rather than the MyISAM storage
engine, because the latter doesn't support foreign keys. See
https://kinsta.com/knowledgebase/convert-myisam-to-innodb/

[/zone-plus-one](https://github.com/ideafablabs/partymechanics/tree/master/zone-plus-one) contains files for the Plus
One Zones WordPress plugin-in-progress: 
* Functions for creating and working with tables for zones, zone tokens, and plus-ones registered by touching a zone token to a particular zone's RFID-reader microcontroller installation. 
* A custom Plus One Zones admin menu page which just contains testing stuff at the moment, and a custom Manage Zone Names submenu page that lets you add new zones and edit the names of existing zones.
