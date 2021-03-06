<?php
/**
 * Creates an account and grants it rights.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Maintenance
 * @author Rob Church <robchur@gmail.com>
 * @author Pablo Castellano <pablo@anche.no>
 * @author Cristóbal Ramos <cristobalramosmerino@gmail.com>
 */

require_once __DIR__ . '/Maintenance.php';

/**
 * Maintenance script to create an account and grant it rights.
 *
 * @ingroup Maintenance
 */


class ExtendedCreateAndPromote extends Maintenance {
	private static $permitRoles = array( 'sysop', 'bureaucrat', 'bot' );

	public function __construct() {
		parent::__construct();
		$this->mDescription = "Create a new user account and/or grant it additional rights";
		$this->addOption(
			'force',
			'If acccount exists already, just grant it rights, change info or password.'
		);
		foreach ( self::$permitRoles as $role ) {
			$this->addOption( $role, "Add the account to the {$role} group" );
		}

		$this->addOption(
			'custom-groups',
			'Comma-separated list of groups to add the user to',
			false,
			true
		);

		$this->addArg( "username", "Username of new user" );
		$this->addArg( "password", "Password to set (not required if --force is used)", false );
		$this->addArg( "realname", "Real name of new user (not required if --force is used)", false );
		$this->addArg( "email", "Email of new user (not required if --force is used)", false );
	}

	public function execute() {
		$username = $this->getArg( 0 );
		$password = $this->getArg( 1 );
		$realname = $this->getArg( 2 );
		$email = $this->getArg( 3 );
		$force = $this->hasOption( 'force' );
		$inGroups = array();

		$user = User::newFromName( $username );
		if ( !is_object( $user ) ) {
			$this->error( "invalid username.", true );
		}

		$exists = ( 0 !== $user->idForName() );

		if ( $exists && !$force ) {
			$this->error( "Account exists. Perhaps you want the --force option?", true );
		} elseif ( !$exists && !$password ) {
			$this->error( "Argument <password> required!", false );
			$this->maybeHelp( true );
		} elseif ( $exists ) {
			$inGroups = $user->getGroups();
		}

		$promotions = array_diff(
			array_filter( self::$permitRoles, array( $this, 'hasOption' ) ),
			$inGroups
		);

		if ( $exists && !$password && count( $promotions ) === 0 ) {
			$this->output( "Account exists and nothing to do.\n" );

			return;
		} elseif ( count( $promotions ) !== 0 ) {
			$promoText = "User:{$username} into " . implode( ', ', $promotions ) . "...\n";
			if ( $exists ) {
				$this->output( wfWikiID() . ": Promoting $promoText" );
			} else {
				$this->output( wfWikiID() . ": Creating and promoting $promoText" );
			}
		}

		if ( $password ) {
			# Try to set the user info and password
			try {
				$user->setPassword( $password );
				$user->setRealName( $realname );
				$user->setEmail( $email );
				if ( $exists ) {
					$this->output( "User changed.\n" );
					$user->saveSettings();
				}
			} catch ( PasswordError $pwe ) {
				$this->error( $pwe->getText(), true );
			}
		}

		if ( !$exists ) {
			# Insert the account into the database
			$user->addToDatabase();
			$user->saveSettings();
		}

		# Promote user
		array_map( array( $user, 'addGroup' ), $promotions );

		if ( !$exists ) {
			# Increment site_stats.ss_users
			$ssu = new SiteStatsUpdate( 0, 0, 0, 0, 1 );
			$ssu->doUpdate();
		}

		$this->output( "done.\n" );
	}
}

$maintClass = "ExtendedCreateAndPromote";
require_once RUN_MAINTENANCE_IF_MAIN;
