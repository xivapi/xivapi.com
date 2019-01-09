#!/usr/bin/env bash

sudo apt-get update -y
sudo apt-get install -y software-properties-common acl htop curl git dos2unix
sudo add-apt-repository ppa:ondrej/php -y
sudo apt-get update -y
sudo apt-get install -y -qq php7.2-fpm php-apcu php7.2-dev php7.2-cli php7.2-tidy php7.2-json php7.2-fpm php7.2-intl php7.2-mysql php7.2-sqlite php7.2-curl php7.2-gd php7.2-mbstring php7.2-dom php7.2-xml php7.2-zip php7.2-tidy php7.2-bcmath
sudo sed -i 's|display_errors = Off|display_errors = On|' /etc/php/7.2/fpm/php.ini
sudo sed -i 's|memory_limit = 128M|memory_limit = 500M|' /etc/php/7.2/fpm/php.ini

sudo curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
sudo service php7.2-fpm restart
sudo apt-get install supervisor -y

git clone git://github.com/xivapi/xivapi.com.git /home/ubuntu/xivapi.com
cd /home/ubuntu/xivapi.com
git checkout rabbitmq
composer install

echo "Setup complete"
