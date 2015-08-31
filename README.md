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

        sudo apt-get install apache2 php5 libapache2-mod-php5 mysql-server

1. Install the requirements libraries:
      
        sudo apt-get install php5-curl php5-json php5-mysqlnd

2. Be sure to have enabled mod_alias apache module: 

        sudo a2enmod alias

3. Retrieve the service code and put it into the `/opt` directory

        git clone --recursive https://code.geant.net/stash/scm/~switch.haemmerle/edugain-connectivity-check.git /opt/edugain-connectivity-check

4. Create a new site for ECCS on the Apache instance:

        vim /etc/apache2/sites-available/eccs.conf
   
        Apache < 2.4 : 

        <IfModule mod_alias.c>
            Alias /eccs /opt/edugain-connectivity-check/web/home

            <Directory /opt/edugain-connectivity-check/web/home>
                Options Indexes MultiViews FollowSymLinks
                Order deny,allow
                Allow from all
            </Directory>
        </IfModule>

        
        Apache >= 2.4 :

        <IfModule mod_alias.c>
            Alias /eccs /opt/edugain-connectivity-check/web/home

            <Directory /opt/edugain-connectivity-check/web/home>
                Options Indexes MultiViews FollowSymLinks
                Require all granted
            </Directory>
        </IfModule>

5. Enable the new apache site:

        sudo a2ensite eccs.conf ; service apache2 reload

6. Modify the "**password_db_mccs**" value inside the **database/mccs_db.sql** file and import it into your mysql server:

        mysql -u root -pPASSWORD < /opt/mccs/database/mccs_db.sql

7. Copy the **properties.ini.example** to **properties.ini** in the folder **check_script** and change it with your DB and Mail parameters.

8. Copy the **properties.ini.php.example** to **properties.ini.php** in the folder **web** and change it with your DB parameters (in this case the user created should only have SELECT grant on the tables of the database).

9. Add a line to the crontab (`crontab -e`) to repeat the script every day at 5 o'clock:

        00 5 * * * root /usr/bin/php /opt/mccs/check_script/mccs.php > /var/log/eccs.log
  
10. Open a web browser and go to the MCCS Page: https://**FULL.QUALIFIED.DOMAIN.NAME**/eccs

11. Enjoy yourself

# Useful notes
1. HOWTO Disable an entity on the service's database:

        UPDATE EntityDescriptors SET ignoreEntity = 1, ignoreReason = 'Uses Javascript to redirect', currentResult = NULL, previousResult = NULL WHERE entityID = 'https://idp-test-1.example.org/SSO/saml2/idp';

2. HOWTO Disable more than one entity on the service's database:

        UPDATE EntityDescriptors SET ignoreReason = 'Due to SSL issues', ignoreEntity = 1, currentResult = NULL, previousResult = NULL WHERE entityID IN ('https://idp-test-1.example.org/idp/shibboleth', 'https://idp-test-2.example.org/idp/shibboleth');

3. HOWTO Disable an entire Federation on the service's database:

        UPDATE EntityDescriptors SET ignoreEntity = 1, ignoreReason = 'Federation excluded from check', currentResult = NULL, previousResult = NULL WHERE registrationAuthority = 'https://registrationAuthority_1.example.org';

4. HOWTO Disable more than one Federation on the service's database:

         UPDATE EntityDescriptors SET ignoreEntity = 1, ignoreReason = 'Federation excluded from check', currentResult = NULL, previousResult = NULL WHERE registrationAuthority IN ('https://registrationAuthority_1.example.org', 'http://registrationAuthority_2.example.org/');
