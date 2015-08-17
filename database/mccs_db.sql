SET NAMES 'utf8';

SET CHARACTER SET utf8;

CREATE DATABASE IF NOT EXISTS mccs_db CHARACTER SET=utf8;

GRANT ALL PRIVILEGES ON mccs_db.* TO mccs_user@localhost IDENTIFIED BY 'password_db_mccs';
GRANT SELECT ON mccs_db.* TO mccs_webuser@localhost IDENTIFIED BY 'password_db_mccs_web';
FLUSH PRIVILEGES;

use mccs_db;

CREATE TABLE IF NOT EXISTS Federations
(
	federationName VARCHAR(255) NULL,
	emailAddress VARCHAR(255) NULL,
	registrationAuthority VARCHAR(255) NOT NULL,
	updated BOOLEAN NOT NULL DEFAULT 0,
	UNIQUE (registrationAuthority),
	PRIMARY KEY (registrationAuthority)
) ENGINE=InnoDB  DEFAULT CHARSET="utf8";

INSERT IGNORE INTO `Federations` (`federationName`, `emailAddress`, `registrationAuthority`) VALUES
	('eduGAIN', 'eduGAIN-ot@edugain.org', '*');

CREATE TABLE IF NOT EXISTS EntityDescriptors
(
	entityID VARCHAR(255) NOT NULL,
	registrationAuthority VARCHAR(255) NOT NULL,
	displayName VARCHAR(255),
	ignoreEntity BOOLEAN NOT NULL default 0,
	lastCheck TIMESTAMP NULL default NULL,
	currentResult VARCHAR(16) NULL default NULL,
	previousResult VARCHAR(16) NULL default NULL,
	technicalContacts BLOB NULL,
	supportContacts BLOB NULL,
	updated BOOLEAN NOT NULL DEFAULT 0,
	ignoreReason VARCHAR(1000) NULL,
	serviceLocation VARCHAR(255) NOT NULL,
	UNIQUE (entityID),
	FOREIGN KEY (registrationAuthority) REFERENCES Federations(registrationAuthority) ON UPDATE CASCADE ON DELETE CASCADE,
	PRIMARY KEY (entityID)
) ENGINE=InnoDB  DEFAULT CHARSET="utf8";

CREATE TABLE IF NOT EXISTS EntityChecks
(
	id MEDIUMINT NOT NULL AUTO_INCREMENT,
	entityID VARCHAR(255) NOT NULL,
	spEntityID VARCHAR(255) NOT NULL,
	serviceLocation VARCHAR(255) NOT NULL,
	acsUrls VARCHAR(255) NOT NULL,
	checkTime TIMESTAMP NOT NULL default CURRENT_TIMESTAMP,
	checkHtml BLOB NULL,
	httpStatusCode INTEGER NOT NULL,
	checkResult VARCHAR(16) NOT NULL,
	checkExec MEDIUMINT NOT NULL default 0,
	FOREIGN KEY (entityID) REFERENCES EntityDescriptors(entityID) ON UPDATE CASCADE ON DELETE CASCADE,
	PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET="utf8";
