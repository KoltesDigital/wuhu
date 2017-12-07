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
  php5 \
  php5-gd \
  php5-mysqlnd \
  php5-curl \
  php5-mbstring \
  mysql-server-5.5 \
  libapache2-mod-php5 \
  mc \
  git \
  ssh \
  sudo

# -------------------------------------------------
# set up the files / WWW dir

mkdir /var/www/entries_private
mkdir /var/www/entries_public
mkdir /var/www/screenshots

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
  "<VirtualHost *:80>\n" \
  "\tDocumentRoot /var/www/www_admin\n" \
  "\tServerName admin.lan\n" \
  "\t<Directory />\n" \
  "\t\tOptions FollowSymLinks\n" \
  "\t\tAllowOverride All\n" \
  "\t</Directory>\n" \
  "\tErrorLog \${APACHE_LOG_DIR}/admin_error.log\n" \
  "\tCustomLog \${APACHE_LOG_DIR}/admin_access.log combined\n" \
  "</VirtualHost>\n" \
  > /etc/apache2/sites-available/wuhu.conf

a2ensite wuhu

echo "Restarting Apache..."
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
