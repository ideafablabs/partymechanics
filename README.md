# Some in-process WordPress plugin/API files for Idea Fab Labs Santa Cruz's Mint party project

[ifl-party-mechanics.php](https://github.com/ideafablabs/partymechanics/blob/master/ifl-party-mechanics.php) is a
WordPress plugin containing the functions used by rest-api.php, and also functions for working with the movie
quotes, user pairings, and rf tokens tables in general.

[quotes.csv](https://github.com/ideafablabs/partymechanics/blob/master/quotes.csv) is the database of short science
fiction movie quotes in CSV form.

[rest-api.php](https://github.com/ideafablabs/partymechanics/blob/master/rest-api.php) contains the code for the
quote_pair API endpoint, where you call it with two user IDs and it returns the movie quote assigned to that
pairing (if that pairing has not been seen previously, then a new quote gets assigned to it and is then returned).
Later it will contain code for rf token/user IDs assigning and retrieval endpoints as well.
