#!/bin/bash
# install script for http://wuhu.function.hu/

if [ "$EUID" -ne 0 ]
then
  echo "Please run as root"
  exit
fi

# -------------------------------------------------
# PHP 5.6 packages
apt-get install apt-transport-https lsb-release ca-certificates
wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list
apt-get update

apt-get -y install \
  apache2 \
  libapache2-mod-php5 \
  mysql-server-5.5 \
  mc \
  openssl \
  php5 \
  php5-gd \
  php5-mysqlnd \
  php5-curl \
  php5-mbstring \
  ssh

# -------------------------------------------------
# set up the files / WWW dir

mkdir /var/www/entries_private
mkdir /var/www/entries_public
mkdir /var/www/screenshots
mkdir /var/www/www_admin_cert

chmod -R g+rw /var/www
chown -R www-data:www-data /var/www

# -------------------------------------------------
# set up PHP

for i in /etc/php/*/*/php.ini
do
  sed -i -e 's/^upload_max_filesize.*$/upload_max_filesize = 128M/' $i
  sed -i -e 's/^post_max_size.*$/post_max_size = 256M/' $i
  sed -i -e 's/^memory_limit.*$/memory_limit = 512M/' $i
  sed -i -e 's/^session.gc_maxlifetime.*$/session.gc_maxlifetime = 604800/' $i
  sed -i -e 's/^short_open_tag.*$/short_open_tag = On/' $i
done

# -------------------------------------------------
# set up SSL for www_admin

openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /var/www/www_admin_cert/ssl.key -out /var/www/www_admin/ssl.crt
openssl dhparam -out /var/www/www_admin_cert/dhparam.pem 2048

echo -e \
  "SSLCipherSuite EECDH+AESGCM:EDH+AESGCM:AES256+EECDH:AES256+EDH\n" \
  "SSLProtocol All -SSLv2 -SSLv3\n" \
  "SSLHonorCipherOrder On\n" \
  "#Header always set Strict-Transport-Security \"max-age=63072000; includeSubdomains; preload\"\n" \
  "Header always set Strict-Transport-Security \"max-age=63072000; includeSubdomains\"\n" \
  "Header always set X-Frame-Options DENY\n" \
  "Header always set X-Content-Type-Options nosniff\n" \
  "SSLCompression off \n" \
  "SSLSessionTickets Off\n" \
  "SSLUseStapling on \n" \
  "SSLStaplingCache \"shmcb:logs/stapling-cache(150000)\"\n" \
  "SSLOpenSSLConfCmd DHParameters \"/var/www/www_admin_cert/dhparam.pem\"\n" \
  > /etc/apache2/conf-available/wuhu-ssl.conf

a2enmod ssl
a2enmod headers
a2enconf wuhu-ssl

# -------------------------------------------------
# set up Apache

rm /etc/apache2/sites-enabled/*

echo -e \
  "<VirtualHost *:80>\n" \
  "\tDocumentRoot /var/www/www_party\n" \
  "\t<Directory />\n" \
  "\t\tOptions FollowSymLinks\n" \
  "\t\tAllowOverride All\n" \
  "\t</Directory>\n" \
  "\tErrorLog \${APACHE_LOG_DIR}/party_error.log\n" \
  "\tCustomLog \${APACHE_LOG_DIR}/party_access.log combined\n" \
  "\t</VirtualHost>\n" \
  "\n" \
  "<VirtualHost *:443>\n" \
  "\tDocumentRoot /var/www/www_admin\n" \
  "\tServerName admin.lan\n" \
  "\t<Directory />\n" \
  "\t\tOptions FollowSymLinks\n" \
  "\t\tAllowOverride All\n" \
  "\t</Directory>\n" \
  "\tErrorLog \${APACHE_LOG_DIR}/admin_error.log\n" \
  "\tCustomLog \${APACHE_LOG_DIR}/admin_access.log combined\n" \
  "\tSSLEngine on\n" \
  "\tSSLCertificateFile /var/www/www_admin_cert/dhparam.pem\n" \
  "\tSSLCertificateKeyFile /var/www/www_admin_cert/ssl.key\n" \
  "</VirtualHost>\n" \
  > /etc/apache2/sites-available/wuhu.conf

a2ensite wuhu

apache2ctl configtest
service apache2 restart

# -------------------------------------------------
# TODO? set up nameserver / dhcp?

# -------------------------------------------------
# set up MySQL

echo -e "Enter a MySQL password for the Wuhu user: \c"
read -s WUHU_MYSQL_PASS

echo "Now connecting to MySQL, please enter the MySQL root password:"
echo -e \
  "CREATE DATABASE wuhu;\n" \
  "GRANT ALL PRIVILEGES ON wuhu.* TO 'wuhu'@'%' IDENTIFIED BY '$WUHU_MYSQL_PASS';\n" \
  | mysql -u root -p

# -------------------------------------------------
# We're done, wahey!

printf "\n\n\n*** CONGRATULATIONS, Wuhu is now ready to configure at http://admin.lan\n"
