SET NAMES 'utf8';

SET CHARACTER SET utf8;

CHARSET utf8;

CREATE DATABASE IF NOT EXISTS mccs_db CHARACTER SET=utf8;

grant all privileges on mccs_db.* to mccs_user@localhost identified by 'ciaomccs';

use mccs_db;

CREATE TABLE IF NOT EXISTS Federations
(
federationName VARCHAR(255) NULL,
emailAddress VARCHAR(255) NULL,
registrationAuthority VARCHAR(255) NOT NULL PRIMARY KEY,
UNIQUE (registrationAuthority)
)ENGINE=InnoDB  DEFAULT CHARSET="utf8";

INSERT INTO `Federations` (`federationName`, `emailAddress`, `registrationAuthority`) VALUES
('eduGAIN', 'eduGAIN-ot@edugain.org', '*');

CREATE TABLE IF NOT EXISTS EntityDescriptors
(
entityID VARCHAR(255) NULL,
registrationAuthority VARCHAR(255) NOT NULL,
ignoreEntity BOOLEAN NOT NULL default 0,
lastCheck TIMESTAMP NULL default NULL,
checkResult BLOB NULL,
checkStatusCode VARCHAR(16) NULL,
previousCheckStatusCode VARCHAR(16) NULL,
technicalContacts BLOB NULL,
supportContacts BLOB NULL,
UNIQUE (entityID),
FOREIGN KEY (registrationAuthority) REFERENCES Federations(registrationAuthority)
)ENGINE=InnoDB  DEFAULT CHARSET="utf8";



