<?php
class mdb_Defaults
{
	static $_defaults = array (
		'system.title'						=> 'Mouse Breeding Database',
		'system.versions.bzr.binary'		=> 'bzr',
		'system.dojo.theme'					=> 'soria',
		'system.display.footer.launchpad'	=> true,
	);

	static $_userPrefs = array (
		'search.go.input.suggest'			=> '1',
		'interface.toaster.position'		=> 'tr',
		'interface.toaster.direction'		=> 'left',
		'interface.toaster.duration'		=> '300',
		'interface.table.expand'			=> true,
	);

	public static function getDefaults() {
		return self::$_defaults;
	}

	public static function getUserPrefs() {
		return self::$_userPrefs;
	}
}
