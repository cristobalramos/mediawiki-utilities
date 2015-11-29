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


class ImportUsersCli extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = "Create new accounts from a CVS data, using the same format required by ImportUsers extension";
		$this->addOption(
			'force',
			'If acccount exists already, just grant it rights, change info or password.'
		);
	}

	public function execute() {
		$text = $this->getStdin( Maintenance::STDIN_ALL );

		$force = $this->hasOption( 'force' );

		$summary = array(
			'all' => 0,
			'skipped' => 0,
			'added' => 0,
			'updated' => 0,
			'errors' => 0
		);

		$data = explode( "\n", rtrim( $text ) );

		foreach ( $data as $line => $userdata ) {
			++$summary['all'];
			$userdata = explode( ',', trim( $userdata ) );
			if ( !count( $userdata ) ) {
				$realline = $line + 1;
				$this->output( "User data in the line $realline has invalid format or is blank and was skipped.\n" );
				++$summary['skipped'];

				continue;
			}


			list( $username, $password, $email, $realname, $promotions ) = array_pad( $userdata, 5, null); 

			$user = User::newFromName( $username );
			if ( !is_object( $user ) ) {
				$this->error( "invalid username." );
				++$summary['errors'];

				continue;
			}
			$exists = ( 0 !== $user->idForName() );
			if ( $exists && !$force ) {
				$this->output( "$username exists. Skipped.\n" );
				++$summary['skipped'];

				continue;
			} elseif ( !$exists && !$password ) {
				$this->output( "$username created without password.\n" );
			} elseif ( $exists ) {
				$inGroups = $user->getGroups();
			}
			if ( !is_null($promotions) ){
				$promotions = explode( ' ', trim( $promotions ) );
			}
			if ( $exists && !$password && count( $promotions ) === 0 ) {
				$this->output( "$username exists and nothing to do.\n" );
				++$summary['skipped'];

				continue;
			} elseif ( count( $promotions ) !== 0 ) {
				$promoText = "User:{$username} into " . implode( ', ', $promotions ) . "...\n";
				if ( $exists ) {
					$this->output( wfWikiID() . ": Promoting $promoText \n" );
				} else {
					$this->output( wfWikiID() . ": Creating and promoting $promoText \n" );
				}
				array_map( array( $user, 'addGroup' ), $promotions );
			}

			if ( !is_null( $password ) ) {
				try {
					$user->setPassword( $password );
				} catch ( PasswordError $pwe ) {
					$this->error( $pwe->getText(), true );
				}
			}

			if ( !is_null( $realname ) ) {
				$user->setRealName( $realname );
			}
			if ( !is_null( $email ) ) {
				$user->setEmail( $email );
			}

			if ( !$exists ) {
				$user->addToDatabase();
				$user->saveSettings();
				++$summary['added'];
			} else {
				$this->output( "User $username updated.\n" );
				$user->saveSettings();
				++$summary['updated'];
			}
		}
		if ( $summary['added'] > 0 ) {
			$ssu = new SiteStatsUpdate( 0, 0, 0, 0, $summary['added'] );
			$ssu->doUpdate();
		}
		$this->output( "Import finished " );
		if ($summary['errors']) {
			$this->output( "with {$summary['errors']} errors" );
		}
		$this->output( "\nAdded: {$summary['added']}\nUpdated: {$summary['updated']}\nSkipped: {$summary['skipped']}\nTotal: {$summary['all']} \n" );
	}
}
$maintClass = "ImportUsersCli";
require_once RUN_MAINTENANCE_IF_MAIN;

