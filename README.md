## Mediawiki-utilities

A collection of dirty (and not so) scripts to manipulate a mediawiki deployment.
Is a job in progress, growing along a ultra personalized mediawiki project, so I will be adding more scripts soon.

## Installation

Each script must be downloaded and put in specific folder, mostly maintenance.

## Scripts
### importUsersCli
* Copy to maintenance folder. 
* Run from terminal `php importUsersCli.php < "username,password,email,realname,groups"`
* Run `php importUsersCli.php --help` for specific parameters.

This script create new accounts from a CVS data passed by STDIN, using the same format required by ImportUsers extension.

### extendedCreateAndPromote
* Copy to maintenance folder.
* Run from terminal `php extendedCreateAndPromote.php`
The same logic under createAndPromote, but with two more parameters: realname and email.
## License

GNU General Public License 2.0
