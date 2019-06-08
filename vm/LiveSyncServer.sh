#!/usr/bin/env bash

sudo apt-get update -y
sudo apt-get install -y software-properties-common acl htop curl git dos2unix
sudo add-apt-repository ppa:ondrej/php -y
sudo apt-get update -y
sudo apt-get install redis-server -y -qq
sudo apt-get install -y -qq php-apcu php7.3-fpm php7.3-dev php7.3-cli php7.3-tidy php7.3-json php7.3-intl php7.3-mysql php7.3-sqlite php7.3-curl php7.3-gd php7.3-mbstring php7.3-dom php7.3-xml php7.3-zip php7.3-bcmath
sudo sed -i 's|display_errors = Off|display_errors = On|' /etc/php/7.3/fpm/php.ini
sudo sed -i 's|memory_limit = 128M|memory_limit = 512M|' /etc/php/7.3/fpm/php.ini

sudo curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
sudo service php7.3-fpm restart
sudo apt-get install supervisor -y

git clone git://github.com/xivapi/xivapi.com.git /home/ubuntu/xivapi.com
cd /home/ubuntu/xivapi.com
git checkout rabbitmq
composer install

echo "Setup complete"
