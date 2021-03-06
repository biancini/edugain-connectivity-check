SET NAMES 'utf8';

SET CHARACTER SET utf8;

CREATE DATABASE IF NOT EXISTS eccs_db CHARACTER SET=utf8;

GRANT ALL PRIVILEGES ON eccs_db.* TO eccs_user@localhost IDENTIFIED BY 'password_db_eccs';
GRANT SELECT ON eccs_db.* TO eccs_webuser@localhost IDENTIFIED BY 'password_db_eccs_web';
FLUSH PRIVILEGES;

use eccs_db;

CREATE TABLE IF NOT EXISTS Federations
(
	federationName VARCHAR(255) NULL,
	emailAddress VARCHAR(255) NULL,
	registrationAuthority VARCHAR(255) NOT NULL,
	updated BOOLEAN NOT NULL DEFAULT 0,
   sgDelegateName VARCHAR(255) NULL,
   sgDelegateSurname VARCHAR(255) NULL,
   sgDelegateEmail VARCHAR(255) NULL,
   sgDeputyName VARCHAR(255) NULL,
   sgDeputySurname VARCHAR(255) NULL,
   sgDeputyEmail VARCHAR(255) NULL,
	UNIQUE (registrationAuthority),
	PRIMARY KEY (registrationAuthority)
) ENGINE=InnoDB  DEFAULT CHARSET="utf8";

INSERT IGNORE INTO Federations (federationName, emailAddress, registrationAuthority, updated, sgDelegateName, sgDelegateSurname, sgDelegateEmail, sgDeputyName, sgDeputySurname, sgDeputyEmail) VALUES
   ('eduGAIN', 'eduGAIN-ot@edugain.org', '*','1',NULL,NULL,NULL,NULL,NULL,NULL);

CREATE TABLE IF NOT EXISTS EntityDescriptors
(
	entityID VARCHAR(255) NOT NULL,
	registrationAuthority VARCHAR(255) NOT NULL,
	displayName VARCHAR(255),
	ignoreEntity BOOLEAN NOT NULL default 0,
	lastCheck TIMESTAMP NULL default NULL,
	currentResult VARCHAR(23) NULL default NULL,
	previousResult VARCHAR(23) NULL default NULL,
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
	checkResult VARCHAR(23) NOT NULL,
	checkExec MEDIUMINT NOT NULL default 0,
	FOREIGN KEY (entityID) REFERENCES EntityDescriptors(entityID) ON UPDATE CASCADE ON DELETE CASCADE,
	PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET="utf8";

CREATE TABLE IF NOT EXISTS FederationStats
(
  checkDate DATE NOT NULL,
  registrationAuthority VARCHAR(255) NOT NULL,
  currentResult VARCHAR(23) NULL default NULL,
  numIdPs INTEGER
) ENGINE=InnoDB  DEFAULT CHARSET="utf8";

CREATE OR REPLACE VIEW FederationStatsView AS
   SELECT `EntityDescriptors`.`registrationAuthority` AS `registrationAuthority`,
   `EntityDescriptors`.`currentResult` AS `currentResult`,
   count(0) AS `numIdPs`
   FROM `EntityDescriptors`
   GROUP BY `EntityDescriptors`.`registrationAuthority`, `EntityDescriptors`.`currentResult`;
