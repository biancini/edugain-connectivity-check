# MCCS
Metadata Consumption Check Service

# HOWTO Install the Service (on Debian architecture)

0. Install the requiremets packages:

        sudo apt-get install apache2 libapache2-mod-php5 mysql-server

1. Install the requirements libraries:
      
        sudo apt-get install php5 php5-curl php5-json php5-mysql php5-mysqlnd

2. Be sure to have enabled mod_alias apache module: 

        sudo a2ensite alias

3. Create a new site for MCCS on the Apache instance:

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

4. Enable the new apache site:

        sudo a2ensite mccs.conf

5. Import the MCCS DB provided by database/mccs_db.sql

        mysql -u root -pPASSWORD < /opt/mccs/database/mccs_db.sql

6. Copy the **properties.ini.example** to **properties.ini** and change it with your DB and Mail parameter.
   Make attention on the variables:
      * **map_url**: the metadata feed for eduGAIN
      * **parallel**: the number of processes executed simultaneously
      * **check_history**: the number of check to maintain in the DB for each IdP


7. Add a cron job to the crontab for executing the script:

        crontab -e
  
  add the line:
   
        00 5 * * * root /usr/bin/php /opt/mccs/check_script/mccs.php > /var/log/mccs.log
   
8. Open a web browser and go to the MCCS Page: https://**FULL.QUALIFIED.DOMAIN.NAME**/mccs