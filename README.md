# ECCS ![Build Status](https://travis-ci.org/biancini/edugain-connectivity-check.svg?branch=master) ![Codacy Badge](https://api.codacy.com/project/badge/grade/cd49b92435fc4793941ab4517fba4144)
*eduGAIN Connectivity Check Service*

# Requirements

- **MySQL** any version (tested with 5.5.41)
- **PHP5** any version (tested with 5.5.21 but should work even with different versions)
- **php5-mysqlnd** package any version (tested with 5.5.22)
- **php5-mysql** package any version (tested with 5.5.21)
- **php5-curl** package any version (tested with 5.5.22)
- **php5-json** package any version (tested with 1.3.6-1)
- **Apache2** (with mod-php5)
- **PhantomsJS** (tested wih v2.1.1)

# HOWTO Install the Service (on Ubuntu architecture)

0. Install the requirements packages:

        # sudo apt-get install apache2 php5 libapache2-mod-php5 mysql-server git

1. Install the requirements libraries:

        # sudo apt-get install libfontconfig php5-json php5-mysqlnd

2. Install the compiled version 2.1.1 of PhantomJS to your /usr/local/bin directory:

    * Create a new directory into /usr/local/src:

            # mkdir /usr/local/src/phantomjs ; cd /usr/local/src/phantomjs

    * Retrieve the PhantomJS binary and extract it:

            # wget https://bitbucket.org/ariya/phantomjs/downloads/phantomjs-2.1.1-linux-x86_64.tar.bz2

            # bzip2 -d phantomjs-2.1.1-linux-x86_64.tar.bz2
            # tar -xf phantomjs-2.1.1-linux-x86_64.tar 

    * Put the phantoms executable placed inside **/bin** into **/usr/local/bin** directory:

            # cd phantomjs-2.1.1-linux-x86_64/bin ; cp phantomjs /usr/local/bin

    * Verify the version of the PhantomJS installed (should return "2.1.1"):

            # phantomjs -v

3. Be sure to have enabled *mod_alias* apache module:

        # sudo a2enmod alias

4. Retrieve the service code and put it into the `/opt` directory

        # git clone --recursive https://code.geant.net/stash/scm/~switch.haemmerle/edugain-connectivity-check.git /opt/edugain-connectivity-check

5. Create a new site for ECCS on the Apache instance:

        # vim /etc/apache2/sites-available/eccs.conf

        Apache < 2.4 :

        <IfModule mod_alias.c>
           Alias /eccs /opt/edugain-connectivity-check/web

           <Directory /opt/edugain-connectivity-check/web>
              Options Indexes MultiViews FollowSymLinks
              Order deny,allow
              Allow from all
           </Directory>
        </IfModule>


        Apache >= 2.4 :

        <IfModule mod_alias.c>
           Alias /eccs /opt/edugain-connectivity-check/web

           <Directory /opt/edugain-connectivity-check/web>
              Options Indexes MultiViews FollowSymLinks
              Require all granted
           </Directory>
        </IfModule>

6. Enable the new apache site:

        # sudo a2ensite eccs.conf ; service apache2 reload

7. Modify the **password_db_eccs** value inside the **database/eccs_db.sql** file and import it into your mysql server:

        # mysql -u root -pPASSWORD < /opt/edugain-connectivity-check/database/eccs_db.sql

8. Copy the **properties.ini.php.example** to **properties.ini.php** in the folder **check_script** and change it with your DB and Mail parameters.

9. Copy the **properties.ini.php.example** to **properties.ini.php** in the folder **web/services** and change it with your DB parameters (in this case the user created should only have SELECT grant on the tables of the database).

10. Add a line to the crontab (`crontab -e`) to repeat the script every day at 8 o'clock:

        00 8 * * * cd /opt/edugain-connectivity-check/check_script ; /usr/bin/php mccs.php > /var/log/eccs.log

11. Open a web browser and go to the ECCS Page: https://**FULL.QUALIFIED.DOMAIN.NAME**/eccs

12. Enjoy yourself

# Useful notes

1. **HOWTO Disable an entity on the service's database:**

        UPDATE EntityDescriptors SET ignoreEntity = 1, ignoreReason = 'Uses Javascript to redirect', currentResult = NULL, previousResult = NULL WHERE entityID = 'https://idp-test-1.example.org/SSO/saml2/idp';

2. **HOWTO Disable more than one entity on the service's database:**

        UPDATE EntityDescriptors SET ignoreReason = 'Due to SSL issues', ignoreEntity = 1, currentResult = NULL, previousResult = NULL WHERE entityID IN ('https://idp-test-1.example.org/idp/shibboleth', 'https://idp-test-2.example.org/idp/shibboleth');

3. **HOWTO Disable one or more Federations from the service's check:**

      Configure the [disabled_federation] settings inside the **properties.ini.php** file of the **check_script** folder by listing the federations that you want disable by separating them with a comma.

      For Example:

        [disabled_federation]
        reg_auth = "http://www.federation1.nl/,https://www.federation2.dk,http://federation3.no/"

      To **enable again** the federation, remove it from the comma-separated list.

4. **HOWTO Empty the DB for a clean test:**

        # mysql -u root -pPASSWORD < /opt/edugain-connectivity-check/database/clean_eccs_db.sql


# How to send emails to eduGAIN Steering Group members

1. Configure the [email] settings inside the **properties.ini.php** file of the **check_script** folder:

        [email]
        host = smtp.server.edugain.net                           (your mail server)
        port = 25                                                (port used to send emails)
        tls = true                                               (set to "true" if you use TLS)
        user = username                                          (your username)
        password = password                                      (your password)
        from = edugain-integration@geant.net                     (leave it as is)
        replyTo = edugain-integration@geant.net                  (leave it as is)
        baseurl = https://server-hosting-eccs.edugain.net/eccs   (change with the correct value)
        test_recipient = test.user@geant.net                     (leave it empty to send email to delegate/deputy)

2. Run the command:

        cd /opt/edugain-connectivity-check/check_script ; /usr/bin/mailer.php


# How to test the code

The code developed can be easily tested with automated testing tools like PHPspec or AngularJS testing.
To test the various components do as explained in the following:

## View

For the AngularJS web interface, you can use karma:

```sh
# apt-get install npm nodejs
# npm install -g karma-cli
# npm install -g karma-junit-reporter karma-ng-scenario karma-junit-reporter karma-phantomjs-launcher karma-coverage karma-chai-as-promised

# cd tests/view/
# karma start karma.config.js
```

The output for the command should show all tests passed with success:

```sh
[DEBUG] config - Loading config /opt/edugain-connectivity-check/tests/view/karma.config.js
Chrome 44.0.2403 (Windows 10 0.0.0): Executed 18 of 18 SUCCESS (0.155 secs / 0.094 secs)
```

## Json

For the Json API used by the webpage, you can use PHPSpec:

```sh
# cd tests/apis/
# curl http://getcomposer.org/installer | php
# php composer.phar install
# ./bin/phpspec run
```

The output for the command should show all tests passed with success:

```sh
4 specs
32 examples (32 passed)
96ms
```
