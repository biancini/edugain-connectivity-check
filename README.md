# ECCS
eduGAIN Connectivity Check Service

# Requirements

- **MySQL** any version (tested with 5.5.41)
- **PHP5** any version (tested with 5.5.21 but should work even with different versions)
- **php5-mysqlnd** package any version (tested with 5.5.22)
- **php5-mysql** package any version (tested with 5.5.21)
- **php5-curl** package any version (tested with 5.5.22)
- **php5-json** package any version (tested with 1.3.6-1)
- **Apache2** (with mod-php5) 

# HOWTO Install the Service (on Ubuntu architecture)

0. Install the requiremets packages:

```sh
# sudo apt-get install apache2 php5 libapache2-mod-php5 mysql-server
```

1. Install the requirements libraries:

```sh      
# sudo apt-get install php5-curl php5-json php5-mysqlnd
```

2. Be sure to have enabled mod_alias apache module: 

```sh
# sudo a2enmod alias
```

3. Retrieve the service code and put it into the `/opt` directory
        
```sh
git clone --recursive https://code.geant.net/stash/scm/~switch.haemmerle/edugain-connectivity-check.git /opt/edugain-connectivity-check
```

4. Create a new site for ECCS on the Apache instance:

```apache
vim /etc/apache2/sites-available/eccs.conf
   
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
```

5. Enable the new apache site:

```sh
# sudo a2ensite eccs.conf ; service apache2 reload
```

6. Modify the "**password_db_mccs**" value inside the **database/mccs_db.sql** file and import it into your mysql server:
        
```sh
# mysql -u root -pPASSWORD < /opt/mccs/database/mccs_db.sql
```

7. Copy the **properties.ini.php.example** to **properties.ini.php** in the folder **check_script** and change it with your DB and Mail parameters.

8. Copy the **properties.ini.php.example** to **properties.ini.php** in the folder **web** and change it with your DB parameters (in this case the user created should only have SELECT grant on the tables of the database).

9. Add a line to the crontab (`crontab -e`) to repeat the script every day at 5 o'clock:

```cron
00 5 * * * cd /opt/MCCS/check_script; /usr/bin/php mccs.php > /var/log/eccs.log
```
  
10. Open a web browser and go to the ECCS Page: https://**FULL.QUALIFIED.DOMAIN.NAME**/eccs

11. Enjoy yourself

# Useful notes
1. HOWTO Disable an entity on the service's database:

```sql
UPDATE EntityDescriptors SET ignoreEntity = 1, ignoreReason = 'Uses Javascript to redirect', currentResult = NULL, previousResult = NULL WHERE entityID = 'https://idp-test-1.example.org/SSO/saml2/idp';
```

2. HOWTO Disable more than one entity on the service's database:

```sql
UPDATE EntityDescriptors SET ignoreReason = 'Due to SSL issues', ignoreEntity = 1, currentResult = NULL, previousResult = NULL WHERE entityID IN ('https://idp-test-1.example.org/idp/shibboleth', 'https://idp-test-2.example.org/idp/shibboleth');
```

3. HOWTO Disable an entire Federation on the service's database:

```sql
UPDATE EntityDescriptors SET ignoreEntity = 1, ignoreReason = 'Federation excluded from check', currentResult = NULL, previousResult = NULL WHERE registrationAuthority = 'https://registrationAuthority_1.example.org';
```

4. HOWTO Disable more than one Federation on the service's database:

```sql
UPDATE EntityDescriptors SET ignoreEntity = 1, ignoreReason = 'Federation excluded from check', currentResult = NULL, previousResult = NULL WHERE registrationAuthority IN ('https://registrationAuthority_1.example.org', 'http://registrationAuthority_2.example.org/');
```

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
[DEBUG] config - Loading config /opt/MCCS/tests/view/karma.config.js
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
