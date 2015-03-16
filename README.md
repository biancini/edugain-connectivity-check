# MCCS
Metadata Consumption Check Service

# HOWTO Install the Service (on Ubuntu architecture)

   0. Install the requiremets packages:

        sudo apt-get install apache2 php5 libapache2-mod-php5 mysql-server

1. Install the requirements libraries:
      
        sudo apt-get install php5-curl php5-json php5-mysqlnd

2. Be sure to have enabled mod_alias apache module: 

        sudo a2enmod alias

3. Retrieve the service code and put it into the `/opt` directory

        git clone --recursive https://malavolti@bitbucket.org/biancini/mccs.git /opt/mccs

4. Create a new site for MCCS on the Apache instance:

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

5. Enable the new apache site:

        sudo a2ensite mccs.conf ; service apache2 reload

6. Modify the "**password_db_mccs**" value inside the **database/mccs_db.sql** file and import it into your mysql server:

        mysql -u root -pPASSWORD < /opt/mccs/database/mccs_db.sql

7. Copy the **properties.ini.example** to **properties.ini** and change it with your DB and Mail parameters.

8. Add a line to the crontab (`crontab -e`) to repeat the script every day at 5 o'clock:

        00 5 * * * root /usr/bin/php /opt/mccs/check_script/mccs.php > /var/log/mccs.log
   
9. Open a web browser and go to the MCCS Page: https://**FULL.QUALIFIED.DOMAIN.NAME**/mccs

10. Enjoy yourself