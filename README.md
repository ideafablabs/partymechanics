# Some in-process WordPress and Arduino files for Idea Fab Labs Santa Cruz's Mint party project

[ifl-party-mechanics.php](https://github.com/ideafablabs/partymechanics/blob/master/ifl-party-mechanics.php) is a
WordPress plugin containing the functions used by rest-api.php, and also functions for working with the movie
quotes, user pairings, and rf tokens tables in general.

[quotes.csv](https://github.com/ideafablabs/partymechanics/blob/master/quotes.csv) is the database of short science
fiction movie quotes in CSV form.

[rest-api.php](https://github.com/ideafablabs/partymechanics/blob/master/rest-api.php) contains the code for these API
endpoints:
* **quote_pair** - call it with two token IDs *NFC1* and *NFC2* and it returns the movie quote assigned to that
pairing (if that pairing has not been seen previously, then a new quote gets assigned to it and is then returned)
* **get_user_id_from_token_id** - call it with a token ID *TOKEN_ID* to retrieve the user ID that token was registered to
* **get_token_ids_from_user_id** - call it with a user ID *USER_ID* to retrieve all token IDs registered to that user
* **add_token_id_and_user_id_to_tokens_table** - call it with a token ID *TOKEN_ID* and a user ID *USER_ID* to register
that token to that user in the tokens table

The [Party Mechanics Notes](https://docs.google.com/document/d/1-3XrTe-Q02qRC4WK6LZkSj_1pk22UXLcHj5TGS_8biM/edit)
Google Doc is the place to collaborate on more extensive documentation.

Right now the Arduino folder contains the ESP example sketch WiFiMulti, illustrating configuring your WeMos D1 R1
board to connect to whichever one of one or more predefined wifi networks is available, and many in-process
sketches from just before last March's Doublemint party.