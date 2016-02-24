use eccs_db;

DELETE FROM Federations;
DELETE FROM EntityDescriptors;
DELETE FROM EntityChecks;

INSERT IGNORE INTO Federations (federationName, emailAddress, registrationAuthority, updated, sgDelegateName, sgDelegateSurname, sgDelegateEmail, sgDeputyName, sgDeputySurname, sgDeputyEmail) VALUES
   ('eduGAIN', 'eduGAIN-ot@edugain.org', '*','1',NULL,NULL,NULL,NULL,NULL,NULL);
