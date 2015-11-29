## Mediawiki-utilities

A collection of dirty (and not so) scripts to manipulate a mediawiki deployment.
Is a job in progress, growing along a ultra personalized mediawiki project, so I will be adding more scripts soon.

## Installation

Each script must be downloaded and put in specific folder, mostly maintenance.

## Scripts
### importUsersCli
* Copy to maintenance folder. 
* Run from terminal `php importUsersCli.php < file.csv` or `php importUsersCli.php --force < file.csv` to update existing users.

This script create new accounts from a CVS data passed by STDIN, using the same format required by ImportUsers extension: *username,password,email,realname,groups*. Each line is a new user and only the username is required to create a user.

### extendedCreateAndPromote
* Copy to maintenance folder.
* Run from terminal `php extendedCreateAndPromote.php`
The same logic under createAndPromote, but with two more parameters: realname and email.

## License
GNU General Public License 2.0
