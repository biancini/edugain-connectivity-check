# MCCS
Metadata Consumption Check Service

# HOWTO Install the Service (on Debian architecture)

1. Be sure to have enabled mod_alias apache module: 

      sudo a2ensite alias

2. Create a new site for MCCS on the Apache instance:

      vim /etc/apache2/sites-available/mccs.conf
   
      <IfModule mod_alias.c>
         Alias /mccs /opt/mccs/web
         
         <Directory /opt/mccs/web>
            Options Indexes MultiViews FollowSymLinks
            Order deny,allow
            Allow from all
            Require all granted
         </Directory>
      </IfModule>

3. Enable the new apache site:

      sudo a2ensite mccs.conf

4. Import the MCCS DB provided by database/mccs_db.sql

      mysql -u root -pPASSWORD < /opt/mccs/database/mccs_db.sql
   
5. Execute the script:
   
      cd /opt/mccs ; php check_script/mccs.php
   
6. Open a web browser and go to the MCCS Page: https://FULL.QUALIFIED.DOMAIN.NAME/mccs